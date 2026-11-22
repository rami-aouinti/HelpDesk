<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\TicketMessageCreated;
use App\ServiceDesk\Application\Commands\AddTicketMessage\AddTicketMessageCommand;
use App\ServiceDesk\Application\Commands\AddTicketMessage\AddTicketMessageHandler;
use App\ServiceDesk\Application\Commands\AddTicketMessageWithAttachments\AddTicketMessageWithAttachmentsCommand;
use App\ServiceDesk\Application\Commands\AddTicketMessageWithAttachments\AddTicketMessageWithAttachmentsHandler;
use App\ServiceDesk\Application\Commands\AddTicketMessageWithAttachments\TicketAttachmentInput;
use App\ServiceDesk\Application\Commands\AssignTicketToTeam\AssignTicketToTeamCommand;
use App\ServiceDesk\Application\Commands\AssignTicketToTeam\AssignTicketToTeamHandler;
use App\ServiceDesk\Application\Commands\LinkTicketToProject\LinkTicketToProjectCommand;
use App\ServiceDesk\Application\Commands\LinkTicketToProject\LinkTicketToProjectHandler;
use App\ServiceDesk\Application\Commands\AssignTicketToAgent\AssignTicketToAgentCommand;
use App\ServiceDesk\Application\Commands\AssignTicketToAgent\AssignTicketToAgentHandler;
use App\ServiceDesk\Application\Commands\UnassignTicketFromAgent\UnassignTicketFromAgentCommand;
use App\ServiceDesk\Application\Commands\UnassignTicketFromAgent\UnassignTicketFromAgentHandler;
use App\ServiceDesk\Application\Commands\UpdateTicketEffort\UpdateTicketEffortCommand;
use App\ServiceDesk\Application\Commands\UpdateTicketEffort\UpdateTicketEffortHandler;
use App\ServiceDesk\Application\Commands\UpdateTicket\UpdateTicketCommand;
use App\ServiceDesk\Application\Commands\UpdateTicket\UpdateTicketHandler;
use App\ServiceDesk\Domain\Exceptions\InvalidTicketData;
use App\ServiceDesk\Domain\Exceptions\TicketAssignmentRejected;
use App\ServiceDesk\Domain\Exceptions\TicketProjectLinkRejected;
use App\ServiceDesk\Application\Commands\CreateTicket\CreateTicketCommand;
use App\ServiceDesk\Application\Commands\CreateTicket\CreateTicketHandler;
use App\ServiceDesk\Application\Commands\ChangeTicketPriority\ChangeTicketPriorityCommand;
use App\ServiceDesk\Application\Commands\ChangeTicketPriority\ChangeTicketPriorityHandler;
use App\ServiceDesk\Application\Commands\ChangeTicketStatus\ChangeTicketStatusCommand;
use App\ServiceDesk\Application\Commands\ChangeTicketStatus\ChangeTicketStatusHandler;
use App\ServiceDesk\Domain\Enums\TicketPriority;
use App\ServiceDesk\Domain\Enums\TicketStatus;
use App\ServiceDesk\Domain\Exceptions\InvalidTicketTransition;
use App\Http\Controllers\Controller;
use App\Http\Middleware\CacheApiGetResponse;
use App\Http\Requests\StoreTicketMessageRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketMessageResource;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\TicketService;
use App\Services\TicketSearchService;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class TicketController extends Controller
{
    public function index(Request $request, TicketSearchService $ticketSearch): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Ticket::class);

        $filters = $request->validate([
            'status' => ['sometimes', 'string', Rule::in(['open', 'in_progress', 'waiting_for_customer', 'resolved', 'closed'])],
            'priority' => ['sometimes', 'string', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'team_id' => ['sometimes', 'integer', 'min:1'],
            'project_id' => ['sometimes', 'integer', 'min:1'],
            'assigned_to' => ['sometimes', 'integer', 'min:1'],
            'sprint_id' => ['sometimes', 'integer', 'min:1', 'exists:sprints,id'],
            'search' => ['sometimes', 'string', 'max:100'],
            'unassigned' => ['sometimes', Rule::in(['true', 'false', '1', '0', 1, 0])],
            'created_from' => ['sometimes', 'date'],
            'created_to' => ['sometimes', 'date', Rule::when($request->filled('created_from'), ['after_or_equal:created_from'])],
            'updated_from' => ['sometimes', 'date'],
            'updated_to' => ['sometimes', 'date', Rule::when($request->filled('updated_from'), ['after_or_equal:updated_from'])],
            'sort_by' => ['sometimes', 'string', Rule::in(['created_at', 'updated_at', 'priority', 'status', 'subject'])],
            'sort_direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $searchIds = isset($filters['search']) ? $ticketSearch->searchIds(trim($filters['search'])) : null;

        $tickets = $this->visibleTickets($request->user())
            ->when(isset($filters['status']), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(isset($filters['priority']), fn (Builder $query) => $query->where('priority', $filters['priority']))
            ->when(isset($filters['team_id']), fn (Builder $query) => $query->where('team_id', $filters['team_id']))
            ->when(isset($filters['project_id']), fn (Builder $query) => $query->where('project_id', $filters['project_id']))
            ->when(isset($filters['assigned_to']), fn (Builder $query) => $query->where('assigned_to', $filters['assigned_to']))
            ->when(isset($filters['sprint_id']), fn (Builder $query) => $query->where('sprint_id', $filters['sprint_id']))
            ->when(isset($filters['unassigned']), fn (Builder $query) => filter_var($filters['unassigned'], FILTER_VALIDATE_BOOLEAN)
                ? $query->whereNull('assigned_to')
                : $query->whereNotNull('assigned_to'))
            ->when(isset($filters['created_from']), fn (Builder $query) => $query->whereDate('created_at', '>=', $filters['created_from']))
            ->when(isset($filters['created_to']), fn (Builder $query) => $query->whereDate('created_at', '<=', $filters['created_to']))
            ->when(isset($filters['updated_from']), fn (Builder $query) => $query->whereDate('updated_at', '>=', $filters['updated_from']))
            ->when(isset($filters['updated_to']), fn (Builder $query) => $query->whereDate('updated_at', '<=', $filters['updated_to']))
            ->when($searchIds !== null, fn (Builder $query) => $query->whereIn('id', $searchIds))
            ->when($searchIds === null && isset($filters['search']), function (Builder $query) use ($filters): void {
                $term = trim($filters['search']);
                $query->where(function (Builder $nested) use ($term): void {
                    $nested->where('subject', 'like', '%' . $term . '%')
                        ->orWhere('description', 'like', '%' . $term . '%');

                    if (ctype_digit($term)) {
                        $nested->orWhereKey((int) $term);
                    }
                });
            })
            ->with(['customer', 'assignedAgent', 'team', 'project', 'sprint' => $this->sprintWithProgress()])
            ->orderBy($filters['sort_by'] ?? 'created_at', $filters['sort_direction'] ?? 'desc')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return TicketResource::collection($tickets);
    }

    public function store(StoreTicketRequest $request, CreateTicketHandler $handler): JsonResponse
    {
        $data = $request->validated();
        $ticketId = $handler->handle(new CreateTicketCommand(
            subject: $data['subject'],
            description: $data['description'],
            priority: TicketPriority::from($data['priority']),
            projectId: isset($data['project_id']) ? (int) $data['project_id'] : null,
            customerId: (int) $request->user()->getKey(),
        ));

        $ticket = Ticket::query()
            ->with(['customer', 'assignedAgent', 'team', 'project', 'sprint' => $this->sprintWithProgress()])
            ->findOrFail($ticketId);

        return (new TicketResource($ticket))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Ticket $ticket): TicketResource
    {
        Gate::authorize('view', $ticket);

        $ticket->load([
            'customer',
            'assignedAgent',
            'team',
            'project',
            'sprint' => $this->sprintWithProgress(),
            'messages' => function ($query) use ($request): void {
                $query->with(['user', 'attachments'])->oldest();

                if ($request->user()->hasRole('customer')) {
                    $query->where('is_internal', false);
                }
            },
        ]);

        return new TicketResource($ticket);
    }

    public function update(
        UpdateTicketRequest $request,
        Ticket $ticket,
        TicketService $ticketService,
        ChangeTicketStatusHandler $statusHandler,
        ChangeTicketPriorityHandler $priorityHandler,
        AssignTicketToTeamHandler $teamAssignmentHandler,
        AssignTicketToAgentHandler $assignmentHandler,
        LinkTicketToProjectHandler $projectLinkHandler,
        UnassignTicketFromAgentHandler $unassignmentHandler,
        UpdateTicketEffortHandler $effortHandler,
        UpdateTicketHandler $mixedUpdateHandler,
    ): TicketResource {
        $data = $request->validated();

        if (count($data) > 1) {
            try {
                $mixedUpdateHandler->handle(new UpdateTicketCommand(
                    ticketId: (int) $ticket->getKey(),
                    actorId: (int) $request->user()->getKey(),
                    changes: $data,
                ));
            } catch (TicketAssignmentRejected|TicketProjectLinkRejected|InvalidTicketTransition|InvalidTicketData $exception) {
                $field = match (true) {
                    $exception instanceof TicketAssignmentRejected,
                    $exception instanceof TicketProjectLinkRejected => $exception->field,
                    $exception instanceof InvalidTicketTransition => 'status',
                    default => 'ticket',
                };
                throw ValidationException::withMessages([$field => $exception->getMessage()]);
            }
            $updatedTicket = $ticket->fresh();
        } elseif (
            $data !== []
            && array_diff(array_keys($data), ['estimated_minutes', 'elapsed_minutes']) === []
        ) {
            $effortHandler->handle(new UpdateTicketEffortCommand(
                ticketId: (int) $ticket->getKey(),
                actorId: (int) $request->user()->getKey(),
                estimatedMinutes: isset($data['estimated_minutes']) ? (int) $data['estimated_minutes'] : null,
                elapsedMinutes: isset($data['elapsed_minutes']) ? (int) $data['elapsed_minutes'] : null,
                updateEstimatedMinutes: array_key_exists('estimated_minutes', $data),
                updateElapsedMinutes: array_key_exists('elapsed_minutes', $data),
            ));

            $updatedTicket = $ticket->fresh();
        } elseif (array_keys($data) === ['project_id']) {
            try {
                $projectLinkHandler->handle(new LinkTicketToProjectCommand(
                    ticketId: (int) $ticket->getKey(),
                    actorId: (int) $request->user()->getKey(),
                    projectId: $data['project_id'] === null ? null : (int) $data['project_id'],
                ));
            } catch (TicketProjectLinkRejected $exception) {
                throw ValidationException::withMessages([
                    $exception->field => $exception->getMessage(),
                ]);
            }

            $updatedTicket = $ticket->fresh();
        } elseif (array_keys($data) === ['assigned_to'] && $data['assigned_to'] === null) {
            try {
                $unassignmentHandler->handle(new UnassignTicketFromAgentCommand(
                    ticketId: (int) $ticket->getKey(),
                    actorId: (int) $request->user()->getKey(),
                ));
            } catch (TicketAssignmentRejected $exception) {
                throw ValidationException::withMessages([
                    $exception->field => $exception->getMessage(),
                ]);
            }

            $updatedTicket = $ticket->fresh();
        } elseif (
            array_key_exists('assigned_to', $data)
            && $data['assigned_to'] !== null
            && array_diff(array_keys($data), ['team_id', 'assigned_to']) === []
        ) {
            $teamId = array_key_exists('team_id', $data)
                ? $data['team_id']
                : $ticket->team_id;

            if ($teamId === null) {
                throw ValidationException::withMessages([
                    'assigned_to' => 'Select a team before assigning an agent.',
                ]);
            }

            try {
                $assignmentHandler->handle(new AssignTicketToAgentCommand(
                    ticketId: (int) $ticket->getKey(),
                    actorId: (int) $request->user()->getKey(),
                    agentId: (int) $data['assigned_to'],
                    teamId: (int) $teamId,
                ));
            } catch (TicketAssignmentRejected $exception) {
                throw ValidationException::withMessages([
                    $exception->field => $exception->getMessage(),
                ]);
            }

            $updatedTicket = $ticket->fresh();
        } elseif (array_keys($data) === ['team_id']) {
            try {
                $teamAssignmentHandler->handle(new AssignTicketToTeamCommand(
                    ticketId: (int) $ticket->getKey(),
                    actorId: (int) $request->user()->getKey(),
                    teamId: $data['team_id'] === null ? null : (int) $data['team_id'],
                ));
            } catch (TicketAssignmentRejected $exception) {
                throw ValidationException::withMessages([
                    $exception->field => $exception->getMessage(),
                ]);
            }
            $updatedTicket = $ticket->fresh();
        } elseif (array_keys($data) === ['priority']) {
            $priorityHandler->handle(new ChangeTicketPriorityCommand(
                ticketId: (int) $ticket->getKey(),
                actorId: (int) $request->user()->getKey(),
                priority: TicketPriority::from($data['priority']),
            ));

            $updatedTicket = $ticket->fresh();
        } elseif (array_keys($data) === ['status']) {
            try {
                $statusHandler->handle(new ChangeTicketStatusCommand(
                    ticketId: (int) $ticket->getKey(),
                    actorId: (int) $request->user()->getKey(),
                    status: TicketStatus::from($data['status']),
                ));
            } catch (InvalidTicketTransition $exception) {
                throw ValidationException::withMessages([
                    'status' => $exception->getMessage(),
                ]);
            }

            $updatedTicket = $ticket->fresh();
        } else {
            $updatedTicket = $ticketService->update($ticket, $data, $request->user()->id);
        }

        return new TicketResource($updatedTicket->load(['customer', 'assignedAgent', 'team', 'project', 'sprint' => $this->sprintWithProgress()]));
    }

    public function storeMessage(
        StoreTicketMessageRequest $request,
        Ticket $ticket,
        AddTicketMessageHandler $handler,
        AddTicketMessageWithAttachmentsHandler $attachmentHandler,
    ): JsonResponse {
        $data = $request->validated();
        $isInternal = $request->boolean('is_internal');
        if ($request->file('attachments', []) === []) {
            $messageId = $handler->handle(new AddTicketMessageCommand(
                ticketId: (int) $ticket->getKey(),
                authorId: (int) $request->user()->getKey(),
                body: trim((string) ($data['body'] ?? '')),
                isInternal: $isInternal,
            ));
            CacheApiGetResponse::flush();
            $message = TicketMessage::query()->with(['user', 'attachments'])->findOrFail($messageId);

            return (new TicketMessageResource($message))
                ->response()
                ->setStatusCode(201);
        }

        $attachmentInputs = array_map(
            static fn ($file): TicketAttachmentInput => new TicketAttachmentInput(
                temporaryPath: $file->getPathname(),
                originalName: $file->getClientOriginalName(),
                mimeType: $file->getMimeType(),
                size: $file->getSize(),
            ),
            $request->file('attachments', []),
        );

        $messageId = $attachmentHandler->handle(new AddTicketMessageWithAttachmentsCommand(
            ticketId: (int) $ticket->getKey(),
            authorId: (int) $request->user()->getKey(),
            body: trim((string) ($data['body'] ?? '')),
            isInternal: $isInternal,
            attachments: $attachmentInputs,
        ));
        CacheApiGetResponse::flush();
        $message = TicketMessage::query()->with(['user', 'attachments'])->findOrFail($messageId);

        return (new TicketMessageResource($message))
            ->response()
            ->setStatusCode(201);
    }

    public function downloadAttachment(
        Request $request,
        Ticket $ticket,
        TicketAttachment $attachment,
        DownloadTicketAttachmentHandler $handler,
    ): StreamedResponse {
        Gate::authorize('view', $ticket);
        abort_unless($attachment->ticket_id === $ticket->id, 404);

        if (
            $request->user()->hasRole('customer')
            && $attachment->message()->where('is_internal', true)->exists()
        ) {
            abort(404);
        }

        $download = $handler->handle(new DownloadTicketAttachmentQuery(
            disk: $attachment->disk,
            path: $attachment->path,
            originalName: $attachment->original_name,
            mimeType: $attachment->mime_type,
        ));
        abort_if($download === null, 404);

        return response()->streamDownload(
            static function () use ($download): void {
                try {
                    fpassthru($download->stream);
                } finally {
                    if (is_resource($download->stream)) {
                        fclose($download->stream);
                    }
                }
            },
            $download->filename,
            [
                'Content-Type' => $download->mimeType,
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    private function sprintWithProgress(): Closure
    {
        return static function ($query): void {
            $query->withCount('tickets')
                ->withCount([
                    'tickets as unfinished_tickets_count' => static fn (Builder $tickets) => $tickets->where('status', '!=', 'closed'),
                ]);
        };
    }

    /**
     * @return Builder<Ticket>
     */
    private function visibleTickets(User $user): Builder
    {
        $query = Ticket::query();

        // Customers must never inherit global ticket visibility, even if a permission is misassigned.
        if ($user->hasRole('customer')) {
            return $query->where('user_id', $user->id);
        }

        if ($user->can('tickets.view.all')) {
            return $query;
        }

        if (! $user->hasRole('agent')) {
            return $query->whereRaw('1 = 0');
        }

        // Keep membership checks in SQL instead of issuing two separate pluck queries
        // and transferring potentially large ID lists from PostgreSQL to PHP.
        return $query
            ->where(function (Builder $builder) use ($user): void {
                $builder->whereNull('team_id')->orWhereExists(
                    DB::table('team_user')
                        ->selectRaw('1')
                        ->whereColumn('team_user.team_id', 'tickets.team_id')
                        ->where('team_user.user_id', $user->getKey()),
                );
            })
            ->where(function (Builder $builder) use ($user): void {
                $builder->whereNull('project_id')->orWhereExists(
                    DB::table('project_user')
                        ->selectRaw('1')
                        ->whereColumn('project_user.project_id', 'tickets.project_id')
                        ->where('project_user.user_id', $user->getKey()),
                );
            });
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\TicketMessageCreated;
use App\ServiceDesk\Application\Commands\AddTicketMessage\AddTicketMessageCommand;
use App\ServiceDesk\Application\Commands\AddTicketMessage\AddTicketMessageHandler;
use App\ServiceDesk\Application\Commands\AddTicketMessageWithAttachments\AddTicketMessageWithAttachmentsCommand;
use App\ServiceDesk\Application\Commands\AddTicketMessageWithAttachments\AddTicketMessageWithAttachmentsHandler;
use App\ServiceDesk\Application\Commands\AddTicketMessageWithAttachments\TicketAttachmentInput;
use App\ServiceDesk\Application\Queries\DownloadTicketAttachment\DownloadTicketAttachmentQuery;
use App\ServiceDesk\Application\Queries\DownloadTicketAttachment\DownloadTicketAttachmentHandler;
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
use App\ServiceDesk\Application\Commands\ClaimTicket\ClaimTicketCommand;
use App\ServiceDesk\Application\Commands\ClaimTicket\ClaimTicketHandler;
use App\ServiceDesk\Application\Commands\CreateTicket\CreateTicketHandler;
use App\ServiceDesk\Application\Commands\ChangeTicketPriority\ChangeTicketPriorityCommand;
use App\ServiceDesk\Application\Commands\ChangeTicketPriority\ChangeTicketPriorityHandler;
use App\ServiceDesk\Application\Commands\ChangeTicketStatus\ChangeTicketStatusCommand;
use App\ServiceDesk\Application\Commands\ChangeTicketStatus\ChangeTicketStatusHandler;
use App\ServiceDesk\Domain\Enums\TicketPriority;
use App\ServiceDesk\Domain\Enums\TicketStatus;
use App\ServiceDesk\Domain\Exceptions\InvalidTicketTransition;
use App\ServiceDesk\Domain\Exceptions\TicketClaimRejected;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Project;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class TicketController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->can('viewAny', Ticket::class), 403);

        $user = auth()->user();
        $projects = $user->can('projects.view.all') ? Project::query()->orderBy('name')->get() : $user->projects()->orderBy('name')->get();

        return view('tickets.index', ['projects' => $projects]);
    }

    public function create(): View
    {
        abort_unless(auth()->user()->can('create', Ticket::class), 403);

        $user = auth()->user();
        $projects = $user->can('projects.view.all') ? Project::query()->orderBy('name')->get() : $user->projects()->orderBy('name')->get();

        return view('tickets.create', ['projects' => $projects]);
    }

    public function store(StoreTicketRequest $request, CreateTicketHandler $handler): RedirectResponse
    {
        $data = $request->validated();
        $ticketId = $handler->handle(new CreateTicketCommand(
            subject: $data['subject'],
            description: $data['description'],
            priority: TicketPriority::from($data['priority']),
            projectId: isset($data['project_id']) ? (int) $data['project_id'] : null,
            customerId: (int) $request->user()->getKey(),
        ));

        return redirect()
            ->route($request->user()->hasRole('customer') ? 'portal.home' : 'tickets.index')
            ->with('success', "Ticket #{$ticketId} created successfully.");
    }

    public function show(Ticket $ticket): View
    {
        abort_unless(auth()->user()->can('view', $ticket), 403);

        $ticket->load(['customer', 'assignedAgent', 'team', 'project', 'sprint', 'histories.user']);

        $messagesQuery = $ticket->messages()->with(['user', 'attachments'])->oldest();
        if (auth()->user()->hasRole('customer')) {
            $messagesQuery->where('is_internal', false);
        }

        $user = auth()->user();
        $teams = Team::query()->with('members')->orderBy('name');
        $agents = User::query()->role('agent')->where('is_active', true)->orderBy('name');

        if (! $user->can('tickets.view.all')) {
            if ($user->hasRole('agent')) {
                $teamIds = $user->teams()->pluck('teams.id');
                $teams->whereIn('id', $teamIds);
                $agents->whereHas('teams', fn ($query) => $query->whereIn('teams.id', $teamIds));
            } else {
                $teams->whereRaw('1 = 0');
                $agents->whereRaw('1 = 0');
            }
        }

        $histories = $ticket->histories;
        $userIds = $histories->where('action', 'assigned_to')->flatMap(fn ($history) => [$history->old_value, $history->new_value])->filter()->unique()->values();
        $teamIds = $histories->where('action', 'team_id')->flatMap(fn ($history) => [$history->old_value, $history->new_value])->filter()->unique()->values();
        $projectIds = $histories->where('action', 'project_id')->flatMap(fn ($history) => [$history->old_value, $history->new_value])->filter()->unique()->values();

        return view('tickets.show', [
            'ticket' => $ticket,
            'messages' => $messagesQuery->get(),
            'teams' => $teams->get(),
            'agents' => $agents->get(),
            'projects' => $user->can('projects.view.all') ? Project::query()->orderBy('name')->get() : $user->projects()->orderBy('name')->get(),
            'historyUsers' => User::query()->whereIn('id', $userIds)->get()->keyBy('id'),
            'historyTeams' => Team::query()->whereIn('id', $teamIds)->get()->keyBy('id'),
            'historyProjects' => Project::query()->whereIn('id', $projectIds)->get()->keyBy('id'),
        ]);
    }

    public function storeMessage(Request $request, Ticket $ticket, AddTicketMessageHandler $handler, AddTicketMessageWithAttachmentsHandler $attachmentHandler): RedirectResponse
    {
        abort_unless($request->user()->can('view', $ticket), 403);

        $data = $request->validate([
            'body' => ['nullable', 'required_without:attachments', 'string', 'min:2', 'max:10000'],
            'is_internal' => ['sometimes', 'boolean'],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => [
                'required',
                'file',
                'max:10240',
                'mimes:pdf,jpg,jpeg,png,txt,csv,doc,docx,xls,xlsx',
                'extensions:pdf,jpg,jpeg,png,txt,csv,doc,docx,xls,xlsx',
            ],
        ]);
        $isInternal = $request->boolean('is_internal');
        if ($isInternal) {
            abort_unless($request->user()->can('update', $ticket), 403);
        }

        if ($request->file('attachments', []) === []) {
            $handler->handle(new AddTicketMessageCommand(
                ticketId: (int) $ticket->getKey(),
                authorId: (int) $request->user()->getKey(),
                body: trim((string) ($data['body'] ?? '')),
                isInternal: $isInternal,
            ));

            return redirect()
                ->route('tickets.show', $ticket)
                ->with('success', $isInternal ? 'Internal note added.' : 'Reply sent successfully.');
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

        $attachmentHandler->handle(new AddTicketMessageWithAttachmentsCommand(
            ticketId: (int) $ticket->getKey(),
            authorId: (int) $request->user()->getKey(),
            body: trim((string) ($data['body'] ?? '')),
            isInternal: $isInternal,
            attachments: $attachmentInputs,
        ));

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', $isInternal ? 'Internal note added.' : 'Reply sent successfully.');
    }

    public function downloadAttachment(
        Request $request,
        Ticket $ticket,
        TicketAttachment $attachment,
        DownloadTicketAttachmentHandler $handler,
    ): StreamedResponse {
        abort_unless($request->user()->can('view', $ticket), 403);
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
        abort_if(! $download instanceof \App\ServiceDesk\Application\Queries\DownloadTicketAttachment\TicketAttachmentDownload, 404);

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

    public function claim(Request $request, Ticket $ticket, ClaimTicketHandler $handler): RedirectResponse
    {
        abort_unless($request->user()->hasRole('agent'), 403);
        abort_unless($request->user()->can('update', $ticket), 403);

        $data = $request->validate([
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
        ]);

        try {
            $handler->handle(new ClaimTicketCommand(
                ticketId: (int) $ticket->getKey(),
                agentId: (int) $request->user()->getKey(),
                teamId: isset($data['team_id']) ? (int) $data['team_id'] : null,
            ));
        } catch (TicketClaimRejected $exception) {
            throw ValidationException::withMessages([
                $exception->field => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Ticket claimed successfully.');
    }

    /**
     * @throws Throwable
     */
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
        UpdateTicketHandler $mixedUpdateHandler
    ): RedirectResponse {
        $data = $request->validated();

        // Multi-field writes are handled atomically by one application use case.
        if (count($data) > 1) {
            try {
                $mixedUpdateHandler->handle(new UpdateTicketCommand(
                    ticketId: (int) $ticket->getKey(),
                    actorId: (int) $request->user()->getKey(),
                    changes: $data,
                ));
            } catch (TicketAssignmentRejected | TicketProjectLinkRejected | InvalidTicketTransition | InvalidTicketData $exception) {
                $field = match (true) {
                    $exception instanceof TicketAssignmentRejected,
                    $exception instanceof TicketProjectLinkRejected => $exception->field,
                    $exception instanceof InvalidTicketTransition => 'status',
                    default => 'ticket',
                };
                throw ValidationException::withMessages([$field => $exception->getMessage()]);
            }
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
        } elseif (array_keys($data) === ['priority']) {
            $priorityHandler->handle(new ChangeTicketPriorityCommand(
                ticketId: (int) $ticket->getKey(),
                actorId: (int) $request->user()->getKey(),
                priority: TicketPriority::from($data['priority']),
            ));
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
        } else {
            $ticketService->update($ticket, $data, $request->user()->id);
        }

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Ticket updated successfully.');
    }
}

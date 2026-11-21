<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\TicketCreated;
use App\Events\TicketMessageCreated;
use App\Http\Controllers\Controller;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class TicketController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Ticket::class);

        $filters = $request->validate([
            'status' => ['sometimes', 'string', Rule::in(['open', 'in_progress', 'waiting_for_customer', 'resolved', 'closed'])],
            'priority' => ['sometimes', 'string', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'team_id' => ['sometimes', 'integer', 'min:1'],
            'project_id' => ['sometimes', 'integer', 'min:1'],
            'assigned_to' => ['sometimes', 'integer', 'min:1'],
            'search' => ['sometimes', 'string', 'max:100'],
            'unassigned' => ['sometimes', 'boolean'],
            'created_from' => ['sometimes', 'date'],
            'created_to' => ['sometimes', 'date', 'after_or_equal:created_from'],
            'updated_from' => ['sometimes', 'date'],
            'updated_to' => ['sometimes', 'date', 'after_or_equal:updated_from'],
            'sort_by' => ['sometimes', 'string', Rule::in(['created_at', 'updated_at', 'priority', 'status', 'subject'])],
            'sort_direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $tickets = $this->visibleTickets($request->user())
            ->when(isset($filters['status']), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(isset($filters['priority']), fn (Builder $query) => $query->where('priority', $filters['priority']))
            ->when(isset($filters['team_id']), fn (Builder $query) => $query->where('team_id', $filters['team_id']))
            ->when(isset($filters['project_id']), fn (Builder $query) => $query->where('project_id', $filters['project_id']))
            ->when(isset($filters['assigned_to']), fn (Builder $query) => $query->where('assigned_to', $filters['assigned_to']))
            ->when(isset($filters['unassigned']), fn (Builder $query) => $filters['unassigned']
                ? $query->whereNull('assigned_to')
                : $query->whereNotNull('assigned_to'))
            ->when(isset($filters['created_from']), fn (Builder $query) => $query->whereDate('created_at', '>=', $filters['created_from']))
            ->when(isset($filters['created_to']), fn (Builder $query) => $query->whereDate('created_at', '<=', $filters['created_to']))
            ->when(isset($filters['updated_from']), fn (Builder $query) => $query->whereDate('updated_at', '>=', $filters['updated_from']))
            ->when(isset($filters['updated_to']), fn (Builder $query) => $query->whereDate('updated_at', '<=', $filters['updated_to']))
            ->when(isset($filters['search']), function (Builder $query) use ($filters): void {
                $term = trim($filters['search']);
                $query->where(function (Builder $nested) use ($term): void {
                    $nested->where('subject', 'like', '%'.$term.'%')
                        ->orWhere('description', 'like', '%'.$term.'%');

                    if (ctype_digit($term)) {
                        $nested->orWhereKey((int) $term);
                    }
                });
            })
            ->with(['customer', 'assignedAgent', 'team', 'project'])
            ->orderBy($filters['sort_by'] ?? 'created_at', $filters['sort_direction'] ?? 'desc')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return TicketResource::collection($tickets);
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        $ticket = Ticket::create([
            ...$request->validated(),
            'status' => 'open',
            'user_id' => $request->user()->id,
        ]);

        event(new TicketCreated($ticket));

        return (new TicketResource($ticket->load(['customer', 'assignedAgent', 'team', 'project'])))
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
            'messages' => function (Builder $query) use ($request): void {
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
    ): TicketResource {
        $updatedTicket = $ticketService->update(
            $ticket,
            $request->validated(),
            $request->user()->id,
        );

        return new TicketResource($updatedTicket->load(['customer', 'assignedAgent', 'team', 'project']));
    }

    public function storeMessage(
        StoreTicketMessageRequest $request,
        Ticket $ticket,
    ): JsonResponse {
        $data = $request->validated();
        $isInternal = $request->boolean('is_internal');
        $storedPaths = [];

        try {
            $message = DB::transaction(function () use ($ticket, $request, $data, $isInternal, &$storedPaths): TicketMessage {
                $message = $ticket->messages()->create([
                    'user_id' => $request->user()->id,
                    'body' => trim((string) ($data['body'] ?? '')) ?: 'Shared attachments.',
                    'is_internal' => $isInternal,
                ]);

                foreach ($request->file('attachments', []) as $file) {
                    $path = $file->store("ticket-attachments/{$ticket->id}", 'local');

                    if ($path === false) {
                        throw new \RuntimeException('The attachment could not be stored.');
                    }

                    $storedPaths[] = $path;
                    $originalName = basename(str_replace('\\', '/', $file->getClientOriginalName()));

                    $message->attachments()->create([
                        'ticket_id' => $ticket->id,
                        'user_id' => $request->user()->id,
                        'disk' => 'local',
                        'path' => $path,
                        'original_name' => mb_strcut($originalName, 0, 255, 'UTF-8'),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ]);
                }

                return $message;
            });
        } catch (Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk('local')->delete($storedPaths);
            }

            throw $exception;
        }

        event(new TicketMessageCreated($message));

        $message->load(['user', 'attachments']);

        return (new TicketMessageResource($message))
            ->response()
            ->setStatusCode(201);
    }

    public function downloadAttachment(
        Request $request,
        Ticket $ticket,
        TicketAttachment $attachment,
    ): StreamedResponse {
        Gate::authorize('view', $ticket);
        abort_unless($attachment->ticket_id === $ticket->id, 404);

        if (
            $request->user()->hasRole('customer')
            && $attachment->message()->where('is_internal', true)->exists()
        ) {
            abort(404);
        }

        $disk = Storage::disk($attachment->disk);
        abort_unless($disk->exists($attachment->path), 404);

        return $disk->download(
            $attachment->path,
            basename($attachment->original_name),
            [
                'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    /**
     * @return Builder<Ticket>
     */
    private function visibleTickets(User $user): Builder
    {
        $query = Ticket::query();

        if ($user->can('tickets.view.all')) {
            return $query;
        }

        if ($user->hasRole('customer')) {
            return $query->where('user_id', $user->id);
        }

        if ($user->hasRole('agent')) {
            $teamIds = $user->teams()->pluck('teams.id');
            $projectIds = $user->projects()->pluck('projects.id');

            return $query
                ->where(function (Builder $builder) use ($teamIds): void {
                    $builder->whereNull('team_id')->orWhereIn('team_id', $teamIds);
                })
                ->where(function (Builder $builder) use ($projectIds): void {
                    $builder->whereNull('project_id')->orWhereIn('project_id', $projectIds);
                });
        }

        return $query->whereRaw('1 = 0');
    }
}

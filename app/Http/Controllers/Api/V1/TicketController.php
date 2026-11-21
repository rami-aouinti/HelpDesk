<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\TicketCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TicketController extends Controller
{
    public function index(Request $request): LengthAwarePaginator
    {
        Gate::authorize('viewAny', Ticket::class);

        $tickets = $this->visibleTickets($request->user())
            ->with(['customer', 'assignedAgent', 'team', 'project'])
            ->latest()
            ->paginate(min(max($request->integer('per_page', 15), 1), 100));

        return TicketResource::collection($tickets)->response()->getData(true);
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
            'messages' => function ($query) use ($request): void {
                $query->with('user')->oldest();

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

    /**
     * Apply the same visibility boundaries as TicketPolicy::view().
     *
     * @return \Illuminate\Database\Eloquent\Builder<Ticket>
     */
    private function visibleTickets(User $user)
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
                ->where(function ($builder) use ($teamIds): void {
                    $builder->whereNull('team_id')->orWhereIn('team_id', $teamIds);
                })
                ->where(function ($builder) use ($projectIds): void {
                    $builder->whereNull('project_id')->orWhereIn('project_id', $projectIds);
                });
        }

        return $query->whereRaw('1 = 0');
    }
}

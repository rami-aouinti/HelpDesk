<?php

namespace App\Livewire\Kanban;

use App\Models\Sprint;
use App\Models\Ticket;
use App\Services\SprintManagementService;
use App\Services\TicketService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;

class Board extends Component
{
    private const array STATUSES = [
        'open' => 'Open',
        'in_progress' => 'In progress',
        'waiting_for_customer' => 'Waiting for customer',
        'resolved' => 'Resolved',
        'closed' => 'Done',
    ];

    #[Url(as: 'sprint')]
    public ?int $sprintId = null;

    #[Url]
    public bool $onlyMine = false;

    public function mount(): void
    {
        $user = auth()->user();
        Gate::authorize('viewAny', Ticket::class);
        abort_unless($user->hasRole('admin') || $user->hasRole('agent'), 403);
        abort_unless($user->can('tickets.update'), 403);

        if ($this->sprintId !== null) {
            Sprint::query()->findOrFail($this->sprintId);
        } else {
            $this->sprintId = Sprint::query()
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->value('id');
        }
    }

    public function selectSprint(int $sprintId): void
    {
        Sprint::query()->findOrFail($sprintId);
        $this->sprintId = $sprintId;
    }

    public function updatedOnlyMine(): void
    {
        //
    }

    public function moveTicketStatus(int $ticketId, string $status, TicketService $tickets): void
    {
        abort_unless(array_key_exists($status, self::STATUSES), 422);

        $ticket = Ticket::query()->findOrFail($ticketId);
        abort_unless((int) $ticket->sprint_id === (int) $this->sprintId, 404);
        Gate::authorize('update', $ticket);
        $this->authorizeTicketMove($ticket);

        try {
            $tickets->update($ticket, ['status' => $status], auth()->id());
            $this->resetErrorBag('board');
        } catch (ValidationException $exception) {
            $this->addError('board', $exception->errors()['status'][0] ?? 'This status transition is not allowed.');
        }
    }

    public function addTicketToSprint(int $ticketId, SprintManagementService $sprints): void
    {
        abort_if($this->sprintId === null, 422, 'Select a sprint first.');

        $ticket = Ticket::query()->findOrFail($ticketId);
        abort_unless($ticket->sprint_id === null, 409);
        Gate::authorize('update', $ticket);
        $this->authorizeTicketMove($ticket);

        $destination = Sprint::query()->findOrFail($this->sprintId);

        try {
            $sprints->moveTicket($ticket, $destination, auth()->user());
            $this->resetErrorBag('board');
        } catch (ValidationException $exception) {
            $this->addError('board', $exception->errors()['sprint'][0] ?? 'The ticket could not be added to this sprint.');
        }
    }

    public function moveTicketToSprint(
        int $ticketId,
        int $destinationSprintId,
        SprintManagementService $sprints,
    ): void {
        $ticket = Ticket::query()->findOrFail($ticketId);
        abort_unless((int) $ticket->sprint_id === (int) $this->sprintId, 404);
        Gate::authorize('update', $ticket);
        $this->authorizeTicketMove($ticket);

        $destination = Sprint::query()->findOrFail($destinationSprintId);

        try {
            $sprints->moveTicket($ticket, $destination, auth()->user());
            $this->resetErrorBag('board');
        } catch (ValidationException $exception) {
            $this->addError('board', $exception->errors()['sprint'][0] ?? 'The ticket could not be moved.');
        }
    }

    private function authorizeTicketMove(Ticket $ticket): void
    {
        $user = auth()->user();

        abort_unless(
            $user->hasRole('admin') || (int) $ticket->assigned_to === (int) $user->id,
            403,
            'You can only move tickets assigned to you.',
        );
    }

    public function render(): View
    {
        $user = auth()->user();
        $sprints = Sprint::query()->orderByDesc('start_date')->orderByDesc('id')->get();
        $sprint = $this->sprintId === null ? null : Sprint::query()->find($this->sprintId);
        $tickets = collect();

        if ($sprint !== null) {
            $query = $sprint->tickets()
                ->with(['customer:id,name', 'assignedAgent:id,name', 'team:id,name', 'project:id,name']);

            if (! $user->hasRole('admin')) {
                $teamIds = $user->teams()->pluck('teams.id');
                $projectIds = $user->projects()->pluck('projects.id');

                $query->where(function ($scope) use ($teamIds): void {
                    $scope->whereNull('team_id')->orWhereIn('team_id', $teamIds);
                })->where(function ($scope) use ($projectIds): void {
                    $scope->whereNull('project_id')->orWhereIn('project_id', $projectIds);
                });
            }

            if ($this->onlyMine && ! $user->hasRole('admin')) {
                $query->where('assigned_to', $user->id);
            }

            $tickets = $query->orderBy('created_at')->get();
        }

        $backlogQuery = Ticket::query()
            ->whereNull('sprint_id')
            ->with(['customer:id,name', 'assignedAgent:id,name', 'team:id,name']);

        if (! $user->hasRole('admin')) {
            $teamIds = $user->teams()->pluck('teams.id');
            $projectIds = $user->projects()->pluck('projects.id');

            $backlogQuery->where(function ($scope) use ($teamIds): void {
                $scope->whereNull('team_id')->orWhereIn('team_id', $teamIds);
            })->where(function ($scope) use ($projectIds): void {
                $scope->whereNull('project_id')->orWhereIn('project_id', $projectIds);
            });
        }

        if ($this->onlyMine && ! $user->hasRole('admin')) {
            $backlogQuery->where('assigned_to', $user->id);
        }

        $backlog = $backlogQuery->orderByDesc('created_at')->limit(50)->get();

        $columns = [];
        foreach (self::STATUSES as $status => $label) {
            $columns[] = [
                'key' => $status,
                'label' => $label,
                'tickets' => $tickets->where('status', $status)->values(),
            ];
        }

        return view('livewire.kanban.board', [
            'sprints' => $sprints,
            'sprint' => $sprint,
            'columns' => $columns,
            'totalTickets' => $tickets->count(),
            'doneTickets' => $tickets->where('status', 'closed')->count(),
            'backlog' => $backlog,
        ]);
    }
}

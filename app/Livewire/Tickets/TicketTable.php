<?php

namespace App\Livewire\Tickets;

use App\Models\Team;
use App\Models\Ticket;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TicketTable extends Component
{
    use WithPagination;

    #[Url] public string $search = '';
    #[Url] public string $status = '';
    #[Url] public string $priority = '';
    #[Url] public string $team = '';
    #[Url] public string $sortBy = 'created_at';
    #[Url] public string $sortDirection = 'desc';
    public int $perPage = 10;

    public function mount(): void
    {
        Gate::authorize('viewAny', Ticket::class);
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'status', 'priority', 'team', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function sort(string $column): void
    {
        abort_unless(in_array($column, ['id', 'subject', 'priority', 'status', 'created_at'], true), 400);
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'status', 'priority', 'team']);
        $this->resetPage();
    }

    public function render(): View
    {
        $sortColumn = in_array($this->sortBy, ['id', 'subject', 'priority', 'status', 'created_at'], true) ? $this->sortBy : 'created_at';
        $sortDirection = $this->sortDirection === 'asc' ? 'asc' : 'desc';
        $perPage = min(max($this->perPage, 5), 50);
        $user = auth()->user();
        $isGlobalViewer = $user->can('tickets.view.all');
        $teamIds = $user->teams()->pluck('teams.id');

        $query = Ticket::query()
            ->with(['customer', 'assignedAgent', 'team', 'project'])
            ->when(! $isGlobalViewer && $user->hasRole('customer'), fn ($builder) => $builder->where('user_id', $user->id)->where(function ($scope) use ($user): void { $scope->whereNull('project_id')->orWhereIn('project_id', $user->projects()->select('projects.id')); }))
            ->when(! $isGlobalViewer && $user->hasRole('agent'), function ($builder) use ($teamIds, $user): void {
                $builder->where(function ($scope) use ($teamIds): void {
                    $scope->whereNull('team_id')->orWhereIn('team_id', $teamIds);
                })->where(function ($scope) use ($user): void {
                    $scope->whereNull('project_id')->orWhereIn('project_id', $user->projects()->select('projects.id'));
                });
            })
            ->when(trim($this->search) !== '', function ($builder): void {
                $search = trim($this->search);
                $builder->where(fn ($nested) => $nested->whereRaw('LOWER(subject) LIKE ?', ['%'.mb_strtolower($search).'%'])
                    ->orWhereRaw('LOWER(description) LIKE ?', ['%'.mb_strtolower($search).'%']));
            })
            ->when($this->status !== '', fn ($builder) => $builder->where('status', $this->status))
            ->when($this->priority !== '', fn ($builder) => $builder->where('priority', $this->priority))
            ->when($this->team !== '' && $isGlobalViewer, fn ($builder) => $builder->where('team_id', $this->team))
            ->when($this->team !== '' && ! $isGlobalViewer && $user->hasRole('agent'), fn ($builder) => $builder->where('team_id', $this->team)->whereIn('team_id', $teamIds))
            ->orderBy($sortColumn, $sortDirection);

        $teams = $isGlobalViewer
            ? Team::query()->orderBy('name')->get(['id', 'name'])
            : ($user->hasRole('agent') ? $user->teams()->orderBy('name')->get(['teams.id', 'teams.name']) : collect());

        return view('livewire.tickets.ticket-table', [
            'tickets' => $query->paginate($perPage),
            'teams' => $teams,
        ]);
    }
}

<?php

namespace App\\Livewire\\Tickets;

use App\\Models\\Team;
use App\\Models\\Ticket;
use Illuminate\\Contracts\\View\\View;
use Livewire\\Attributes\\Url;
use Livewire\\Component;
use Livewire\\WithPagination;

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
        $query = Ticket::query()
            ->with(['customer', 'assignedAgent', 'team'])
            ->when(auth()->user()->hasRole('customer'), fn ($builder) => $builder->where('user_id', auth()->id()))
            ->when(trim($this->search) !== '', function ($builder): void {
                $search = trim($this->search);
                $builder->where(fn ($nested) => $nested->where('subject', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%"));
            })
            ->when($this->status !== '', fn ($builder) => $builder->where('status', $this->status))
            ->when($this->priority !== '', fn ($builder) => $builder->where('priority', $this->priority))
            ->when($this->team !== '', fn ($builder) => $builder->where('team_id', $this->team))
            ->orderBy($this->sortBy, $this->sortDirection);

        return view('livewire.tickets.ticket-table', [
            'tickets' => $query->paginate($this->perPage),
            'teams' => Team::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}

<?php

namespace App\\Livewire\\Tickets;

use App\\Models\\Ticket;
use Illuminate\\Contracts\\View\\View;
use Livewire\\Attributes\\Url;
use Livewire\\Component;
use Livewire\\WithPagination;

class TicketTable extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $query = Ticket::query()
            ->with(['customer', 'assignedAgent'])
            ->when(auth()->user()->hasRole('customer'), fn ($builder) => $builder->where('user_id', auth()->id()))
            ->when($this->search !== '', function ($builder): void {
                $builder->where(function ($nested): void {
                    $nested->where('subject', 'ilike', '%'.$this->search.'%')
                        ->orWhere('description', 'ilike', '%'.$this->search.'%');
                });
            })
            ->when($this->status !== '', fn ($builder) => $builder->where('status', $this->status))
            ->latest();

        return view('livewire.tickets.ticket-table', [
            'tickets' => $query->paginate(10),
        ]);
    }
}

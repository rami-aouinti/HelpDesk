<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class TicketSlaAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public string $slaType,
        public string $state,
        public Carbon $dueAt,
    ) {
        $this->onQueue('notifications');
        $this->afterCommit = true;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $label = $this->slaType === 'first_response' ? 'First response' : 'Resolution';
        $verb = $this->state === 'breached' ? 'has been breached' : 'is approaching its deadline';

        return [
            'title' => $label . ' SLA ' . ($this->state === 'breached' ? 'breached' : 'warning'),
            'message' => $label . ' SLA ' . $verb . ' for ticket #' . $this->ticket->id . '.',
            'ticket_id' => $this->ticket->id,
            'ticket_subject' => $this->ticket->subject,
            'url' => route('tickets.show', $this->ticket),
            'sla_type' => $this->slaType,
            'sla_state' => $this->state,
            'due_at' => $this->dueAt->toIso8601String(),
        ];
    }
}

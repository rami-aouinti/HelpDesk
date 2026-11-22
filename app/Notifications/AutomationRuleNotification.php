<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class AutomationRuleNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Ticket $ticket, private readonly string $message)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Automation update',
            'message' => $this->message,
            'ticket_id' => $this->ticket->id,
            'ticket_subject' => $this->ticket->subject,
            'url' => route('tickets.show', $this->ticket),
        ];
    }
}

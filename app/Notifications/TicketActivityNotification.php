<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketActivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, string>  $changes
     */
    public function __construct(
        public Ticket $ticket,
        public string $subject,
        public string $message,
        public array $changes = [],
    ) {
        $this->onQueue('notifications');
        $this->afterCommit = true;
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ($notifiable->email_notifications_enabled ?? true)
            ? ['database', 'mail']
            : ['database'];
    }

    /**
     * Store a concise, safe in-app notification payload.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->subject,
            'message' => $this->message,
            'ticket_id' => $this->ticket->id,
            'ticket_subject' => $this->ticket->subject,
            'url' => route('tickets.show', $this->ticket),
            'changes' => $this->changes,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage())
            ->subject($this->subject)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->message)
            ->line('Ticket #'.$this->ticket->id.': '.$this->ticket->subject);

        foreach ($this->changes as $label => $value) {
            $mail->line($label.': '.$value);
        }

        return $mail
            ->action('View ticket', route('tickets.show', $this->ticket))
            ->line('You received this email because of an update in HelpDesk.');
    }
}

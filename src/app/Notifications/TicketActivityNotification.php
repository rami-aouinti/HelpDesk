<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketActivityNotification extends Notification implements \Illuminate\Contracts\Queue\ShouldQueue
{
    use Queueable;

    public bool $afterCommit = true;

    /**
     * @param array<string, string> $changes
     */
    public function __construct(
        public Ticket $ticket,
        public string $subject,
        public string $message,
        public array $changes = [],
    ) {
        $this->onQueue('notifications');
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
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

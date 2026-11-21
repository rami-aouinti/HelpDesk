<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Services\TicketNotificationService;

class SendTicketCreatedNotifications
{
    public function __construct(private readonly TicketNotificationService $notifications)
    {
    }

    public function handle(TicketCreated $event): void
    {
        $this->notifications->ticketCreated($event->ticket);
    }
}

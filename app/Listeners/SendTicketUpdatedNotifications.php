<?php

namespace App\Listeners;

use App\Events\TicketUpdated;
use App\Services\TicketNotificationService;

class SendTicketUpdatedNotifications
{
    public function __construct(private readonly TicketNotificationService $notifications) {}

    public function handle(TicketUpdated $event): void
    {
        $this->notifications->ticketUpdated(
            $event->ticket,
            $event->actor,
            $event->changes,
        );
    }
}

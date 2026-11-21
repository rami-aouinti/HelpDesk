<?php

namespace App\Listeners;

use App\Events\TicketMessageCreated;
use App\Services\TicketNotificationService;

class SendTicketMessageNotifications
{
    public function __construct(private readonly TicketNotificationService $notifications) {}

    public function handle(TicketMessageCreated $event): void
    {
        $this->notifications->messageCreated($event->message);
    }
}

<?php

namespace App\Events;

use App\Models\TicketMessage;

class TicketMessageCreated
{
    public function __construct(public readonly TicketMessage $message) {}
}

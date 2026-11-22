<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Commands\CreateTicket;

use App\ServiceDesk\Domain\Enums\TicketPriority;

final readonly class CreateTicketCommand
{
    public function __construct(
        public string $subject,
        public string $description,
        public TicketPriority $priority,
        public ?int $projectId,
        public int $customerId,
    ) {
    }
}

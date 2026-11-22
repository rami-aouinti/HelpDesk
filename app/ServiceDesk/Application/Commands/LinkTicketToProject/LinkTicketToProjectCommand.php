<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Commands\LinkTicketToProject;

final readonly class LinkTicketToProjectCommand
{
    public function __construct(
        public int $ticketId,
        public int $actorId,
        public ?int $projectId,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\DTO;

use DateTimeImmutable;

final readonly class TicketSlaSnapshot
{
    public function __construct(
        public int $ticketId,
        public string $priority,
        public string $status,
        public DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $firstResponseAt,
        public ?DateTimeImmutable $firstPublicStaffReplyAt,
        public ?DateTimeImmutable $firstResponseDueAt,
        public ?DateTimeImmutable $firstResponseWarningSentAt,
        public ?DateTimeImmutable $firstResponseBreachedAt,
        public ?DateTimeImmutable $resolutionDueAt,
        public ?DateTimeImmutable $resolutionWarningSentAt,
        public ?DateTimeImmutable $resolutionBreachedAt,
    ) {
    }
}

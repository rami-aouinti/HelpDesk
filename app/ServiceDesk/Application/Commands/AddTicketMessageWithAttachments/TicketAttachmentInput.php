<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Commands\AddTicketMessageWithAttachments;

final readonly class TicketAttachmentInput
{
    public function __construct(
        public string $temporaryPath,
        public string $originalName,
        public ?string $mimeType,
        public ?int $size,
    ) {
    }
}

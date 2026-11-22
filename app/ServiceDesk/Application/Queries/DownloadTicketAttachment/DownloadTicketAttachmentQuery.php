<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Queries\DownloadTicketAttachment;

final readonly class DownloadTicketAttachmentQuery
{
    public function __construct(
        public string $disk,
        public string $path,
        public string $originalName,
        public ?string $mimeType,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Queries\DownloadTicketAttachment;

final readonly class TicketAttachmentDownload
{
    /** @param resource $stream */
    public function __construct(
        public mixed $stream,
        public string $filename,
        public string $mimeType,
    ) {
    }
}

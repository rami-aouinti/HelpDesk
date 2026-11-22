<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Queries\DownloadTicketAttachment;

use App\ServiceDesk\Application\Contracts\TicketAttachmentStorage;

final class DownloadTicketAttachmentHandler
{
    public function __construct(private readonly TicketAttachmentStorage $storage)
    {
    }

    public function handle(DownloadTicketAttachmentQuery $query): ?TicketAttachmentDownload
    {
        if (! $this->storage->exists($query->disk, $query->path)) {
            return null;
        }

        $stream = $this->storage->readStream($query->disk, $query->path);

        if (! is_resource($stream)) {
            return null;
        }

        return new TicketAttachmentDownload(
            stream: $stream,
            filename: basename($query->originalName),
            mimeType: $query->mimeType ?: 'application/octet-stream',
        );
    }
}

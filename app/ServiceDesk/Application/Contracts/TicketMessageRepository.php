<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Contracts;

interface TicketMessageRepository
{
    public function create(int $ticketId, int $authorId, string $body, bool $isInternal): int;

    /**
     * @param list<array{disk: string, path: string, original_name: string, mime_type: ?string, size: ?int}> $attachments
     */
    public function createWithAttachments(
        int $ticketId,
        int $authorId,
        string $body,
        bool $isInternal,
        array $attachments,
    ): int;
}

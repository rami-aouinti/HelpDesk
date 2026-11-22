<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Commands\AddTicketMessageWithAttachments;

final readonly class AddTicketMessageWithAttachmentsCommand
{
    /**
     * @param list<TicketAttachmentInput> $attachments
     */
    public function __construct(
        public int $ticketId,
        public int $authorId,
        public string $body,
        public bool $isInternal,
        public array $attachments,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\ServiceDesk\Infrastructure\Persistence\Eloquent;

use App\Models\TicketMessage;
use App\ServiceDesk\Application\Contracts\TicketMessageRepository;

final class EloquentTicketMessageRepository implements TicketMessageRepository
{
    public function create(int $ticketId, int $authorId, string $body, bool $isInternal): int
    {
        return $this->createWithAttachments($ticketId, $authorId, $body, $isInternal, []);
    }

    public function createWithAttachments(
        int $ticketId,
        int $authorId,
        string $body,
        bool $isInternal,
        array $attachments,
    ): int {
        $message = TicketMessage::query()->create([
            'ticket_id' => $ticketId,
            'user_id' => $authorId,
            'body' => $body !== '' ? $body : 'Shared attachments.',
            'is_internal' => $isInternal,
        ]);

        foreach ($attachments as $attachment) {
            $message->attachments()->create([
                'ticket_id' => $ticketId,
                'user_id' => $authorId,
                ...$attachment,
            ]);
        }

        return (int) $message->getKey();
    }
}

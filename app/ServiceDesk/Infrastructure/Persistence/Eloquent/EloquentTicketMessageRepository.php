<?php

declare(strict_types=1);

namespace App\ServiceDesk\Infrastructure\Persistence\Eloquent;

use App\Models\TicketMessage;
use App\ServiceDesk\Application\Contracts\TicketMessageRepository;

final class EloquentTicketMessageRepository implements TicketMessageRepository
{
    public function create(int $ticketId, int $authorId, string $body, bool $isInternal): TicketMessage
    {
        return TicketMessage::query()->create([
            'ticket_id' => $ticketId,
            'user_id' => $authorId,
            'body' => $body !== '' ? $body : 'Shared attachments.',
            'is_internal' => $isInternal,
        ]);
    }
}

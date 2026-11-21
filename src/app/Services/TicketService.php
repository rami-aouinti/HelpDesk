<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketHistory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * @package App\Services
 * @author Rami Aouinti
 */
class TicketService
{
    private const array STATUSES = [
        'open',
        'in_progress',
        'waiting_for_customer',
        'resolved',
        'closed',
    ];

    private const array PRIORITIES = [
        'low',
        'normal',
        'high',
        'urgent',
    ];

    /**
     * @throws Throwable
     */
    public function update(Ticket $ticket, array $data, ?int $userId = null): Ticket
    {
        return DB::transaction(function () use ($ticket, $data, $userId) {
            $ticket = Ticket::query()
                ->lockForUpdate()
                ->findOrFail($ticket->id);

            foreach ($data as $field => $newValue) {
                $allowedValues = match ($field) {
                    'status' => self::STATUSES,
                    'priority' => self::PRIORITIES,
                    default => throw new InvalidArgumentException(
                        "Unsupported ticket field: {$field}"
                    ),
                };

                if (! in_array($newValue, $allowedValues, true)) {
                    throw new InvalidArgumentException(
                        "Invalid value for {$field}."
                    );
                }

                $oldValue = $ticket->{$field};

                if ($oldValue === $newValue) {
                    continue;
                }

                $ticket->{$field} = $newValue;
                $ticket->save();

                TicketHistory::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $userId,
                    'action' => $field,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                ]);
            }

            return $ticket->refresh();
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $ticket_id
 * @property int $user_id
 * @property string $body
 * @property bool $is_internal
 * @property-read Ticket $ticket
 * @property-read User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TicketAttachment> $attachments
 */
class TicketMessage extends Model
{
    protected $fillable = ['ticket_id', 'user_id', 'body', 'is_internal'];

    protected $casts = ['is_internal' => 'boolean'];

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<TicketAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class, 'ticket_message_id');
    }
}

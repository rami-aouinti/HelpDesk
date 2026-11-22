<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Support\\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $team_id
 * @property int|null $ticket_id
 * @property string $title
 * @property string|null $description
 * @property string $type
 * @property string $visibility
 * @property string|null $location
 * @property Carbon $starts_at
 * @property Carbon|null $ends_at
 * @property bool $all_day
 * @property-read User $creator
 */
class CalendarEvent extends Model
{
    protected $fillable = [
        'user_id', 'team_id', 'ticket_id', 'title', 'description', 'type',
        'visibility', 'location', 'starts_at', 'ends_at', 'all_day',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'all_day' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function isEditableBy(User $user): bool
    {
        return $user->id === $this->user_id || $user->hasRole('admin');
    }
}

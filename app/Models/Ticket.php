<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @author Rami Aouinti
 */
class Ticket extends Model
{
    protected $fillable = [
        'subject',
        'description',
        'status',
        'priority',
        'user_id',
        'assigned_to',
        'team_id',
        'project_id',
        'sprint_id',
        'estimated_minutes',
        'elapsed_minutes',
    ];

    protected function casts(): array
    {
        return [
            'estimated_minutes' => 'integer',
            'elapsed_minutes' => 'integer',
        ];
    }

    public function getRemainingMinutesAttribute(): int
    {
        return max(0, (int) $this->estimated_minutes - (int) $this->elapsed_minutes);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TicketHistory::class)
            ->latest();
    }
}

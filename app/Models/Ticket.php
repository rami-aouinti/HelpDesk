<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $assigned_to
 * @property int|null $team_id
 * @property int|null $project_id
 * @property int|null $sprint_id
 * @property string $subject
 * @property string $description
 * @property string $status
 * @property string $priority
 * @property int|null $estimated_minutes
 * @property int|null $elapsed_minutes
 * @property-read User|null $customer
 * @property-read User|null $assignedAgent
 * @property-read Team|null $team
 * @property-read Project|null $project
 * @property-read Sprint|null $sprint
 * @property-read Collection<int, TicketMessage> $messages
 * @property-read Collection<int, TicketHistory> $histories
 *
 * @author Rami Aouinti
 */
class Ticket extends Model
{
    protected $fillable = [
        'subject', 'description', 'status', 'priority', 'user_id', 'assigned_to',
        'team_id', 'project_id', 'sprint_id', 'estimated_minutes', 'elapsed_minutes',
    ];

    protected function casts(): array
    {
        return [
            'estimated_minutes' => 'integer',
            'elapsed_minutes' => 'integer',
            'first_response_due_at' => 'datetime',
            'first_response_at' => 'datetime',
            'first_response_warning_sent_at' => 'datetime',
            'first_response_breached_at' => 'datetime',
            'resolution_due_at' => 'datetime',
            'resolution_warning_sent_at' => 'datetime',
            'resolution_breached_at' => 'datetime',
        ];
    }

    public function getRemainingMinutesAttribute(): int
    {
        return max(0, (int) $this->estimated_minutes - (int) $this->elapsed_minutes);
    }

    /** @return BelongsTo<User, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<Sprint, $this> */
    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    /** @return HasMany<TicketMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function satisfactionSurvey(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(TicketSatisfactionSurvey::class);
    }

    /** @return HasMany<TicketHistory, $this> */
    public function histories(): HasMany
    {
        return $this->hasMany(TicketHistory::class)->latest();
    }
}

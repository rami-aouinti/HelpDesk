<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $automation_rule_id
 * @property int|null $ticket_id
 * @property string $trigger
 * @property string $status
 * @property array<string, mixed>|null $context
 * @property string|null $error
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property-read AutomationRule|null $rule
 * @property-read Ticket|null $ticket
 */
class AutomationExecution extends Model
{
    protected $fillable = [
        'automation_rule_id',
        'ticket_id',
        'trigger',
        'status',
        'context',
        'error',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<AutomationRule, $this> */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'automation_rule_id');
    }

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}

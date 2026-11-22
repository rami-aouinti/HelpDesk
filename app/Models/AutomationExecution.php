<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationExecution extends Model
{
    protected $fillable = ['automation_rule_id', 'ticket_id', 'trigger', 'status', 'context', 'error', 'started_at', 'finished_at'];

    protected function casts(): array
    {
        return ['context' => 'array', 'started_at' => 'datetime', 'finished_at' => 'datetime'];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'automation_rule_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationRule extends Model
{
    protected $fillable = ['name', 'description', 'trigger', 'conditions', 'actions', 'is_active', 'created_by'];

    protected function casts(): array
    {
        return ['conditions' => 'array', 'actions' => 'array', 'is_active' => 'boolean'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(AutomationExecution::class);
    }
}

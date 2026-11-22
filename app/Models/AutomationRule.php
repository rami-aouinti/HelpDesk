<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $trigger
 * @property array<string, mixed> $conditions
 * @property list<array<string, mixed>> $actions
 * @property bool $is_active
 * @property int|null $created_by
 * @property-read User|null $creator
 * @property-read Collection<int, AutomationExecution> $executions
 */
class AutomationRule extends Model
{
    protected $fillable = [
        'name',
        'description',
        'trigger',
        'conditions',
        'actions',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'actions' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<AutomationExecution, $this> */
    public function executions(): HasMany
    {
        return $this->hasMany(AutomationExecution::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sprint extends Model
{
    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function getIsCompletedAttribute(): bool
    {
        if (
            array_key_exists('tickets_count', $this->attributes)
            && array_key_exists('unfinished_tickets_count', $this->attributes)
        ) {
            return (int) $this->tickets_count > 0 && (int) $this->unfinished_tickets_count === 0;
        }

        if ($this->relationLoaded('tickets')) {
            return $this->tickets->isNotEmpty()
                && $this->tickets->every(fn (Ticket $ticket): bool => $ticket->status === 'closed');
        }

        return $this->tickets()->exists()
            && ! $this->tickets()->where('status', '!=', 'closed')->exists();
    }

    public function getProgressAttribute(): int
    {
        $total = (int) ($this->tickets_count ?? $this->tickets()->count());

        if ($total === 0) {
            return 0;
        }

        $done = (int) ($this->done_tickets_count
            ?? $this->tickets()->where('status', 'closed')->count());

        return (int) round(($done / $total) * 100);
    }
}

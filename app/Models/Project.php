<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUSES = ['planned', 'active', 'on_hold', 'completed', 'cancelled'];

    protected $fillable = [
        'name', 'description', 'budget', 'start_date', 'end_date',
        'status', 'cover_image_path', 'created_by',
    ];

    protected function casts(): array
    {
        return ['budget' => 'decimal:2', 'start_date' => 'date', 'end_date' => 'date'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class)->latest();
    }
}

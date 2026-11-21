<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $budget
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property string $status
 * @property string|null $cover_image_path
 * @property int|null $created_by
 * @property-read User|null $creator
 * @property-read Collection<int, User> $members
 * @property-read Collection<int, Ticket> $tickets
 * @property-read Collection<int, ProjectDocument> $documents
 */
class Project extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUSES = ['planned', 'active', 'on_hold', 'completed', 'cancelled'];

    protected $fillable = ['name', 'description', 'budget', 'start_date', 'end_date', 'status', 'cover_image_path', 'created_by'];

    protected function casts(): array
    {
        return ['budget' => 'decimal:2', 'start_date' => 'date', 'end_date' => 'date'];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsToMany<User, $this> */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /** @return HasMany<ProjectDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class)->latest();
    }
}

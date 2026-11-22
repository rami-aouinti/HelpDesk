<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $published_at
 * @property Carbon|null $archived_at
 */
class KnowledgeBaseArticle extends Model
{
    protected $fillable = [
        'category_id', 'author_id', 'title', 'slug', 'excerpt', 'content',
        'cover_image_path', 'status', 'view_count', 'published_at', 'archived_at',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'archived_at' => 'datetime', 'view_count' => 'integer'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @param Builder<KnowledgeBaseArticle> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    /** @return BelongsTo<KnowledgeBaseCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseCategory::class, 'category_id');
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return BelongsToMany<KnowledgeBaseTag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeBaseTag::class, 'knowledge_base_article_tag', 'article_id', 'tag_id');
    }

    /** @return HasMany<KnowledgeBaseAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(KnowledgeBaseAttachment::class, 'article_id');
    }
}

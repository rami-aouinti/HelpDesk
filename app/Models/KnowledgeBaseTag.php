<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class KnowledgeBaseTag extends Model
{
    protected $fillable = ['name', 'slug'];

    /** @return BelongsToMany<KnowledgeBaseArticle, $this> */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeBaseArticle::class, 'knowledge_base_article_tag', 'tag_id', 'article_id');
    }
}

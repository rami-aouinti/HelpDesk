<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeBaseCategory extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'color', 'sort_order'];

    /** @return HasMany<KnowledgeBaseArticle, $this> */
    public function articles(): HasMany
    {
        return $this->hasMany(KnowledgeBaseArticle::class, 'category_id');
    }
}

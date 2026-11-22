<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeBaseAttachment extends Model
{
    protected $fillable = ['article_id', 'disk', 'path', 'original_name', 'mime_type', 'size'];

    /** @return BelongsTo<KnowledgeBaseArticle, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseArticle::class, 'article_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiGenerationEvent extends Model
{
    protected $fillable = [
        'article_id',
        'message',
        'percent',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}

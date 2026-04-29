<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    protected $casts = [
        'scheduled_for' => 'datetime',
        'generated_at' => 'datetime',
        'pushed_at' => 'datetime',
        'faq_json' => 'array',
        'outline_json' => 'array',
        'tag_suggestions_json' => 'array',
        'category_suggestions_json' => 'array',
        'status' => ArticleStatus::class,
    ];

    protected $fillable = [
        'wp_post_id',
        'wp_category_id',
        'title',
        'slug',
        'focus_keyword',
        'meta_title',
        'meta_description',
        'excerpt',
        'content_html',
        'faq_json',
        'outline_json',
        'tag_suggestions_json',
        'category_suggestions_json',
        'status',
        'scheduled_for',
        'generated_at',
        'pushed_at',
        'error_message',
        'ai_provider',
        'ai_model',
        'ai_prompt_version',
    ];

    public function generationEvents(): HasMany
    {
        return $this->hasMany(AiGenerationEvent::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleTopic extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic_title',
        'focus_keyword',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];
}

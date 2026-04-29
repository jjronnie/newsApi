<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiJobLog extends Model
{
    protected $fillable = [
        'job_name',
        'status',
        'started_at',
        'finished_at',
        'duration_ms',
        'error_message',
        'meta_json',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'meta_json' => 'array',
    ];
}

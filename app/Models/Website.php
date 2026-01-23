<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $url
 * @property \Illuminate\Support\Carbon|null $last_updated
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Website extends Model
{
    protected $fillable = ['name', 'url', 'last_updated'];

    protected $casts = [
        'last_updated' => 'datetime',
    ];
}

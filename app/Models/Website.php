<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $url
 * @property Carbon|null $last_updated
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Website extends Model
{
    protected $fillable = ['name', 'url', 'last_updated'];

    protected $casts = [
        'last_updated' => 'datetime',
    ];
}

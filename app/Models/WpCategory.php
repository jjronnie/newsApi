<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WpCategory extends Model
{
    protected $fillable = [
        'wp_id',
        'name',
        'slug',
        'parent_wp_id',
    ];
}

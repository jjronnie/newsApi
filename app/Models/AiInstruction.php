<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiInstruction extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'content',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function getActiveByType(string $type): ?self
    {
        return self::where('type', $type)
            ->where('is_active', true)
            ->first();
    }

    public static function getContentByName(string $name): string
    {
        $instruction = self::where('name', $name)
            ->where('is_active', true)
            ->first();

        return $instruction?->content ?? '';
    }
}

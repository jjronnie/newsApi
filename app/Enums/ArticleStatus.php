<?php

namespace App\Enums;

enum ArticleStatus: string
{
    case PENDING = 'pending';
    case GENERATING = 'generating';
    case GENERATED = 'generated';
    case PUSHING = 'pushing';
    case PUSHED = 'pushed';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::GENERATING => 'Generating',
            self::GENERATED => 'Generated',
            self::PUSHING => 'Pushing',
            self::PUSHED => 'Pushed',
            self::FAILED => 'Failed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::GENERATING => 'blue',
            self::GENERATED => 'green',
            self::PUSHING => 'blue',
            self::PUSHED => 'emerald',
            self::FAILED => 'red',
        };
    }
}

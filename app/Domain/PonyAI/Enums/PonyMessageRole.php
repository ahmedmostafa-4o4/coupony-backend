<?php

namespace App\Domain\PonyAI\Enums;

enum PonyMessageRole: string
{
    case USER = 'user';
    case ASSISTANT = 'assistant';
    case SYSTEM = 'system';
    case TOOL = 'tool';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

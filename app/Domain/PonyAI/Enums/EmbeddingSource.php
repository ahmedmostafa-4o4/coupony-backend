<?php

namespace App\Domain\PonyAI\Enums;

enum EmbeddingSource: string
{
    case TITLE = 'title';
    case DESCRIPTION = 'description';
    case COMBINED = 'combined';
    case IMAGE_CAPTION = 'image_caption';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

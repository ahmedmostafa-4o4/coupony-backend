<?php

namespace App\Domain\PonyAI\Models;

use Illuminate\Database\Eloquent\Model;

class AiMessageUsage extends Model
{
    protected $fillable = [
        'usage_date',
        'subject_type',
        'subject_id',
        'used',
        'reservation_tokens',
    ];

    protected function casts(): array
    {
        return [
            'usage_date' => 'immutable_date',
            'used' => 'integer',
            'reservation_tokens' => 'array',
        ];
    }
}

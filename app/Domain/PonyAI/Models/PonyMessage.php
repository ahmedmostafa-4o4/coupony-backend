<?php

namespace App\Domain\PonyAI\Models;

use App\Domain\PonyAI\Enums\PonyMessageRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PonyMessage extends Model
{
    protected $table = 'pony_messages';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'conversation_id',
        'role',
        'content',
        'attachments',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'role' => PonyMessageRole::class,
            'attachments' => 'array',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PonyMessage $message): void {
            if (blank($message->id)) {
                $message->id = (string) Str::uuid();
            }
        });
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(PonyConversation::class, 'conversation_id');
    }
}

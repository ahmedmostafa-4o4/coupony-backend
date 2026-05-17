<?php

namespace App\Domain\PonyAI\Models;

use App\Domain\PonyAI\Enums\AssistantPersona;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PonyConversation extends Model
{
    use SoftDeletes;

    protected $table = 'pony_conversations';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'persona',
        'store_id',
        'title',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'persona' => AssistantPersona::class,
            'last_message_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PonyConversation $conversation): void {
            if (blank($conversation->id)) {
                $conversation->id = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(PonyMessage::class, 'conversation_id')
            ->orderBy('created_at')
            ->orderBy('id');
    }
}

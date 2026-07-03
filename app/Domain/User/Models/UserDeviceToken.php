<?php

namespace App\Domain\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDeviceToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'token_hash',
        'platform',
        'device_id',
        'app_version',
        'last_used_at',
        'revoked_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\UserDeviceTokenFactory::new();
    }

    protected static function booted(): void
    {
        static::saving(function (UserDeviceToken $deviceToken): void {
            if ($deviceToken->isDirty('token')) {
                $deviceToken->token_hash = self::hashToken($deviceToken->token);
            }
        });
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at');
    }

    public function revoke(): bool
    {
        return $this->update(['revoked_at' => now()]);
    }
}

<?php

// ============================================================================
// 1. OTP MODEL: Domain/User/Models/Otp.php
// ============================================================================

namespace App\Domain\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Otp extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\OtpFactory::new();
    }

    protected $table = 'otps';

    protected $fillable = [
        'user_id',
        'phone_or_email',
        'otp_hash',
        'purpose',
        'channel',
        'status',
        'attempts',
        'max_attempts',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'max_attempts' => 'integer',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    protected $hidden = [
        'otp_hash',
    ];

    // ========================================================================
    // RELATIONSHIPS
    // ========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ========================================================================
    // HELPER METHODS
    // ========================================================================

    /**
     * Check if OTP is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if OTP is valid for use.
     */
    public function isValid(): bool
    {
        return $this->status === 'pending' &&
            ! $this->isExpired() &&
            $this->attempts < $this->max_attempts;
    }

    /**
     * Check if OTP is blocked.
     */
    public function isBlocked(): bool
    {
        return $this->status === 'blocked' ||
            $this->attempts >= $this->max_attempts;
    }

    /**
     * Mark OTP as verified.
     */
    public function markAsVerified(): bool
    {
        return $this->update([
            'status' => 'verified',
            'used_at' => now(),
        ]);
    }

    /**
     * Mark OTP as expired.
     */
    public function markAsExpired(): bool
    {
        return $this->update(['status' => 'expired']);
    }

    /**
     * Increment failed attempts.
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');

        if ($this->attempts >= $this->max_attempts) {
            $this->update(['status' => 'blocked']);
        }
    }

    /**
     * Verify OTP code.
     */
    public function verify(string $code): bool
    {
        if (! $this->isValid()) {
            return false;
        }

        $isCorrect = hash_equals(
            $this->otp_hash,
            hash('sha256', $code)
        );

        if ($isCorrect) {
            $this->markAsVerified();

            return true;
        }

        $this->incrementAttempts();

        return false;
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '>', now());
    }

    public function scopeForPurpose($query, string $purpose)
    {
        return $query->where('purpose', $purpose);
    }

    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }
}

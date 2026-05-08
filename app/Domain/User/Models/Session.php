<?php

namespace App\Domain\User\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $table = 'sessions';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'user_id',
        'token',
        'refresh_token',
        'ip_address',
        'user_agent',
        'last_activity',
        'expires_at',
        'device_type',
        'payload',
        'verified_at',
        'created_at',
        'expired_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'expired_at' => 'datetime',
        'last_activity' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($session) {
            // Generate UUID if not set
            if (empty($session->id)) {
                $session->id = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if session is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if session is active.
     */
    public function isActive(): bool
    {
        return !$this->isExpired();
    }
}

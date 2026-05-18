<?php

namespace App\Domain\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRoles extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'store_id',
        'role_id',
        'granted_at',
        'granted_by_user_id',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'granted_at' => 'datetime',
    ];

    /**
     * Get the user that owns the profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

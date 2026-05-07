<?php

namespace App\Domain\Store\Models;

use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreFollowers extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'store_id',
        'user_id',
        'notification_enabled',
        'followed_at',
    ];

    protected $casts = [
        'followed_at' => 'datetime',
        'notification_enabled' => 'boolean',
    ];


    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
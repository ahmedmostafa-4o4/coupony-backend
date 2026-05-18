<?php

namespace App\Domain\Points\Models;

use App\Domain\Product\Models\OfferClaim;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorePointTransaction extends Model
{
    protected $table = 'store_point_transactions';

    protected $fillable = [
        'store_id',
        'admin_user_id',
        'user_id',
        'offer_claim_id',
        'type',
        'points',
        'balance_before',
        'balance_after',
        'reason',
        'note',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'balance_before' => 'integer',
            'balance_after' => 'integer',
            'meta' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function offerClaim(): BelongsTo
    {
        return $this->belongsTo(OfferClaim::class, 'offer_claim_id');
    }
}

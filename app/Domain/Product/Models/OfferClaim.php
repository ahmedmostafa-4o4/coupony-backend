<?php

namespace App\Domain\Product\Models;

use App\Domain\Product\Enums\OfferClaimStatus;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfferClaim extends Model
{
    use HasFactory;

    protected $table = 'offer_claims';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'store_id',
        'product_id',
        'offer_id',
        'status',
        'cancellation_reason',
        'claim_token',
        'qr_code_token',
        'offer_snapshot',
        'expires_at',
        'redeemed_at',
        'redeemed_by',
        'revenue_amount',
        'revenue_currency',
    ];

    protected function casts(): array
    {
        return [
            'status' => OfferClaimStatus::class,
            'offer_snapshot' => 'array',
            'expires_at' => 'datetime',
            'redeemed_at' => 'datetime',
            'revenue_amount' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (OfferClaim $claim) {
            if (blank($claim->id)) {
                $claim->id = (string) \Illuminate\Support\Str::uuid();
            }

            $claim->status ??= OfferClaimStatus::ACTIVE;
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function offer()
    {
        return $this->belongsTo(ProductOffer::class, 'offer_id');
    }

    public function redeemedBy()
    {
        return $this->belongsTo(User::class, 'redeemed_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function scopeWithRedeemedUsageCount(Builder $query): Builder
    {
        return $query->addSelect([
            'usage_count' => self::query()
                ->from('offer_claims as redeemed_claims')
                ->selectRaw('COUNT(*)')
                ->whereColumn('redeemed_claims.offer_id', 'offer_claims.offer_id')
                ->where('redeemed_claims.status', OfferClaimStatus::REDEEMED->value),
        ]);
    }
}

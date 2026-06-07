<?php

namespace App\Domain\Banner\Models;

use App\Domain\Banner\Enums\BannerClaimStatus;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BannerClaim extends Model
{
    use HasFactory;

    protected $table = 'banner_claims';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'banner_id',
        'user_id',
        'store_id',
        'status',
        'cancellation_reason',
        'claim_token',
        'qr_code_token',
        'claim_snapshot',
        'expires_at',
        'redeemed_at',
        'redeemed_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => BannerClaimStatus::class,
            'claim_snapshot' => 'array',
            'expires_at' => 'datetime',
            'redeemed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (BannerClaim $claim) {
            if (blank($claim->id)) {
                $claim->id = (string) Str::uuid();
            }

            $claim->status ??= BannerClaimStatus::ACTIVE;
        });
    }

    public function banner()
    {
        return $this->belongsTo(Banner::class, 'banner_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function redeemedBy()
    {
        return $this->belongsTo(User::class, 'redeemed_by');
    }
}

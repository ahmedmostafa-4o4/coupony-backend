<?php

namespace Database\Factories\Domain\Banner\Models;

use App\Domain\Banner\Enums\BannerClaimStatus;
use App\Domain\Banner\Models\BannerClaim;
use App\Domain\Banner\Models\Banner;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BannerClaimFactory extends Factory
{
    protected $model = BannerClaim::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'user_id' => User::factory(),
            'store_id' => Store::factory(),
            'banner_id' => Banner::factory(),
            'status' => BannerClaimStatus::ACTIVE,
            'claim_token' => Str::random(10),
            'qr_code_token' => Str::random(10),
            'claim_snapshot' => [],
            'expires_at' => now()->addDays(7),
        ];
    }
}

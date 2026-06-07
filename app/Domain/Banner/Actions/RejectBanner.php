<?php

namespace App\Domain\Banner\Actions;

use App\Domain\Banner\Enums\BannerStatus;
use App\Domain\Banner\Events\BannerRejected;
use App\Domain\Banner\Models\Banner;
use App\Domain\User\Models\User;

class RejectBanner
{
    public function execute(Banner $banner, User $rejectedBy, string $reason): Banner
    {
        $banner->update([
            'status' => BannerStatus::REJECTED,
            'rejection_reason' => $reason,
            'is_active' => false,
        ]);

        event(new BannerRejected($banner, $reason));

        return $banner->fresh();
    }
}

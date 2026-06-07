<?php

namespace App\Domain\Banner\Actions;

use App\Domain\Banner\Enums\BannerStatus;
use App\Domain\Banner\Events\BannerApproved;
use App\Domain\Banner\Models\Banner;
use App\Domain\User\Models\User;

class ApproveBanner
{
    public function execute(Banner $banner, User $approvedBy): Banner
    {
        if ($banner->status === BannerStatus::APPROVED) {
            return $banner;
        }

        $banner->update([
            'status' => BannerStatus::APPROVED,
            'approved_by' => $approvedBy->id,
            'approved_at' => now(),
            'rejection_reason' => null,
            'is_active' => true, // Auto-activate upon approval
        ]);

        event(new BannerApproved($banner));

        return $banner->fresh();
    }
}

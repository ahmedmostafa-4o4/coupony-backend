<?php

namespace App\Domain\Banner\Actions;

use App\Domain\Banner\Models\Banner;

class UpdateBanner
{
    public function execute(Banner $banner, array $data): Banner
    {
        $banner->update($data);

        return $banner->fresh();
    }
}

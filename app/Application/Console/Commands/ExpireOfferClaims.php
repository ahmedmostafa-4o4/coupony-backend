<?php

namespace App\Application\Console\Commands;

use App\Domain\Product\Enums\OfferClaimStatus;
use App\Domain\Product\Models\OfferClaim;
use Illuminate\Console\Command;

class ExpireOfferClaims extends Command
{
    protected $signature = 'offer-claims:expire';

    protected $description = 'Mark overdue active offer claims as expired';

    public function handle(): int
    {
        $count = OfferClaim::query()
            ->where('status', OfferClaimStatus::ACTIVE)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => OfferClaimStatus::EXPIRED]);

        $label = $count === 1 ? 'offer claim' : 'offer claims';
        $this->info("Expired {$count} {$label}.");

        return self::SUCCESS;
    }
}

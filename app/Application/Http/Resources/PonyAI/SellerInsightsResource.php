<?php

namespace App\Application\Http\Resources\PonyAI;

use App\Domain\PonyAI\DTOs\StoreInsightsSnapshot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerInsightsResource extends JsonResource
{
    public function __construct(StoreInsightsSnapshot $snapshot)
    {
        parent::__construct($snapshot);
    }

    public function toArray(Request $request): array
    {
        /** @var StoreInsightsSnapshot $snapshot */
        $snapshot = $this->resource;

        return [
            'store_id' => $snapshot->storeId,
            'totals' => [
                'active_products' => $snapshot->activeProductCount,
                'pending_products' => $snapshot->pendingProductCount,
                'views' => $snapshot->totalViews,
                'likes' => $snapshot->totalLikes,
                'favorites' => $snapshot->totalFavorites,
                'claims' => $snapshot->totalClaims,
                'redemptions' => $snapshot->totalRedemptions,
            ],
            'top_products' => $snapshot->topProducts,
            'underperforming_products' => $snapshot->underperformingProducts,
            'inventory_warnings' => $snapshot->inventoryWarnings,
        ];
    }
}

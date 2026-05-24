<?php

namespace App\Application\Http\Resources;

use App\Domain\Subscription\DTOs\EntitlementData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntitlementResource extends JsonResource
{
    /**
     * Create a new resource instance.
     */
    public function __construct(private readonly EntitlementData $entitlementData)
    {
        parent::__construct($entitlementData);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'limits' => collect($this->entitlementData->limits)->map(fn (array $item) => [
                'limit' => $item['limit'],
                'usage' => $item['usage'],
                'remaining' => $item['remaining'],
            ])->toArray(),
            'features' => $this->entitlementData->features,
        ];
    }
}

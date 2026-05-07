<?php

namespace App\Application\Http\Resources;

use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreFollowerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->full_name,
            'avatar' => $this->avatar,
            'followed_at' => $this->whenPivotLoaded('store_followers', function () {
                return $this->pivot->followed_at
                    ? Carbon::parse($this->pivot->followed_at)->toIso8601String()
                    : null;
            }),
        ];
    }
}

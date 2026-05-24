<?php

namespace App\Application\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class NewFollowerResource extends JsonResource
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
            'name' => $this->full_name ?? $this->name,
            'username' => $this->username ?? $this->email,
            'avatar_url' => $this->avatar ?? $this->profile?->avatar_url ?? null,
            'location' => $this->profile?->city ?? null,
            'is_verified' => (bool) ($this->is_verified ?? false),
            'followed_at' => $this->whenPivotLoaded('store_followers', function () {
                return $this->pivot->followed_at
                    ? Carbon::parse($this->pivot->followed_at)->toIso8601String()
                    : null;
            }),
        ];
    }
}

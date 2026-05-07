<?php

namespace App\Application\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class FollowedStoreResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'logo_url' => $this->logo_url ? asset("storage/{$this->logo_url}") : null,
            'banner_url' => $this->banner_url ? asset("storage/{$this->banner_url}") : null,
            'is_verified' => $this->is_verified,
            'rating_avg' => $this->rating_avg,
            'rating_count' => $this->rating_count,
            'followers_count' => (int) $this->followers_count,
            'is_following' => true,
            'notification_enabled' => (bool) ($this->pivot?->notification_enabled ?? false),
            'followed_at' => $this->pivot?->followed_at
                ? Carbon::parse($this->pivot->followed_at)->toIso8601String()
                : null,
        ];
    }
}

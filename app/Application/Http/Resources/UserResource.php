<?php

namespace App\Application\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'email' => $this->email,
            'status' => $this->status,
            'language' => $this->language,
            'full_name' => $this->full_name,
            'phone_number' => $this->phone_number,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')->values()),
            'profile' => $this->whenLoaded('profile', [
                'first_name' => $this->profile?->first_name,
                'last_name' => $this->profile?->last_name,
                'avatar' => $this->profile?->avatar_url,
                'date_of_birth' => $this->profile?->date_of_birth?->toDateString(),
                'bio' => $this->profile?->bio,
                'gender' => $this->profile?->gender
            ]),

            'points' => $this->whenLoaded('points', [
                'balance' => $this->points?->balance,
            ]),


            'stores' => $this->whenLoaded('stores'),

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

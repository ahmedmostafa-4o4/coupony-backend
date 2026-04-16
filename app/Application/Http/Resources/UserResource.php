<?php

namespace App\Application\Http\Resources;

use DB;
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
            'roles' => $this->whenLoaded('roles', fn() => $this->roles->pluck('name')->values()),
            'profile' => $this->whenLoaded('profile', [
                'first_name' => $this->profile?->first_name,
                'last_name' => $this->profile?->last_name,
                'avatar' => $this->profile?->avatar_url,
                'date_of_birth' => $this->profile?->date_of_birth?->toDateString(),
                'bio' => $this->profile?->bio,
                'gender' => $this->profile?->gender
            ]),
            'is_store_owner' => $this->stores()->exists(),
            'is_onboarding_completed' => $this->isOnboardingCompleted($this->id, $request->header('X-User-Role') ?? $request->input('role') ?? $this->roles?->first()?->name),

            'sessions' => $this->whenLoaded('sessions', [
                'token' => $this->sessions?->first()?->token,
                'ip_address' => $this->sessions?->first()?->ip_address,
                'user_agent' => $this->sessions?->first()?->user_agent,
                'device_type' => $this->sessions?->first()?->device_type,
                'expires_at' => $this->sessions?->first()?->expires_at,
                'last_activity' => $this->sessions?->first()?->last_activity,
            ]),

            'points' => $this->whenLoaded('points', [
                'balance' => $this->points?->balance,
            ]),

            'stores' => $this->whenLoaded('stores'),

            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    private function isOnboardingCompleted(string $userId, ?string $role): bool
    {
        return match ($role) {
            'customer' => DB::table('interests')->where('user_id', $userId)->exists(),
            'seller' => DB::table('shop_interests')->where('user_id', $userId)->exists(),
            default => false,
        };
    }
}

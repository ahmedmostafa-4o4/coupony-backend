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
            'provider' => $this->provider,
            'provider_id' => $this->provider_id,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')->values()),
            'profile' => $this->whenLoaded('profile', [
                'first_name' => $this->profile?->first_name,
                'last_name' => $this->profile?->last_name,
                'avatar' => $this->profile?->avatar_url,
                'date_of_birth' => $this->profile?->date_of_birth?->toDateString(),
                'bio' => $this->profile?->bio,
                'gender' => $this->profile?->gender,
            ]),
            'is_store_owner' => $this->stores()->exists(),
            'is_employee' => $this->storeEmployeeAssignments()->exists(),
            'is_onboarding_completed' => $this->isOnboardingCompleted($this->id, $request->header('X-User-Role') ?? $request->input('role') ?? $this->roles?->first()?->name),

            'last_login_at' => $this->last_login_at?->toISOString(),
            'last_ip' => $this->last_ip,

            'sessions' => $this->whenLoaded('sessions', fn () => $this->sessions->map(fn ($session) => [
                'id' => $session->id,
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'device_type' => $session->device_type,
                'expires_at' => $session->expires_at?->toISOString(),
                'last_activity' => $session->last_activity,
            ])),

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

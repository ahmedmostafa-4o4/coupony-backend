<?php

namespace App\Application\Http\Resources;

use App\Domain\Subscription\Enums\SubscriptionStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionStatusResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'status' => $this->status?->value ?? $this->status,
            'days_remaining' => $this->getDaysRemaining(),
            'message' => $this->getActionableMessage(),
        ];
    }

    /**
     * Calculate days remaining until the current period ends.
     */
    private function getDaysRemaining(): ?int
    {
        if (! $this->current_period_end) {
            return null;
        }

        $days = (int) now()->diffInDays($this->current_period_end, false);

        return max(0, $days);
    }

    /**
     * Get contextual actionable message based on subscription status.
     */
    private function getActionableMessage(): ?string
    {
        $status = $this->status instanceof SubscriptionStatus
            ? $this->status
            : SubscriptionStatus::tryFrom($this->status);

        return match ($status) {
            SubscriptionStatus::GRACE => 'Your subscription has expired. Renew before ' .
                ($this->grace_period_end?->toDateString() ?? 'the grace period ends') .
                ' to avoid losing access.',
            SubscriptionStatus::DEGRADED => 'Your account is in degraded mode. Some features are restricted. Please renew your subscription to restore full access.',
            SubscriptionStatus::SUSPENDED => 'Your store is suspended. Payment is required to reactivate your subscription.',
            SubscriptionStatus::TRIAL => 'You are on a trial period. Subscribe to a plan before your trial ends.',
            SubscriptionStatus::NONE => 'No active subscription. Subscribe to a plan to unlock features.',
            default => null,
        };
    }
}

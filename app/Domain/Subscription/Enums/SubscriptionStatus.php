<?php

namespace App\Domain\Subscription\Enums;

enum SubscriptionStatus: string
{
    case NONE = 'none';
    case TRIAL = 'trial';
    case ACTIVE = 'active';
    case GRACE = 'grace';
    case DEGRADED = 'degraded';
    case SUSPENDED = 'suspended';
    case ARCHIVED = 'archived';

    public function canCancelAtPeriodEnd(): bool
    {
        return in_array($this, [
            self::TRIAL,
            self::ACTIVE,
            self::GRACE,
            self::DEGRADED,
        ], true);
    }
}

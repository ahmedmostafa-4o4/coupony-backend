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
}

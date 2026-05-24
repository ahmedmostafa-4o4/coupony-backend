<?php

namespace App\Domain\Subscription\Enums;

enum BillingCycle: string
{
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';
}

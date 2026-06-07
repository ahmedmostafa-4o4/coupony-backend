<?php

namespace App\Domain\Notification\Enums;

enum NotificationTypes: string
{
    // Customer notification types
    case OFFER_CLAIM_CREATED = 'offer_claim_created';
    case OFFER_REDEEMED = 'offer_redeemed';
    case POINTS_EARNED = 'points_earned';
    case NEW_OFFER = 'new_offer';
    case OFFER_CLAIM_CANCELLED = 'offer_claim_cancelled';
    case BANNER_CLAIM_CANCELLED = 'banner_claim_cancelled';

    // Seller notification types
    case NEW_OFFER_CLAIM = 'new_offer_claim';
    case OFFER_REDEEMED_BY_EMPLOYEE = 'offer_redeemed_by_employee';
    case SELLER_POINTS_EARNED = 'seller_points_earned';
    case STORE_APPROVED = 'store_approved';
    case STORE_REJECTED = 'store_rejected';
    case STORE_PENDING = 'store_pending';
    case PRODUCT_APPROVED = 'product_approved';
    case PRODUCT_REJECTED = 'product_rejected';
    case PRODUCT_PENDING = 'product_pending';
    case ANALYTICS_DAILY_SUMMARY = 'analytics_daily_summary';
    case ANALYTICS_MILESTONE = 'analytics_milestone';
    case EMPLOYEE_INVITATION_ACCEPTED = 'employee_invitation_accepted';
    case EMPLOYEE_INVITATION_REJECTED = 'employee_invitation_rejected';
    case NEW_FOLLOWER = 'new_follower';

    // Banner notification types
    case BANNER_APPROVED = 'banner_approved';
    case BANNER_REJECTED = 'banner_rejected';

    // Shared types
    case SYSTEM = 'system';
    case GENERAL = 'general';

    // Legacy/internal types
    case STORE_DOCUMENT_APPROVED = 'store_document_approved';
    case STORE_DOCUMENT_REJECTED = 'store_document_rejected';
    case SUBSCRIPTION_PAYMENT_APPROVED = 'subscription_payment_approved';
    case SUBSCRIPTION_PAYMENT_FAILED = 'subscription_payment_failed';
    case SUBSCRIPTION_EXPIRING_SOON = 'subscription_expiring_soon';
    case SUBSCRIPTION_GRACE_STARTED = 'subscription_grace_started';
    case SUBSCRIPTION_DEGRADED = 'subscription_degraded';
    case SUBSCRIPTION_SUSPENDED = 'subscription_suspended';
}

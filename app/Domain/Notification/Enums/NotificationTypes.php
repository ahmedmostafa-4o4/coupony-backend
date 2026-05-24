<?php

namespace App\Domain\Notification\Enums;

enum NotificationTypes: string
{
    case ORDER_CONFIRMATION = 'order_confirmation';
    case ORDER_SHIPPED = 'order_shipped';
    case ORDER_DELIVERED = 'order_delivered';
    case PROMOTION = 'promotion';
    case REVIEW_REQUEST = 'review_request';
    case LOW_STOCK = 'low_stock';
    case PRICE_DROP = 'price_drop';
    case BACK_IN_STOCK = 'back_in_stock';
    case COUPON_EXPIRING = 'coupon_expiring';
    case STORE_APPROVED = 'store_approved';
    case STORE_REJECTED = 'store_rejected';
    case STORE_DOCUMENT_APPROVED = 'store_document_approved';
    case STORE_DOCUMENT_REJECTED = 'store_document_rejected';
    case SUBSCRIPTION_PAYMENT_APPROVED = 'subscription_payment_approved';
    case SUBSCRIPTION_PAYMENT_FAILED = 'subscription_payment_failed';
    case SUBSCRIPTION_EXPIRING_SOON = 'subscription_expiring_soon';
    case SUBSCRIPTION_GRACE_STARTED = 'subscription_grace_started';
    case SUBSCRIPTION_DEGRADED = 'subscription_degraded';
    case SUBSCRIPTION_SUSPENDED = 'subscription_suspended';
}

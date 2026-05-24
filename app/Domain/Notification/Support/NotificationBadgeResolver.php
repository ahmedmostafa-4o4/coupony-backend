<?php

namespace App\Domain\Notification\Support;

class NotificationBadgeResolver
{
    private const BADGE_MAP = [
        'store_approved' => 'approved',
        'product_approved' => 'approved',
        'employee_invitation_accepted' => 'approved',
        'store_rejected' => 'rejected',
        'product_rejected' => 'rejected',
        'employee_invitation_rejected' => 'rejected',
        'store_pending' => 'pending',
        'product_pending' => 'pending',
        'offer_redeemed' => 'used',
        'offer_redeemed_by_employee' => 'used',
        'new_offer_claim' => 'used',
        'offer_claim_created' => 'used',
    ];

    /**
     * Resolve badge_status from notification type.
     */
    public static function resolve(string $type): string
    {
        return self::BADGE_MAP[$type] ?? 'none';
    }
}

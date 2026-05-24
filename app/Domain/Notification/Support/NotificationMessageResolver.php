<?php

namespace App\Domain\Notification\Support;

use App\Domain\User\Models\User;

class NotificationMessageResolver
{
    /**
     * Resolve localized title and message for a notification type.
     *
     * @return array{title: string, message: string}
     */
    public static function resolve(string $type, array $params = [], ?User $user = null): array
    {
        $locale = self::resolveLocale($user);

        $templates = self::templates($locale);

        if (! isset($templates[$type])) {
            return [
                'title' => $params['title'] ?? $type,
                'message' => $params['message'] ?? '',
            ];
        }

        $template = $templates[$type];

        return [
            'title' => $template['title'],
            'message' => self::interpolate($template['message'], $params),
        ];
    }

    private static function resolveLocale(?User $user): string
    {
        if ($user && $user->language) {
            return $user->language;
        }

        return app()->getLocale() === 'ar' ? 'ar' : 'en';
    }

    private static function interpolate(string $template, array $params): string
    {
        foreach ($params as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $template = str_replace('{'.$key.'}', (string) $value, $template);
            }
        }

        return $template;
    }

    /**
     * @return array<string, array{title: string, message: string}>
     */
    private static function templates(string $locale): array
    {
        if ($locale === 'ar') {
            return self::arabicTemplates();
        }

        return self::englishTemplates();
    }

    private static function arabicTemplates(): array
    {
        return [
            // Customer types
            'offer_claim_created' => [
                'title' => 'تم حجز العرض',
                'message' => 'تم حجز عرض {product_name} بنجاح. ينتهي في {expires_at}.',
            ],
            'offer_redeemed' => [
                'title' => 'تم استخدام العرض',
                'message' => 'تم استخدام عرض {product_name} بنجاح في {store_name}.',
            ],
            'points_earned' => [
                'title' => 'ربحت نقاط',
                'message' => 'ربحت {points} نقطة مقابل استخدام عرض {product_name}.',
            ],
            'new_offer' => [
                'title' => 'عرض جديد بالقرب منك',
                'message' => '{store_name} أضاف عرضاً جديداً: {product_name} بخصم {discount_value}.',
            ],

            // Seller types
            'new_offer_claim' => [
                'title' => 'عميل جديد حجز عرضك',
                'message' => 'حجز {customer_email} عرض {product_name}.',
            ],
            'offer_redeemed_by_employee' => [
                'title' => 'تم استخدام عرض',
                'message' => 'استخدم {employee_email} عرض {product_name} للعميل {customer_email}.',
            ],
            'seller_points_earned' => [
                'title' => 'ربح متجرك نقاط',
                'message' => 'ربح متجرك {points} نقطة مقابل استخدام عرض {product_name}.',
            ],
            'store_approved' => [
                'title' => 'تم قبول متجرك',
                'message' => 'مبروك! تم الموافقة على متجرك وأصبح نشطاً الآن.',
            ],
            'store_rejected' => [
                'title' => 'تم رفض متجرك',
                'message' => 'نأسف، تم رفض طلب متجرك. السبب: {rejection_reason}.',
            ],
            'store_pending' => [
                'title' => 'متجرك قيد المراجعة',
                'message' => 'شكراً لتقديم طلبك. متجرك قيد المراجعة وسنخبرك بالنتيجة قريباً.',
            ],
            'product_approved' => [
                'title' => 'تم قبول المنتج',
                'message' => 'تم الموافقة على منتجك {product_name} وأصبح ظاهراً للعملاء.',
            ],
            'product_rejected' => [
                'title' => 'تم رفض المنتج',
                'message' => 'تم رفض منتجك {product_name}. السبب: {rejection_reason}.',
            ],
            'product_pending' => [
                'title' => 'منتجك قيد المراجعة',
                'message' => 'منتجك {product_name} قيد المراجعة. سنخبرك بالنتيجة قريباً.',
            ],
            'analytics_daily_summary' => [
                'title' => 'ملخص اليوم',
                'message' => 'متجرك حقق {views} مشاهدة، {claims} حجز، و{redemptions} استخدام اليوم.',
            ],
            'analytics_milestone' => [
                'title' => 'إنجاز جديد',
                'message' => 'مبروك! وصل متجرك إلى {milestone_value} {milestone_type}.',
            ],
            'employee_invitation_accepted' => [
                'title' => 'قبل الموظف دعوتك',
                'message' => 'قبل {employee_email} دعوتك للعمل كـ {role} في متجرك.',
            ],
            'employee_invitation_rejected' => [
                'title' => 'رفض الموظف دعوتك',
                'message' => 'رفض {employee_email} دعوتك للعمل كـ {role} في متجرك.',
            ],
            'new_follower' => [
                'title' => 'متابع جديد',
                'message' => '{follower_name} بدأ متابعة متجرك.',
            ],
            'system' => [
                'title' => 'رسالة من النظام',
                'message' => '{message}',
            ],
            'general' => [
                'title' => 'إشعار عام',
                'message' => '{message}',
            ],
        ];
    }

    private static function englishTemplates(): array
    {
        return [
            // Customer types
            'offer_claim_created' => [
                'title' => 'Offer Claimed',
                'message' => 'You claimed {product_name}. Expires at {expires_at}.',
            ],
            'offer_redeemed' => [
                'title' => 'Offer Redeemed',
                'message' => 'Your offer {product_name} was redeemed at {store_name}.',
            ],
            'points_earned' => [
                'title' => 'Points Earned',
                'message' => 'You earned {points} points for redeeming {product_name}.',
            ],
            'new_offer' => [
                'title' => 'New Offer Nearby',
                'message' => '{store_name} added a new offer: {product_name} with {discount_value} off.',
            ],

            // Seller types
            'new_offer_claim' => [
                'title' => 'New Offer Claim',
                'message' => '{customer_email} claimed {product_name}.',
            ],
            'offer_redeemed_by_employee' => [
                'title' => 'Offer Redeemed',
                'message' => '{employee_email} redeemed {product_name} for {customer_email}.',
            ],
            'seller_points_earned' => [
                'title' => 'Store Points Earned',
                'message' => 'Your store earned {points} points for {product_name}.',
            ],
            'store_approved' => [
                'title' => 'Store Approved',
                'message' => 'Congratulations! Your store has been approved and is now active.',
            ],
            'store_rejected' => [
                'title' => 'Store Rejected',
                'message' => 'Sorry, your store was rejected. Reason: {rejection_reason}.',
            ],
            'store_pending' => [
                'title' => 'Store Under Review',
                'message' => 'Thank you for submitting. Your store is under review.',
            ],
            'product_approved' => [
                'title' => 'Product Approved',
                'message' => 'Your product {product_name} has been approved.',
            ],
            'product_rejected' => [
                'title' => 'Product Rejected',
                'message' => 'Your product {product_name} was rejected. Reason: {rejection_reason}.',
            ],
            'product_pending' => [
                'title' => 'Product Under Review',
                'message' => 'Your product {product_name} is under review.',
            ],
            'analytics_daily_summary' => [
                'title' => 'Daily Summary',
                'message' => 'Your store had {views} views, {claims} claims, and {redemptions} redemptions today.',
            ],
            'analytics_milestone' => [
                'title' => 'New Milestone',
                'message' => 'Congratulations! Your store reached {milestone_value} {milestone_type}.',
            ],
            'employee_invitation_accepted' => [
                'title' => 'Invitation Accepted',
                'message' => '{employee_email} accepted your invitation as {role}.',
            ],
            'employee_invitation_rejected' => [
                'title' => 'Invitation Rejected',
                'message' => '{employee_email} declined your invitation as {role}.',
            ],
            'new_follower' => [
                'title' => 'New Follower',
                'message' => '{follower_name} started following your store.',
            ],
            'system' => [
                'title' => 'System Message',
                'message' => '{message}',
            ],
            'general' => [
                'title' => 'General',
                'message' => '{message}',
            ],
        ];
    }
}

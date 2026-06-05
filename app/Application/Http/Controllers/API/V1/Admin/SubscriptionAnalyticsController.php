<?php

namespace App\Application\Http\Controllers\API\V1\Admin;

use App\Application\Http\Controllers\Controller;
use App\Domain\Subscription\Enums\PaymentSessionStatus;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\PaymentSession;
use App\Domain\Subscription\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriptionAnalyticsController extends Controller
{
    public function statistics(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        // Total active subscribers
        $activeSubscribersCount = Subscription::where('status', SubscriptionStatus::ACTIVE)->count();

        // Total revenue collected (from PAID payment sessions)
        $totalRevenue = PaymentSession::where('status', PaymentSessionStatus::PAID)->sum('amount');

        // MRR (Monthly Recurring Revenue) estimation
        // We will sum up the monthly price of the plans for all currently active subscriptions
        $mrr = Subscription::where('status', SubscriptionStatus::ACTIVE)
            ->join('subscription_plans', 'subscriptions.plan_id', '=', 'subscription_plans.id')
            ->sum('subscription_plans.price_monthly');

        // Churn count (subscriptions cancelled or suspended in the last 30 days)
        $churnCount = Subscription::whereIn('status', [SubscriptionStatus::ARCHIVED, SubscriptionStatus::SUSPENDED])
            ->where('updated_at', '>=', now()->subDays(30))
            ->count();

        return $this->localizedJson([
            'message' => __('api.admin.subscription_analytics.retrieved', ['default' => 'Subscription analytics retrieved successfully.']),
            'data' => [
                'active_subscribers' => $activeSubscribersCount,
                'total_revenue' => (float) $totalRevenue,
                'mrr' => (float) $mrr,
                'churn_last_30_days' => $churnCount,
            ],
        ]);
    }
}

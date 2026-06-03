<?php

namespace App\Application\Http\Controllers\API\V1\Admin;

use App\Application\Http\Controllers\Controller;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Get overview and analytics statistics for the admin dashboard.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function overview(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $now = Carbon::now();
            $startOfMonth = $now->copy()->startOfMonth();

            // 1. Growth & Acquisition
            $totalUsers = User::count();
            $totalActiveStores = Store::where('status', 'active')->count();
            $newUsersThisMonth = User::where('created_at', '>=', $startOfMonth)->count();
            $newStoresThisMonth = Store::where('created_at', '>=', $startOfMonth)->count();

            // 2. Financial & Business Health
            $totalSalesVolume = Store::sum('total_sales') ?? 0;
            $premiumStores = Store::where('subscription_tier', '!=', 'free')->count();
            $averageStoreRating = Store::where('status', 'active')->avg('rating_avg') ?? 0;

            // 3. Points Economy (Loyalty Liability)
            $userPointsCurrent = DB::table('user_points')->sum('current_balance') ?? 0;
            $storePointsCurrent = DB::table('store_points')->sum('current_balance') ?? 0;
            $totalPointsInCirculation = $userPointsCurrent + $storePointsCurrent;

            $userPointsEarned = DB::table('user_points')->sum('lifetime_earned') ?? 0;
            $storePointsEarned = DB::table('store_points')->sum('lifetime_earned') ?? 0;
            $lifetimePointsEarned = $userPointsEarned + $storePointsEarned;

            $userPointsSpent = DB::table('user_points')->sum('lifetime_spent') ?? 0;
            $storePointsSpent = DB::table('store_points')->sum('lifetime_spent') ?? 0;
            $lifetimePointsSpent = $userPointsSpent + $storePointsSpent;

            $pointsRedemptionRate = $lifetimePointsEarned > 0 
                ? round(($lifetimePointsSpent / $lifetimePointsEarned) * 100, 2) 
                : 0;

            // 4. Pending Action Items (Operational)
            $pendingStoreApprovals = Store::where('status', 'pending')->count();
            $pendingVerifications = DB::table('store_verifications')->where('status', 'pending')->count();
            $unresolvedCustomerTickets = DB::table('contact_us_customer')->count();
            $unresolvedSellerTickets = DB::table('contact_us_seller')->count();

            // 5. Charts Data (Last 30 days time-series & categorical)
            $thirtyDaysAgo = $now->copy()->subDays(30)->startOfDay();

            $userGrowth = User::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->groupBy('date')->orderBy('date')->get();

            $storeGrowth = Store::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->groupBy('date')->orderBy('date')->get();

            $claimsVolume = DB::table('offer_claims')
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->groupBy('date')->orderBy('date')->get();

            $pointsEarned = DB::table('user_point_transactions')
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(points) as count'))
                ->where('type', 'earned')
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->groupBy('date')->orderBy('date')->get();

            $pointsSpent = DB::table('user_point_transactions')
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(points) as count'))
                ->where('type', 'spent')
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->groupBy('date')->orderBy('date')->get();

            $subscriptionDistribution = Store::select(DB::raw('subscription_tier as tier'), DB::raw('count(*) as count'))
                ->groupBy('tier')
                ->get();

            $topStores = Store::select('id', 'name', 'total_sales', 'rating_avg')
                ->where('status', 'active')
                ->orderByDesc('total_sales')
                ->limit(5)
                ->get();

            return $this->localizedJson([
                'message' => __('api.admin.dashboard.overview_retrieved', [], 'en'), // Fallback to en or use translations if available
                'data' => [
                    'growth' => [
                        'total_users' => $totalUsers,
                        'total_stores' => $totalActiveStores,
                        'new_users_this_month' => $newUsersThisMonth,
                        'new_stores_this_month' => $newStoresThisMonth,
                    ],
                    'financial' => [
                        'total_sales_volume' => (float) $totalSalesVolume,
                        'premium_stores' => $premiumStores,
                        'average_store_rating' => round((float) $averageStoreRating, 2),
                    ],
                    'points_economy' => [
                        'total_points_in_circulation' => (int) $totalPointsInCirculation,
                        'lifetime_points_earned' => (int) $lifetimePointsEarned,
                        'lifetime_points_spent' => (int) $lifetimePointsSpent,
                        'points_redemption_rate' => $pointsRedemptionRate,
                    ],
                    'operational' => [
                        'pending_store_approvals' => $pendingStoreApprovals,
                        'pending_verifications' => $pendingVerifications,
                        'unresolved_customer_tickets' => $unresolvedCustomerTickets,
                        'unresolved_seller_tickets' => $unresolvedSellerTickets,
                    ],
                    'charts' => [
                        'user_growth' => $userGrowth,
                        'store_growth' => $storeGrowth,
                        'claims_volume' => $claimsVolume,
                        'subscription_distribution' => $subscriptionDistribution,
                        'top_stores' => $topStores,
                        'points_flow' => [
                            'earned' => $pointsEarned,
                            'spent' => $pointsSpent,
                        ]
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve dashboard overview analytics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->localizedJson([
                'message' => __('api.admin.dashboard.overview_failed', [], 'en'),
            ], 500);
        }
    }
}

<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\ProductAnalyticsRequest;
use App\Application\Http\Requests\SellerDashboardRequest;
use App\Application\Http\Requests\UpdateMonthlyGoalRequest;
use App\Domain\Analytics\Actions\GetProductAnalyticsAction;
use App\Domain\Analytics\Actions\GetSellerDashboardAction;
use App\Domain\Analytics\Actions\UpdateMonthlyGoalAction;
use App\Domain\Product\Models\Product;
use App\Domain\Store\Enums\StorePermission;
use App\Domain\Store\Models\Store;
use Illuminate\Http\JsonResponse;

class SellerAnalyticsController extends Controller
{
    public function __construct(
        private readonly GetSellerDashboardAction $getSellerDashboardAction,
        private readonly UpdateMonthlyGoalAction $updateMonthlyGoalAction,
        private readonly GetProductAnalyticsAction $getProductAnalyticsAction,
    ) {}

    /**
     * Get the seller's analytics dashboard.
     */
    public function dashboard(SellerDashboardRequest $request, string $storeId): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $store = $this->resolveStore($request, $storeId);

        $data = $this->getSellerDashboardAction->execute(
            $store,
            $request->validated('period')
        );

        return response()->json($data);
    }

    /**
     * Update the seller's monthly goal.
     */
    public function updateMonthlyGoal(UpdateMonthlyGoalRequest $request, string $storeId): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $store = $this->resolveStore($request, $storeId);

        $goal = $this->updateMonthlyGoalAction->execute(
            $store,
            $request->validated('goal')
        );

        return response()->json(['goal' => $goal]);
    }

    /**
     * Get analytics for a specific product.
     */
    public function productAnalytics(ProductAnalyticsRequest $request, string $storeId, string $productId): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $store = $this->resolveStore($request, $storeId);

        $product = Product::find($productId);

        if (! $product) {
            abort(404);
        }

        if ($product->store_id !== $store->id) {
            abort(403);
        }

        $data = $this->getProductAnalyticsAction->execute(
            $product,
            $request->validated('period')
        );

        return response()->json($data);
    }

    /**
     * Resolve and authorize the store by ID.
     *
     * The user must either own the store or be an employee with ANALYTICS_VIEW permission.
     * Aborts with 404 if store not found, 403 if user has no access.
     */
    private function resolveStore($request, string $storeId): Store
    {
        $store = Store::find($storeId);

        if (! $store) {
            abort(404);
        }

        $user = $request->user();

        // Check if the user owns this store
        if ($store->owner_user_id === $user->id) {
            return $store;
        }

        // Check if the user is an employee of this store with analytics permission
        $isEmployee = $store->employees()->where('user_id', $user->id)->exists();

        if ($isEmployee && $store->employeeHasPermission($user, StorePermission::ANALYTICS_VIEW->value)) {
            return $store;
        }

        abort(403);
    }
}

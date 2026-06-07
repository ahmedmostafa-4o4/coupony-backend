<?php

namespace App\Application\Http\Controllers\API\V1\Admin;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\Admin\ApproveStoreVerificationRequest;
use App\Application\Http\Requests\Admin\RejectStoreVerificationRequest;
use App\Application\Http\Resources\VerificationResource;
use App\Domain\Store\Actions\ApproveVerificationDocument;
use App\Domain\Store\Actions\RejectVerificationDocument;
use App\Domain\Store\Models\StoreVerification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreVerificationManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $query = StoreVerification::query()
            ->with('store'); // Eager load the store details

        // Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by Store ID
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->input('store_id'));
        }

        // Search by Store Name
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('store', function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%");
            });
        }

        // Sort by newest by default, or let them specify sorting
        $query->orderBy('created_at', 'desc');

        $verifications = $query->paginate($request->integer('per_page', 20));

        return $this->localizedJson([
            'message' => 'Store verifications retrieved successfully.',
            'data' => VerificationResource::collection($verifications->items()),
            'meta' => [
                'current_page' => $verifications->currentPage(),
                'last_page' => $verifications->lastPage(),
                'total' => $verifications->total(),
            ]
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $verification = StoreVerification::with('store')->findOrFail($id);

        return $this->localizedJson([
            'message' => 'Store verification retrieved successfully.',
            'data' => new VerificationResource($verification),
        ]);
    }

    public function approve(
        ApproveStoreVerificationRequest $request,
        string $id,
        ApproveVerificationDocument $action
    ): JsonResponse {
        $this->applyAuthenticatedLocale($request);

        $verification = StoreVerification::with('store')->findOrFail($id);

        try {
            $verification = $action->execute(
                $verification,
                $request->user(),
                $request->input('notes')
            );

            return $this->localizedJson([
                'message' => 'Store verification document approved successfully.',
                'data' => new VerificationResource($verification),
            ]);
        } catch (\Exception $e) {
            return $this->localizedJson([
                'message' => $e->getMessage(),
                'success' => false,
            ], 400);
        }
    }

    public function reject(
        RejectStoreVerificationRequest $request,
        string $id,
        RejectVerificationDocument $action
    ): JsonResponse {
        $this->applyAuthenticatedLocale($request);

        $verification = StoreVerification::with('store')->findOrFail($id);

        try {
            $verification = $action->execute(
                $verification,
                $request->user(),
                $request->input('reason')
            );

            return $this->localizedJson([
                'message' => 'Store verification document rejected successfully.',
                'data' => new VerificationResource($verification),
            ]);
        } catch (\Exception $e) {
            return $this->localizedJson([
                'message' => $e->getMessage(),
                'success' => false,
            ], 400);
        }
    }
}

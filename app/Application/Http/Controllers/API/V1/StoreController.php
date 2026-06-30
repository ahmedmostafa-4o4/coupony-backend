<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\CreateStoreRequest;
use App\Application\Http\Requests\UpdateStoreProfileRequest;
use App\Application\Http\Requests\UpdateStoreRequest;
use App\Application\Http\Requests\UpdateVerificationDocumentRequest;
use App\Application\Http\Resources\PublicStoreResource;
use App\Application\Http\Resources\StoreCollection;
use App\Application\Http\Resources\StoreResource;
use App\Domain\Store\Actions\CreateStore;
use App\Domain\Store\Actions\UpdateStore;
use App\Domain\Store\Actions\UpdateStoreProfile;
use App\Domain\Store\Actions\UpdateVerificationDocument;
use App\Domain\Store\DTOs\StoreData;
use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Enums\VerificationDocumentType;
use App\Domain\Store\Enums\VerificationStatus;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreProfileView;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class StoreController extends Controller
{
    public function __construct(
        private readonly CreateStore $createStoreAction,
        private readonly UpdateStore $updateStoreAction,
        private readonly UpdateStoreProfile $updateStoreProfileAction,
        private readonly UpdateVerificationDocument $updateVerificationDocumentAction
    ) {
    }

    /**
     * Create a new store.
     */
    public function store(CreateStoreRequest $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $user = $request->user();

        try {
            return DB::transaction(function () use ($request, $user) {
                // Create store
                $store = $this->createStoreAction->execute(
                    $user,
                    StoreData::fromRequest($request)
                );

                // Handle file uploads after store creation
                $this->handleStoreFileUploads($request, $store);
                $this->handleVerificationDocuments($request, $store);

                // Load relationships
                $store->load($this->storeRelations());

                return $this->successResponse(
                    new StoreResource($store),
                    __('api.store.created'),
                    201
                );
            });
        } catch (Throwable $e) {
            Log::error('Store creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                __('api.store.create_failed'),
                500
            );
        }
    }

    /**
     * Update store information.
     */
    public function update(UpdateStoreRequest $request, Store $store): JsonResponse
    {

        $this->applyAuthenticatedLocale($request);

        Gate::authorize('update', $store);

        $user = $request->user();

        try {
            return DB::transaction(function () use ($request, $store, $user) {
                $updatedStore = $this->updateStoreAction->execute(
                    $store,
                    $user,
                    StoreData::fromUpdateRequest($request, $store->id)
                );

                // Load relationships
                $updatedStore->load($this->storeRelations());

                return $this->successResponse(
                    new StoreResource($updatedStore),
                    __('api.store.updated')
                );
            });
        } catch (Throwable $e) {
            Log::error('Store update failed', [
                'store_id' => $store->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                __('api.store.update_failed'),
                500
            );
        }
    }

    /**
     * Update non-reviewable store profile information.
     */
    public function updateProfile(UpdateStoreProfileRequest $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        Gate::authorize('updateProfile', $store);

        $user = $request->user();

        try {
            return DB::transaction(function () use ($request, $store) {
                $updatedStore = $this->updateStoreProfileAction->execute(
                    $store,
                    $request->validated(),
                    $request->file('logo_url'),
                    $request->file('banner_url')
                );

                $updatedStore->load($this->storeRelations());

                return $this->successResponse(
                    new StoreResource($updatedStore),
                    __('api.store.profile_updated')
                );
            });
        } catch (Throwable $e) {
            Log::error('Store profile update failed', [
                'store_id' => $store->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                __('api.store.update_failed'),
                500
            );
        }
    }

    /**
     * Get authenticated user's stores.
     */
    public function index(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $stores = $request->user()
                ->stores()
                ->with($this->storeRelations())
                ->latest()
                ->get();

            return $this->successResponse(
                new StoreCollection($stores),
                __('api.store.retrieved')
            );
        } catch (Throwable $e) {
            Log::error('Failed to retrieve user stores', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                __('api.store.retrieve_failed'),
                500
            );
        }
    }

    /**
     * Get public active stores with filters.
     */
    public function publicIndex(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:store_categories,id'],
            'city' => ['nullable', 'string', 'max:255'],
            'is_verified' => ['nullable', 'boolean'],
            'min_rating' => ['nullable', 'numeric', 'between:0,5'],
            'sort_by' => ['nullable', Rule::in(['latest', 'rating', 'name', 'popular'])],
            'sort_direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $query = Store::query()
                ->where('status', StoreStatus::ACTIVE)
                ->with($this->publicStoreRelations());

            if (filled($validated['search'] ?? null)) {
                $query->where(function ($builder) use ($validated) {
                    $builder
                        ->where('name', 'like', '%' . $validated['search'] . '%')
                        ->orWhere('description', 'like', '%' . $validated['search'] . '%')
                        ->orWhereHas('categories', function ($catBuilder) use ($validated) {
                            $catBuilder->where('name', 'like', '%' . $validated['search'] . '%');
                        })
                        ->orWhereHas('addresses', function ($addrBuilder) use ($validated) {
                            $addrBuilder->where('city', 'like', '%' . $validated['search'] . '%')
                                ->orWhere('address_line1', 'like', '%' . $validated['search'] . '%');
                        });
                });
            }

            if (filled($validated['category_id'] ?? null)) {
                $query->whereHas('categories', fn($builder) => $builder->whereKey($validated['category_id']));
            }

            if (filled($validated['city'] ?? null)) {
                $query->whereHas('addresses', function ($builder) use ($validated) {
                    $builder->where('city', 'like', '%' . $validated['city'] . '%');
                });
            }

            if (array_key_exists('is_verified', $validated) && $validated['is_verified'] !== null) {
                $query->where('is_verified', $validated['is_verified']);
            }

            if (filled($validated['min_rating'] ?? null)) {
                $query->where('rating_avg', '>=', $validated['min_rating']);
            }

            $sortBy = $validated['sort_by'] ?? 'latest';
            $sortDirection = $validated['sort_direction']
                ?? ($sortBy === 'name' ? 'asc' : 'desc');

            match ($sortBy) {
                'rating' => $query->orderBy('rating_avg', $sortDirection)->orderBy('rating_count', 'desc'),
                'name' => $query->orderBy('name', $sortDirection),
                'popular' => $query->orderBy('followers_count', $sortDirection)->orderBy('rating_avg', 'desc'),
                default => $query->latest(),
            };

            $stores = $query->paginate($validated['per_page'] ?? 15);

            return $this->paginatedResponse(
                PublicStoreResource::collection($stores->getCollection())->resolve($request),
                __('api.store.public_retrieved'),
                $stores
            );
        } catch (Throwable $e) {
            Log::error('Failed to retrieve public stores', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                __('api.store.public_retrieve_failed'),
                500
            );
        }
    }

    /**
     * Show a single public store profile.
     * Records a profile view for analytics.
     */
    public function publicShow(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if ($store->status !== StoreStatus::ACTIVE) {
            return $this->errorResponse(__('api.store.retrieve_failed'), 404);
        }

        try {
            $store->load($this->publicStoreRelations());

            // Record profile view for analytics
            $viewer = $this->resolveAuthenticatedUser($request);
            StoreProfileView::create([
                'store_id' => $store->id,
                'user_id' => $viewer?->id,
                'ip_address' => $request->ip(),
            ]);

            return $this->successResponse(
                (new PublicStoreResource($store))->resolve($request),
                __('api.store.public_retrieved')
            );
        } catch (Throwable $e) {
            Log::error('Failed to retrieve public store', [
                'store_id' => $store->id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                __('api.store.public_retrieve_failed'),
                500
            );
        }
    }

    /**
     * Update verification document.
     */
    public function updateVerificationDocument(
        UpdateVerificationDocumentRequest $request,
        Store $store
    ): JsonResponse {
        $this->applyAuthenticatedLocale($request);

        Gate::authorize('updateVerificationDocuments', $store);

        $user = $request->user();

        try {
            return DB::transaction(function () use ($request, $store, $user) {
                $documentType = $request->input('document_type');
                $file = $request->file('document');

                // Upload new file
                $path = $this->uploadVerificationDocument($file, $store->id, $documentType);

                // Update verification document
                $verification = $this->updateVerificationDocumentAction->execute(
                    $store,
                    $user,
                    $documentType,
                    $path
                );

                return $this->successResponse(
                    $verification,
                    __('api.store.verification_updated')
                );
            });
        } catch (Throwable $e) {
            Log::error('Verification document update failed', [
                'store_id' => $store->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                __('api.store.verification_update_failed'),
                500
            );
        }
    }

    /**
     * Handle store file uploads (logo and banner).
     */
    private function handleStoreFileUploads(CreateStoreRequest $request, Store $store): void
    {
        if ($request->hasFile('logo_url')) {
            $logoPath = $this->uploadFile(
                $request->file('logo_url'),
                $store->id,
                'logo'
            );
            $store->update(['logo_url' => $logoPath]);
        }

        if ($request->hasFile('banner_url')) {
            $bannerPath = $this->uploadFile(
                $request->file('banner_url'),
                $store->id,
                'banner'
            );
            $store->update(['banner_url' => $bannerPath]);
        }
    }

    /**
     * Handle verification documents upload.
     */
    private function handleVerificationDocuments(CreateStoreRequest $request, Store $store): void
    {
        foreach (VerificationDocumentType::cases() as $docType) {
            $requestKey = "verification_docs.{$docType->value}";

            if ($request->hasFile($requestKey)) {
                $docPath = $this->uploadVerificationDocument(
                    $request->file($requestKey),
                    $store->id,
                    $docType->value
                );

                $store->verifications()->updateOrCreate(
                    ['document_type' => $docType->value],
                    [
                        'document_path' => $docPath,
                        'status' => VerificationStatus::PENDING->value,
                    ]
                );
            }
        }
    }

    /**
     * Upload a file to storage.
     */
    private function uploadFile($file, string $storeId, string $folder): string
    {
        return $file->store("stores/{$storeId}/{$folder}", 'public');
    }

    /**
     * Upload a verification document.
     */
    private function uploadVerificationDocument($file, string $storeId, string $documentType): string
    {
        return $file->store("stores/{$storeId}/verifications/{$documentType}", 'public');
    }

    /**
     * Delete a file from storage if it exists.
     */
    private function deleteFileIfExists(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Get standard store relationships to load.
     */
    private function storeRelations(): array
    {
        return ['owner', 'categories', 'addresses', 'verifications', 'hours', 'socials.social'];
    }

    /**
     * Get public-safe store relationships to load.
     */
    private function publicStoreRelations(): array
    {
        return ['categories', 'addresses', 'hours', 'socials.social'];
    }

    /**
     * Return a success JSON response.
     */
    private function successResponse($data, string $message, int $status = 200): JsonResponse
    {
        return $this->localizedJson([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Return an error JSON response.
     */
    private function errorResponse(string $message, int $status = 400, ?array $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return $this->localizedJson($response, $status);
    }

    private function paginatedResponse($data, string $message, LengthAwarePaginator $paginator): JsonResponse
    {
        return $this->localizedJson([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}

<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Resources\AddressResource;
use App\Domain\User\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MeAddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $addresses = $request->user()
            ->addresses()
            ->latest('addressables.created_at')
            ->get();

        return $this->localizedJson([
            'data' => AddressResource::collection($addresses),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $this->validateAddress($request, true);
        $user = $request->user();

        $address = DB::transaction(function () use ($validated, $user) {
            $address = Address::create($this->extractAddressFields($validated));

            $pivotFields = $this->extractPivotFields($validated);

            $user->addresses()->attach($address->id, $pivotFields);
            $this->syncDefaultFlags($user, $address->id, $pivotFields);

            return $user->addresses()->whereKey($address->id)->firstOrFail();
        });

        return $this->localizedJson([
            'data' => new AddressResource($address),
        ], 201);
    }

    public function update(Request $request, int $addressId): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $this->validateAddress($request, false);
        $user = $request->user();
        $address = $user->addresses()->whereKey($addressId)->firstOrFail();

        $updatedAddress = DB::transaction(function () use ($validated, $user, $address) {
            $addressFields = $this->extractAddressFields($validated);
            if ($addressFields !== []) {
                $address->update($addressFields);
            }

            $pivotFields = $this->extractPivotFields($validated, false);
            if ($pivotFields !== []) {
                $user->addresses()->updateExistingPivot($address->id, $pivotFields);
                $this->syncDefaultFlags($user, $address->id, $pivotFields);
            }

            return $user->addresses()->whereKey($address->id)->firstOrFail();
        });

        return $this->localizedJson([
            'data' => new AddressResource($updatedAddress),
        ]);
    }

    public function destroy(Request $request, int $addressId): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $user = $request->user();
        $address = $user->addresses()->whereKey($addressId)->firstOrFail();

        DB::transaction(function () use ($user, $address) {
            $user->addresses()->detach($address->id);
            $address->delete();
        });

        return $this->localizedJson([
            'message' => 'Address deleted successfully.',
        ]);
    }

    private function validateAddress(Request $request, bool $creating): array
    {
        $required = $creating ? ['required', 'string', 'max:255'] : ['sometimes', 'string', 'max:255'];
        $requiredCity = $creating ? ['required', 'string', 'max:100'] : ['sometimes', 'string', 'max:100'];

        return $request->validate([
            'first_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'company' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_line1' => $required,
            'address_line2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => $requiredCity,
            'state_province' => ['sometimes', 'nullable', 'string', 'max:100'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:20'],
            'country_code' => ['sometimes', 'nullable', 'string', 'size:2'],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:20'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'delivery_instructions' => ['sometimes', 'nullable', 'string'],
            'label' => ['sometimes', 'nullable', 'string', 'max:50'],
            'is_default_shipping' => ['sometimes', 'boolean'],
            'is_default_billing' => ['sometimes', 'boolean'],
        ]);
    }

    private function extractAddressFields(array $validated): array
    {
        return collect($validated)->only([
            'first_name',
            'last_name',
            'company',
            'address_line1',
            'address_line2',
            'city',
            'state_province',
            'postal_code',
            'country_code',
            'phone_number',
            'latitude',
            'longitude',
            'delivery_instructions',
        ])->all();
    }

    private function extractPivotFields(array $validated, bool $withDefaults = true): array
    {
        $pivotFields = [];

        if (array_key_exists('label', $validated) || $withDefaults) {
            $pivotFields['label'] = $validated['label'] ?? 'home';
        }

        if (array_key_exists('is_default_shipping', $validated) || $withDefaults) {
            $pivotFields['is_default_shipping'] = (bool) ($validated['is_default_shipping'] ?? false);
        }

        if (array_key_exists('is_default_billing', $validated) || $withDefaults) {
            $pivotFields['is_default_billing'] = (bool) ($validated['is_default_billing'] ?? false);
        }

        return $pivotFields;
    }

    private function syncDefaultFlags($user, int $addressId, array $pivotFields): void
    {
        if (($pivotFields['is_default_shipping'] ?? false) === true) {
            DB::table('addressables')
                ->where('owner_type', $user::class)
                ->where('owner_id', $user->id)
                ->where('address_id', '!=', $addressId)
                ->update(['is_default_shipping' => false]);
        }

        if (($pivotFields['is_default_billing'] ?? false) === true) {
            DB::table('addressables')
                ->where('owner_type', $user::class)
                ->where('owner_id', $user->id)
                ->where('address_id', '!=', $addressId)
                ->update(['is_default_billing' => false]);
        }
    }
}

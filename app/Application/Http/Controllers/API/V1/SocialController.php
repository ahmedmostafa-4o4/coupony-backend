<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\CreateSocialRequest;
use App\Application\Http\Requests\UpdateSocialRequest;
use App\Domain\Store\Models\Social;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SocialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            return $this->localizedJson([
                'success' => true,
                'message' => 'Socials retrieved successfully.',
                'data' => Social::query()
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Social $social) => $this->formatSocial($social)),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to retrieve socials', [
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'success' => false,
                'message' => 'Failed to retrieve socials.',
            ], 500);
        }
    }

    public function store(CreateSocialRequest $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $iconPath = $request->file('icon')->store('socials/icons', 'public');

            $social = Social::create([
                'name' => $request->string('name')->toString(),
                'icon' => $iconPath,
            ]);

            return $this->localizedJson([
                'success' => true,
                'message' => 'Social created successfully.',
                'data' => $this->formatSocial($social),
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Failed to create social', [
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'success' => false,
                'message' => 'Failed to create social.',
            ], 500);
        }
    }

    public function update(UpdateSocialRequest $request, Social $social): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $data = [];

            if ($request->filled('name')) {
                $data['name'] = $request->string('name')->toString();
            }

            if ($request->hasFile('icon')) {
                $newIconPath = $request->file('icon')->store('socials/icons', 'public');

                if ($social->icon && Storage::disk('public')->exists($social->icon)) {
                    Storage::disk('public')->delete($social->icon);
                }

                $data['icon'] = $newIconPath;
            }

            $social->update($data);

            return $this->localizedJson([
                'success' => true,
                'message' => 'Social updated successfully.',
                'data' => $this->formatSocial($social->fresh()),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to update social', [
                'social_id' => $social->id,
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'success' => false,
                'message' => 'Failed to update social.',
            ], 500);
        }
    }

    public function destroy(Request $request, Social $social): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            if ($social->storeSocial()->exists()) {
                return $this->localizedJson([
                    'success' => false,
                    'message' => 'Cannot delete social while it is assigned to stores.',
                ], 400);
            }

            if ($social->icon && Storage::disk('public')->exists($social->icon)) {
                Storage::disk('public')->delete($social->icon);
            }

            $social->delete();

            return $this->localizedJson([
                'success' => true,
                'message' => 'Social deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to delete social', [
                'social_id' => $social->id,
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'success' => false,
                'message' => 'Failed to delete social.',
            ], 500);
        }
    }

    private function formatSocial(Social $social): array
    {
        return [
            'id' => $social->id,
            'name' => $social->name,
            'icon' => $social->icon,
            'icon_url' => $social->icon ? Storage::disk('public')->url($social->icon) : null,
            'created_at' => $social->created_at?->toIso8601String(),
            'updated_at' => $social->updated_at?->toIso8601String(),
        ];
    }
}

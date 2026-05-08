<?php

namespace App\Domain\Product\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class RecommendationMlService
{
    public function recommend(string $userId, array $recentProductIds, int $limit): ?array
    {
        $baseUrl = rtrim((string) config('services.recommendation.base_url'), '/');

        if ($baseUrl === '') {
            return null;
        }

        try {
            $response = Http::baseUrl($baseUrl)
                ->acceptJson()
                ->asJson()
                ->timeout((int) config('services.recommendation.timeout', 10))
                ->post('/recommend', [
                    'user_id' => $userId,
                    'recent_product_ids' => array_values($recentProductIds),
                    'limit' => $limit,
                ]);
        } catch (Throwable $throwable) {
            report($throwable);

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();

        if (! is_array($payload) || ! array_key_exists('recommended_ids', $payload) || ! is_array($payload['recommended_ids'])) {
            return null;
        }

        return array_values(
            array_filter(
                $payload['recommended_ids'],
                static fn($id) => is_string($id) && $id !== ''
            )
        );
    }
}

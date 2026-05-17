<?php

namespace App\Domain\PonyAI\Repositories;

use App\Domain\PonyAI\Models\PonyImageEmbedding;
use App\Domain\PonyAI\Models\PonyProductEmbedding;
use Illuminate\Support\Collection;

class EmbeddingRepository
{
    /**
     * @param  array<int, float>  $textEmbedding
     */
    public function upsertProductTextEmbedding(
        string $productId,
        array $textEmbedding,
        int $sourceRevisionNo,
        ?string $modelVersion,
    ): PonyProductEmbedding {
        /** @var PonyProductEmbedding $embedding */
        $embedding = PonyProductEmbedding::query()->updateOrCreate(
            ['product_id' => $productId],
            [
                'text_embedding' => $textEmbedding,
                'source_revision_no' => $sourceRevisionNo,
                'model_version' => $modelVersion,
                'generated_at' => now(),
            ],
        );

        return $embedding;
    }

    /**
     * @param  array<int, float>  $imageEmbedding
     */
    public function upsertProductImageEmbedding(
        string $productId,
        array $imageEmbedding,
        ?string $modelVersion,
    ): PonyProductEmbedding {
        /** @var PonyProductEmbedding $embedding */
        $embedding = PonyProductEmbedding::query()->updateOrCreate(
            ['product_id' => $productId],
            [
                'image_embedding' => $imageEmbedding,
                'model_version' => $modelVersion,
                'generated_at' => now(),
            ],
        );

        return $embedding;
    }

    /**
     * @param  array<int, float>  $embedding
     */
    public function upsertImageEmbedding(
        int $productImageId,
        array $embedding,
        ?string $caption,
        ?string $modelVersion,
    ): PonyImageEmbedding {
        /** @var PonyImageEmbedding $row */
        $row = PonyImageEmbedding::query()->updateOrCreate(
            ['product_image_id' => $productImageId],
            [
                'embedding' => $embedding,
                'caption' => $caption,
                'model_version' => $modelVersion,
                'generated_at' => now(),
            ],
        );

        return $row;
    }

    /**
     * @param  array<int, string>  $productIds
     * @return Collection<int, PonyProductEmbedding>
     */
    public function findProductEmbeddings(array $productIds): Collection
    {
        if ($productIds === []) {
            return collect();
        }

        return PonyProductEmbedding::query()
            ->whereIn('product_id', $productIds)
            ->get();
    }

    /**
     * @param  array<int, string>  $productIds
     * @return Collection<int, PonyImageEmbedding>
     */
    public function findImageEmbeddingsForProducts(array $productIds): Collection
    {
        if ($productIds === []) {
            return collect();
        }

        return PonyImageEmbedding::query()
            ->whereHas('productImage', fn($query) => $query->whereIn('product_id', $productIds))
            ->with('productImage')
            ->get();
    }
}

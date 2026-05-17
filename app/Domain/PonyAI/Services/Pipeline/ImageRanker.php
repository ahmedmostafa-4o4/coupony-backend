<?php

namespace App\Domain\PonyAI\Services\Pipeline;

use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\Exceptions\GeminiException;
use App\Domain\PonyAI\Repositories\EmbeddingRepository;
use App\Domain\PonyAI\Support\VectorMath;

class ImageRanker
{
    public function __construct(
        private readonly GeminiClient $gemini,
        private readonly EmbeddingRepository $embeddings,
    ) {
    }

    /**
     * Rerank candidate product IDs by a weighted blend of image-vs-image cosine
     * (against any stored pony_image_embeddings rows of the candidate's images) and
     * caption-vs-text cosine (against the candidate's stored pony_product_embeddings).
     *
     * Candidates without any embedding fall through in SQL order so we never
     * truncate the candidate set when embeddings haven't been backfilled yet.
     *
     * @param  array<int, string>  $candidateIds
     * @return array<int, string>
     */
    public function rerank(
        string $imageBytes,
        string $mimeType,
        string $captionText,
        array $candidateIds,
        int $topK = 8,
    ): array {
        $topK = max(1, $topK);

        if ($candidateIds === []) {
            return [];
        }

        $imageVector = $this->safeEmbedImage($imageBytes, $mimeType);
        $captionVector = $this->safeEmbedText($captionText);

        if ($imageVector === null && $captionVector === null) {
            return array_slice($candidateIds, 0, $topK);
        }

        $textEmbeddings = $this->embeddings->findProductEmbeddings($candidateIds)
            ->keyBy('product_id');

        $imageEmbeddingRows = $this->embeddings->findImageEmbeddingsForProducts($candidateIds);

        // For each product, take the best (max) score across its images.
        $imageVectorsByProduct = [];
        foreach ($imageEmbeddingRows as $row) {
            $productId = (string) ($row->productImage?->product_id ?? '');

            if ($productId === '' || ! is_array($row->embedding) || $row->embedding === []) {
                continue;
            }

            $imageVectorsByProduct[$productId][] = $row->embedding;
        }

        $alpha = $this->alpha();
        $scored = [];
        $unscored = [];

        foreach ($candidateIds as $productId) {
            $imageScore = $this->bestCosine($imageVector, $imageVectorsByProduct[$productId] ?? []);
            $textScore = $this->bestCosine(
                $captionVector,
                ($textEmbeddings->get($productId)?->text_embedding ?? null) === null
                    ? []
                    : [$textEmbeddings->get($productId)->text_embedding],
            );

            $haveImage = $imageScore !== null && $imageVector !== null;
            $haveText = $textScore !== null && $captionVector !== null;

            if (! $haveImage && ! $haveText) {
                $unscored[] = $productId;
                continue;
            }

            $combined = match (true) {
                $haveImage && $haveText => ($alpha * $imageScore) + ((1.0 - $alpha) * $textScore),
                $haveImage => $imageScore,
                default => $textScore,
            };

            $scored[] = ['id' => $productId, 'score' => $combined];
        }

        usort($scored, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);

        $rankedIds = array_map(static fn(array $row): string => (string) $row['id'], $scored);
        $rankedIds = array_slice($rankedIds, 0, $topK);

        if (count($rankedIds) >= $topK) {
            return $rankedIds;
        }

        $fillNeeded = $topK - count($rankedIds);

        return [...$rankedIds, ...array_slice($unscored, 0, $fillNeeded)];
    }

    /**
     * @param  array<int, float>|null  $query
     * @param  array<int, array<int, float>>  $candidates
     */
    private function bestCosine(?array $query, array $candidates): ?float
    {
        if ($query === null || $candidates === []) {
            return null;
        }

        $best = null;
        foreach ($candidates as $vector) {
            if (! is_array($vector) || $vector === []) {
                continue;
            }
            if (count($vector) !== count($query)) {
                continue;
            }

            $score = VectorMath::cosine($query, $vector);
            if ($best === null || $score > $best) {
                $best = $score;
            }
        }

        return $best;
    }

    /**
     * @return array<int, float>|null
     */
    private function safeEmbedImage(string $imageBytes, string $mimeType): ?array
    {
        if ($imageBytes === '') {
            return null;
        }

        try {
            return $this->gemini->embedImage($imageBytes, $mimeType);
        } catch (GeminiException) {
            return null;
        }
    }

    /**
     * @return array<int, float>|null
     */
    private function safeEmbedText(string $text): ?array
    {
        if (trim($text) === '') {
            return null;
        }

        try {
            return $this->gemini->embedText($text, ['task_type' => 'RETRIEVAL_QUERY']);
        } catch (GeminiException) {
            return null;
        }
    }

    private function alpha(): float
    {
        $value = (float) config('services.gemini.image_rank_alpha', 0.6);

        return max(0.0, min(1.0, $value));
    }
}

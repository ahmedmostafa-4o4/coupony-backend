<?php

namespace App\Domain\PonyAI\Services\Pipeline;

use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\Exceptions\GeminiException;
use App\Domain\PonyAI\Repositories\EmbeddingRepository;
use App\Domain\PonyAI\Support\VectorMath;

class EmbeddingReranker
{
    public function __construct(
        private readonly GeminiClient $gemini,
        private readonly EmbeddingRepository $embeddings,
    ) {
    }

    /**
     * Rerank candidate product IDs by cosine similarity to the query embedding.
     * Candidates without a stored embedding are kept at the tail in their SQL order,
     * so we never blow up the candidate set when embeddings haven't been backfilled yet.
     *
     * @param  array<int, string>  $candidateIds
     * @return array<int, string>
     */
    public function rerank(string $queryText, array $candidateIds, int $topK = 10): array
    {
        $topK = max(1, $topK);

        if ($candidateIds === []) {
            return [];
        }

        $queryVector = $this->safeEmbed($queryText);

        if ($queryVector === null) {
            return array_slice($candidateIds, 0, $topK);
        }

        $stored = $this->embeddings->findProductEmbeddings($candidateIds)
            ->keyBy('product_id');

        $withVector = [];
        $withoutVector = [];

        foreach ($candidateIds as $id) {
            $embedding = $stored->get($id);
            $vector = $embedding?->text_embedding;

            if (is_array($vector) && $vector !== []) {
                $withVector[$id] = $vector;
            } else {
                $withoutVector[] = $id;
            }
        }

        $ranked = VectorMath::topK($queryVector, $withVector, $topK);
        $rerankedIds = array_map(static fn(array $row): string => (string) $row['id'], $ranked);

        if (count($rerankedIds) >= $topK) {
            return $rerankedIds;
        }

        $fillNeeded = $topK - count($rerankedIds);

        return [...$rerankedIds, ...array_slice($withoutVector, 0, $fillNeeded)];
    }

    /**
     * @return array<int, float>|null
     */
    private function safeEmbed(string $text): ?array
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
}

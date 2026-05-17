<?php

namespace App\Domain\PonyAI\Support;

use App\Domain\PonyAI\Exceptions\PonyAIException;

class VectorMath
{
    /**
     * Cosine similarity between two equal-length numeric vectors.
     * Returns 0.0 when either vector has zero magnitude.
     *
     * @param  array<int, float|int>  $a
     * @param  array<int, float|int>  $b
     */
    public static function cosine(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            throw new PonyAIException(sprintf(
                'Cannot compute cosine of vectors of different lengths (%d vs %d).',
                count($a),
                count($b),
            ));
        }

        if ($a === []) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($a as $index => $valueA) {
            $valueA = (float) $valueA;
            $valueB = (float) $b[$index];

            $dot += $valueA * $valueB;
            $normA += $valueA * $valueA;
            $normB += $valueB * $valueB;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }

    /**
     * Rank candidates by cosine similarity against a query vector.
     *
     * @param  array<int, float|int>  $query
     * @param  array<int|string, array<int, float|int>>  $candidates  keyed by id, value = vector
     * @return array<int, array{id: int|string, score: float}>  sorted descending, length <= $topK
     */
    public static function topK(array $query, array $candidates, int $topK): array
    {
        if ($topK <= 0 || $candidates === []) {
            return [];
        }

        $scored = [];
        foreach ($candidates as $id => $vector) {
            if (! is_array($vector) || $vector === []) {
                continue;
            }

            $scored[] = [
                'id' => $id,
                'score' => self::cosine($query, $vector),
            ];
        }

        usort($scored, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, $topK);
    }
}

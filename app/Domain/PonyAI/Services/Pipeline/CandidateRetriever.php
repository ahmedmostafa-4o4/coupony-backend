<?php

namespace App\Domain\PonyAI\Services\Pipeline;

use App\Domain\PonyAI\DTOs\ChatIntent;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class CandidateRetriever
{
    /**
     * Return up to $limit candidate product IDs that match the intent's
     * structured filters and (loose) free-text search. The list is the SQL
     * floor; the embedding reranker will narrow it further.
     *
     * @return array<int, string>
     */
    public function candidates(ChatIntent $intent, int $limit = 50): array
    {
        $limit = max(1, $limit);

        $query = Product::query()
            ->where('status', ProductStatus::ACTIVE->value)
            ->where('approval_status', ProductApprovalStatus::APPROVED->value)
            ->when($intent->categoryId !== null, fn(Builder $q) => $q->whereHas(
                'categories',
                fn(Builder $categoryQuery) => $categoryQuery->whereKey($intent->categoryId),
            ))
            ->when($intent->priceMin !== null, fn(Builder $q) => $q->where('base_price', '>=', $intent->priceMin))
            ->when($intent->priceMax !== null, fn(Builder $q) => $q->where('base_price', '<=', $intent->priceMax));

        $this->applyTextSearch($query, $intent);

        return $query
            ->orderByDesc('rating_avg')
            ->orderByDesc('sale_count')
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id')
            ->all();
    }

    private function applyTextSearch(Builder $query, ChatIntent $intent): void
    {
        $tokens = $this->tokenize($intent->freeText);
        $terms = collect([...$tokens, ...$intent->attributes])
            ->map(fn($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($terms === []) {
            return;
        }

        $query->where(function (Builder $nested) use ($terms): void {
            foreach ($terms as $term) {
                $like = '%'.$this->escapeLike($term).'%';

                $nested->orWhere('title', 'like', $like)
                    ->orWhere('short_description', 'like', $like)
                    ->orWhere('description', 'like', $like);
            }
        });
    }

    /**
     * Split a free-text prompt into search tokens, dropping noise words and
     * very short fragments. Falls back to the full string if tokenizing yields
     * nothing (e.g. a single short term like a product code).
     *
     * @return array<int, string>
     */
    private function tokenize(string $text): array
    {
        $trimmed = trim($text);

        if ($trimmed === '') {
            return [];
        }

        $stopwords = [
            'a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 'from',
            'has', 'have', 'i', 'in', 'is', 'it', 'me', 'my', 'need', 'of',
            'on', 'or', 'please', 'show', 'something', 'that', 'the', 'this',
            'to', 'want', 'with', 'you',
        ];

        $parts = preg_split('/[\s,;.!?\/\\\\]+/u', mb_strtolower($trimmed)) ?: [];
        $tokens = [];
        foreach ($parts as $part) {
            $clean = trim($part);
            if ($clean === '' || mb_strlen($clean) < 3 || in_array($clean, $stopwords, true)) {
                continue;
            }
            $tokens[] = $clean;
        }

        // If every word was a stopword or too short, return an empty token list rather
        // than a verbatim fallback. The caller then runs an unfiltered query (sorted by
        // popularity), which gives a useful candidate set for vague prompts like
        // "show me something".
        return $tokens;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}

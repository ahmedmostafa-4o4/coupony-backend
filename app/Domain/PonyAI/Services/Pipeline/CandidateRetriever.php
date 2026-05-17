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
     * Return up to $limit candidate product IDs.
     *
     * Hard filters (a missing filter is ignored):
     *  - status         = ACTIVE
     *  - approval       = APPROVED
     *  - category_id    matches one of the product's categories (only if valid)
     *  - price_min      <= base_price
     *  - price_max      >= base_price
     *
     * Text is NEVER used as a WHERE clause - it only affects ORDER BY. Any
     * remaining active+approved products still appear at the tail so the
     * embedding reranker (and AnswerComposer) get a usable set to work with.
     *
     * For generic catalog requests ("show me what you have", "هل يوجد منتجات")
     * we skip the soft text ranking entirely and order purely by popularity
     * (rating_avg, sale_count, created_at).
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

        if (! $intent->isGenericCatalogRequest) {
            $tokens = $this->collectSoftRankingTokens($intent);

            if ($tokens !== []) {
                $this->applySoftTextRanking($query, $tokens);
            }
        }

        return $query
            ->orderByDesc('rating_avg')
            ->orderByDesc('sale_count')
            ->orderByDesc('created_at')
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id')
            ->all();
    }

    /**
     * Add a `(CASE WHEN title LIKE ? THEN 3 ELSE 0 END + ...)` expression as the
     * primary ORDER BY so products that contain the user's tokens float to the
     * top - without filtering anything out.
     *
     * @param  array<int, string>  $tokens
     */
    private function applySoftTextRanking(Builder $query, array $tokens): void
    {
        $expressions = [];
        $bindings = [];

        foreach ($tokens as $token) {
            $like = '%'.$this->escapeLike($token).'%';

            $expressions[] = '(CASE WHEN title LIKE ? THEN 3 ELSE 0 END)';
            $bindings[] = $like;

            $expressions[] = '(CASE WHEN short_description LIKE ? THEN 2 ELSE 0 END)';
            $bindings[] = $like;

            $expressions[] = '(CASE WHEN description LIKE ? THEN 1 ELSE 0 END)';
            $bindings[] = $like;
        }

        $expression = '('.implode(' + ', $expressions).')';

        $query->orderByRaw($expression.' desc', $bindings);
    }

    /**
     * Pull the multilingual tokens that should boost ranking. We deliberately
     * collect from arabicQuery / semanticQuery / keywords / attributes BEFORE
     * touching the raw free text, so the model's expanded vocabulary leads.
     *
     * @return array<int, string>
     */
    private function collectSoftRankingTokens(ChatIntent $intent): array
    {
        $sources = [];

        if ($intent->semanticQuery !== null) {
            $sources[] = $intent->semanticQuery;
        }
        if ($intent->arabicQuery !== null) {
            $sources[] = $intent->arabicQuery;
        }
        foreach ($intent->keywords as $keyword) {
            $sources[] = $keyword;
        }
        foreach ($intent->attributes as $attribute) {
            $sources[] = $attribute;
        }
        $sources[] = $intent->freeText;

        $tokens = [];
        foreach ($sources as $source) {
            foreach ($this->tokenize((string) $source) as $token) {
                $tokens[] = $token;
            }
        }

        return array_values(array_unique($tokens));
    }

    /**
     * Split text into search-friendly tokens. Stays multilingual:
     *  - keeps Arabic words (no script filter)
     *  - drops English stopwords only
     *  - keeps short Arabic words (>= 2 chars)
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

            if ($clean === '' || mb_strlen($clean) < 2) {
                continue;
            }
            // Stopword list is English-only; Arabic words pass through.
            if (in_array($clean, $stopwords, true)) {
                continue;
            }

            $tokens[] = $clean;
        }

        return $tokens;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}

<?php

namespace App\Domain\PonyAI\Services;

use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\Models\PonyProductEmbedding;
use App\Domain\PonyAI\Repositories\EmbeddingRepository;
use App\Domain\Product\Models\Product;

class ProductEmbeddingService
{
    public function __construct(
        private readonly GeminiClient $gemini,
        private readonly EmbeddingRepository $embeddings,
    ) {
    }

    public function embed(Product $product): PonyProductEmbedding
    {
        $text = $this->buildTextBlob($product);
        $vector = $this->gemini->embedText($text, ['task_type' => 'RETRIEVAL_DOCUMENT']);

        return $this->embeddings->upsertProductTextEmbedding(
            productId: $product->id,
            textEmbedding: $vector,
            sourceRevisionNo: (int) $product->published_revision_no,
            modelVersion: (string) config('services.gemini.embed_model'),
        );
    }

    public function buildTextBlob(Product $product): string
    {
        $product->loadMissing(['categories', 'variants.attributes', 'offer']);

        $parts = [];
        $parts[] = 'Title: '.((string) $product->title);

        if (filled($product->short_description)) {
            $parts[] = 'Summary: '.((string) $product->short_description);
        }

        if (filled($product->description)) {
            $parts[] = 'Description: '.((string) $product->description);
        }

        $categoryNames = $product->categories
            ->map(fn($category) => (string) ($category->name_en ?? $category->name ?? ''))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($categoryNames !== []) {
            $parts[] = 'Categories: '.implode(', ', $categoryNames);
        }

        $variantTitles = $product->variants
            ->map(fn($variant) => trim(implode(' ', array_filter([
                (string) ($variant->title ?? ''),
                (string) ($variant->option_summary ?? ''),
            ]))))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($variantTitles !== []) {
            $parts[] = 'Variants: '.implode('; ', $variantTitles);
        }

        $variantAttributes = $product->variants
            ->flatMap(fn($variant) => collect($variant->attributes ?? [])
                ->map(fn($attribute) => trim(
                    ((string) ($attribute->attribute_name ?? ''))
                    .'='.((string) ($attribute->attribute_value ?? ''))
                )))
            ->filter(fn(string $pair) => $pair !== '' && $pair !== '=')
            ->unique()
            ->values()
            ->all();

        if ($variantAttributes !== []) {
            $parts[] = 'Attributes: '.implode(', ', $variantAttributes);
        }

        $offer = $product->offer;
        if ($offer) {
            $offerPieces = [];
            $offerPieces[] = 'type='.($offer->type?->value ?? (string) $offer->type);

            if (filled($offer->label)) {
                $offerPieces[] = 'label='.((string) $offer->label);
            }

            $parts[] = 'Offer: '.implode(' ', $offerPieces);
        }

        if (filled($product->base_price)) {
            $parts[] = sprintf(
                'Price: %s %s',
                (string) $product->base_price,
                (string) ($product->currency ?? 'EGP'),
            );
        }

        return implode("\n", $parts);
    }
}

<?php

namespace App\Domain\PonyAI\Services;

use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\Exceptions\PonyAIException;
use App\Domain\PonyAI\Models\PonyImageEmbedding;
use App\Domain\PonyAI\Repositories\EmbeddingRepository;
use App\Domain\Product\Models\ProductImage;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class ImageEmbeddingService
{
    public function __construct(
        private readonly GeminiClient $gemini,
        private readonly EmbeddingRepository $embeddings,
    ) {
    }

    public function embed(ProductImage $image): PonyImageEmbedding
    {
        [$bytes, $mimeType] = $this->readImage($image);

        $caption = $this->gemini->describeImage(
            $bytes,
            $mimeType,
            'Describe this product image for catalog search. Include category, primary color, '
            .'materials, distinguishing features, and any visible brand text. Keep it under 80 words.',
        );

        $captionText = trim($caption->text);

        if ($captionText === '') {
            throw new PonyAIException(sprintf(
                'Gemini returned an empty caption for product image #%d.',
                $image->id,
            ));
        }

        $vector = $this->gemini->embedText($captionText, ['task_type' => 'RETRIEVAL_DOCUMENT']);

        return $this->embeddings->upsertImageEmbedding(
            productImageId: (int) $image->id,
            embedding: $vector,
            caption: $captionText,
            modelVersion: (string) config('services.gemini.embed_model'),
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function readImage(ProductImage $image): array
    {
        $path = (string) $image->image_url;

        if ($path === '') {
            throw new PonyAIException(sprintf(
                'Product image #%d has no image_url stored.',
                $image->id,
            ));
        }

        $disk = $this->resolveDisk();

        if (! $disk->exists($path)) {
            throw new PonyAIException(sprintf(
                'Product image #%d at %s is not present on the public disk.',
                $image->id,
                $path,
            ));
        }

        $bytes = (string) $disk->get($path);
        $mimeType = (string) ($disk->mimeType($path) ?: $this->guessMimeFromPath($path));

        return [$bytes, $mimeType];
    }

    private function resolveDisk(): Filesystem
    {
        return Storage::disk('public');
    }

    private function guessMimeFromPath(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'application/octet-stream',
        };
    }
}

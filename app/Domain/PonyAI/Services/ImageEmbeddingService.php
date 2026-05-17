<?php

namespace App\Domain\PonyAI\Services;

use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\Exceptions\PonyAIException;
use App\Domain\PonyAI\Models\PonyImageEmbedding;
use App\Domain\PonyAI\Repositories\EmbeddingRepository;
use App\Domain\Product\Models\ProductImage;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ImageEmbeddingService
{
    /**
     * MIME types Gemini Vision accepts AND that we are willing to fetch from a
     * remote source. Anything else (text/html, application/json, etc.) is a
     * sign the URL is wrong - reject rather than send garbage to Gemini.
     */
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

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
     * Fetch the raw bytes + MIME type for a ProductImage.
     *
     * The stored image_url can either be a relative path on the public disk
     * (the seller-upload case) or a full external URL (the imported-catalog
     * case). We pick the right transport for each.
     *
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

        if ($this->isHttpUrl($path)) {
            return $this->downloadRemote($image, $path);
        }

        return $this->readFromDisk($image, $path);
    }

    private function isHttpUrl(string $path): bool
    {
        return Str::startsWith($path, ['http://', 'https://']);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function readFromDisk(ProductImage $image, string $path): array
    {
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

    /**
     * Download a remote image into memory. The bytes are NOT persisted - they
     * live only long enough for Gemini Vision to caption them, then go out of
     * scope.
     *
     * @return array{0: string, 1: string}
     */
    private function downloadRemote(ProductImage $image, string $url): array
    {
        $timeout = max(1, (int) config('pony.image_download_timeout', 15));
        $maxBytes = max(1, (int) config('pony.image_download_max_bytes', 6 * 1024 * 1024));

        try {
            $response = Http::timeout($timeout)
                ->connectTimeout($timeout)
                ->retry(2, 200, throw: false)
                ->withHeaders(['Accept' => 'image/*'])
                ->get($url);
        } catch (ConnectionException $exception) {
            throw new PonyAIException(sprintf(
                'Product image #%d at %s could not be downloaded: %s',
                $image->id,
                $url,
                $exception->getMessage(),
            ));
        } catch (Throwable $exception) {
            throw new PonyAIException(sprintf(
                'Product image #%d at %s could not be downloaded: %s',
                $image->id,
                $url,
                $exception->getMessage(),
            ));
        }

        if (! $response->successful()) {
            throw new PonyAIException(sprintf(
                'Product image #%d at %s returned HTTP %d.',
                $image->id,
                $url,
                $response->status(),
            ));
        }

        $bytes = (string) $response->body();
        $size = strlen($bytes);

        if ($size === 0) {
            throw new PonyAIException(sprintf(
                'Product image #%d at %s returned an empty body.',
                $image->id,
                $url,
            ));
        }

        if ($size > $maxBytes) {
            throw new PonyAIException(sprintf(
                'Product image #%d at %s is %d bytes which exceeds the %d byte limit.',
                $image->id,
                $url,
                $size,
                $maxBytes,
            ));
        }

        $mimeType = $this->resolveRemoteMimeType($response->header('Content-Type'), $url);

        if (! in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new PonyAIException(sprintf(
                'Product image #%d at %s has unsupported content-type "%s".',
                $image->id,
                $url,
                $mimeType,
            ));
        }

        return [$bytes, $mimeType];
    }

    private function resolveRemoteMimeType(?string $header, string $url): string
    {
        $mime = is_string($header) ? trim($header) : '';

        if ($mime !== '') {
            // Strip a "; charset=..." suffix that some CDNs add even on binary responses.
            if (($semicolon = strpos($mime, ';')) !== false) {
                $mime = trim(substr($mime, 0, $semicolon));
            }

            $mime = strtolower($mime);

            if ($mime !== '') {
                return $mime;
            }
        }

        return $this->guessMimeFromPath((string) parse_url($url, PHP_URL_PATH));
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

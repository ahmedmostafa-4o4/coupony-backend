<?php

namespace App\Application\Console\Commands;

use App\Domain\PonyAI\Exceptions\PonyAIException;
use App\Domain\PonyAI\Services\ImageEmbeddingService;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class PonyEmbedImages extends Command
{
    protected $signature = 'pony:embed-images
        {--store= : Limit to images of products in a single store id}
        {--chunk=20 : Chunk size when iterating}
        {--force : Re-embed even if an embedding already exists}';

    protected $description = 'Backfill or refresh Pony AI product image embeddings (Gemini Vision captions).';

    public function handle(ImageEmbeddingService $service): int
    {
        $store = $this->option('store');
        $chunk = max(1, (int) $this->option('chunk'));
        $force = (bool) $this->option('force');

        $query = ProductImage::query()
            ->whereHas('product', function (Builder $product) use ($store): void {
                $product->where('status', ProductStatus::ACTIVE->value)
                    ->where('approval_status', ProductApprovalStatus::APPROVED->value);

                if (filled($store)) {
                    $product->where('store_id', $store);
                }
            })
            ->when(! $force, function (Builder $q): void {
                $q->whereNotIn('id', function ($subQuery): void {
                    $subQuery->select('product_image_id')->from('pony_image_embeddings');
                });
            })
            ->orderBy('id');

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No product images to embed.');

            return self::SUCCESS;
        }

        $this->info("Embedding {$total} image(s)...");
        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $succeeded = 0;
        $failed = 0;

        $query->chunkById($chunk, function ($images) use ($service, $progressBar, &$succeeded, &$failed): void {
            foreach ($images as $image) {
                try {
                    $service->embed($image);
                    $succeeded++;
                } catch (PonyAIException $exception) {
                    $failed++;
                    $this->newLine();
                    $this->warn("Skipping image {$image->id}: {$exception->getMessage()}");
                } catch (Throwable $throwable) {
                    $failed++;
                    $this->newLine();
                    $this->error("Failed image {$image->id}: {$throwable->getMessage()}");
                }

                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine(2);
        $this->info("Done. Succeeded: {$succeeded}, Failed: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}

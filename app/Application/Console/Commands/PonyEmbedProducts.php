<?php

namespace App\Application\Console\Commands;

use App\Domain\PonyAI\Exceptions\PonyAIException;
use App\Domain\PonyAI\Services\ProductEmbeddingService;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class PonyEmbedProducts extends Command
{
    protected $signature = 'pony:embed-products
        {--store= : Limit to a single store id}
        {--since= : Only re-embed products updated since this datetime (parseable by Carbon)}
        {--chunk=50 : Chunk size when iterating}
        {--force : Re-embed even if an embedding already exists}';

    protected $description = 'Backfill or refresh Pony AI product text embeddings.';

    public function handle(ProductEmbeddingService $service): int
    {
        $store = $this->option('store');
        $since = $this->option('since');
        $chunk = max(1, (int) $this->option('chunk'));
        $force = (bool) $this->option('force');

        $query = Product::query()
            ->where('status', ProductStatus::ACTIVE->value)
            ->where('approval_status', ProductApprovalStatus::APPROVED->value)
            ->when(filled($store), fn(Builder $q) => $q->where('store_id', $store))
            ->when(filled($since), fn(Builder $q) => $q->where('updated_at', '>=', $since))
            ->when(! $force, function (Builder $q): void {
                $q->whereNotIn('id', function ($subQuery): void {
                    $subQuery->select('product_id')->from('pony_product_embeddings');
                });
            })
            ->orderBy('id');

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No products to embed.');

            return self::SUCCESS;
        }

        $this->info("Embedding {$total} product(s)...");
        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $succeeded = 0;
        $failed = 0;

        $query->chunkById($chunk, function ($products) use ($service, $progressBar, &$succeeded, &$failed): void {
            foreach ($products as $product) {
                try {
                    $service->embed($product);
                    $succeeded++;
                } catch (PonyAIException $exception) {
                    $failed++;
                    $this->newLine();
                    $this->warn("Skipping product {$product->id}: {$exception->getMessage()}");
                } catch (Throwable $throwable) {
                    $failed++;
                    $this->newLine();
                    $this->error("Failed product {$product->id}: {$throwable->getMessage()}");
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

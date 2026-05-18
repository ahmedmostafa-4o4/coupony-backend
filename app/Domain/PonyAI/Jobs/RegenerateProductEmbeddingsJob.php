<?php

namespace App\Domain\PonyAI\Jobs;

use App\Domain\PonyAI\Services\ProductEmbeddingService;
use App\Domain\Product\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RegenerateProductEmbeddingsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public string $productId) {}

    public function handle(ProductEmbeddingService $service): void
    {
        $product = Product::query()->find($this->productId);

        if (! $product) {
            return;
        }

        $service->embed($product);
    }

    public function uniqueId(): string
    {
        return $this->productId;
    }
}

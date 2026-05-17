<?php

namespace App\Domain\PonyAI\Listeners;

use App\Domain\PonyAI\Jobs\RegenerateProductEmbeddingsJob;
use App\Domain\Product\Events\ProductRevisionApproved;

class QueueProductReembeddingOnRevisionApproval
{
    public function handle(ProductRevisionApproved $event): void
    {
        RegenerateProductEmbeddingsJob::dispatch($event->product->id);
    }
}

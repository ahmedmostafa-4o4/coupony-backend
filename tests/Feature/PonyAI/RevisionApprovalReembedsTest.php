<?php

namespace Tests\Feature\PonyAI;

use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\Jobs\RegenerateProductEmbeddingsJob;
use App\Domain\PonyAI\Services\GeminiFakeClient;
use App\Domain\Product\Actions\ApproveProductRevision;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductRevisionAction;
use App\Domain\Product\Enums\ProductRevisionStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductRevision;
use App\Domain\Product\Repositories\ProductRepository;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class RevisionApprovalReembedsTest extends TestCase
{
    use RefreshDatabase;

    private function makePendingRevision(): array
    {
        $seller = User::factory()->create();
        $admin = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $seller->id]);

        $product = Product::factory()->create([
            'store_id' => $store->id,
            'status' => ProductStatus::INACTIVE,
            'approval_status' => ProductApprovalStatus::PENDING,
            'published_revision_no' => 0,
        ]);

        $payload = $this->app->make(ProductRepository::class)->snapshotPayload($product);

        $revision = ProductRevision::create([
            'product_id' => $product->id,
            'revision_no' => 1,
            'action' => ProductRevisionAction::CREATE,
            'status' => ProductRevisionStatus::PENDING,
            'base_revision_no' => null,
            'submitted_by' => $seller->id,
            'submitted_at' => now(),
            'payload' => $payload,
        ]);

        return [$revision, $admin, $product];
    }

    public function test_approving_a_revision_dispatches_reembed_job(): void
    {
        Bus::fake();

        [$revision, $admin] = $this->makePendingRevision();

        $product = $this->app->make(ApproveProductRevision::class)->execute($revision, $admin);

        Bus::assertDispatched(RegenerateProductEmbeddingsJob::class, function (RegenerateProductEmbeddingsJob $job) use ($product) {
            return $job->productId === $product->id;
        });
    }

    public function test_queued_job_writes_embedding_when_run(): void
    {
        /** @var GeminiFakeClient $fake */
        $fake = $this->app->make(GeminiClient::class);
        $fake->queueEmbedding([0.2, 0.4, 0.6]);

        [$revision, $admin, $product] = $this->makePendingRevision();

        // QUEUE_CONNECTION=sync in phpunit.xml, so the listener-dispatched job runs inline.
        $this->app->make(ApproveProductRevision::class)->execute($revision, $admin);

        $this->assertDatabaseHas('pony_product_embeddings', [
            'product_id' => $product->id,
        ]);
    }
}

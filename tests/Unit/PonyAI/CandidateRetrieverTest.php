<?php

namespace Tests\Unit\PonyAI;

use App\Domain\PonyAI\DTOs\ChatIntent;
use App\Domain\PonyAI\Services\Pipeline\CandidateRetriever;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\Category;
use App\Domain\Product\Models\Product;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateRetrieverTest extends TestCase
{
    use RefreshDatabase;

    private function makeStore(): Store
    {
        return Store::factory()->create([
            'owner_user_id' => User::factory()->create()->id,
        ]);
    }

    private function active(array $attributes = []): Product
    {
        return Product::factory()->create(array_merge([
            'store_id' => $this->makeStore()->id,
            'status' => ProductStatus::ACTIVE,
            'approval_status' => ProductApprovalStatus::APPROVED,
        ], $attributes));
    }

    public function test_returns_only_active_and_approved_products(): void
    {
        $expected = $this->active(['title' => 'Red leather wallet']);

        $this->active([
            'title' => 'Inactive wallet',
            'status' => ProductStatus::INACTIVE,
        ]);

        $this->active([
            'title' => 'Pending wallet',
            'approval_status' => ProductApprovalStatus::PENDING,
        ]);

        $ids = $this->app->make(CandidateRetriever::class)->candidates(
            new ChatIntent(freeText: 'wallet'),
            10,
        );

        $this->assertSame([$expected->id], $ids);
    }

    public function test_filters_by_category(): void
    {
        $category = Category::factory()->create(['name' => 'Wallets', 'name_en' => 'Wallets']);

        $matching = $this->active(['title' => 'leather product']);
        $matching->categories()->attach($category->id);

        $this->active(['title' => 'leather product two']); // no category

        $ids = $this->app->make(CandidateRetriever::class)->candidates(
            new ChatIntent(freeText: 'leather', categoryId: $category->id),
            10,
        );

        $this->assertSame([$matching->id], $ids);
    }

    public function test_filters_by_price_band(): void
    {
        $tooCheap = $this->active(['title' => 'item', 'base_price' => 10]);
        $inRange = $this->active(['title' => 'item', 'base_price' => 100]);
        $tooPricey = $this->active(['title' => 'item', 'base_price' => 1000]);

        $ids = $this->app->make(CandidateRetriever::class)->candidates(
            new ChatIntent(freeText: 'item', priceMin: 50, priceMax: 500),
            10,
        );

        $this->assertContains($inRange->id, $ids);
        $this->assertNotContains($tooCheap->id, $ids);
        $this->assertNotContains($tooPricey->id, $ids);
    }

    public function test_free_text_matches_title_summary_or_description(): void
    {
        $byTitle = $this->active(['title' => 'Bluetooth headphones', 'description' => 'audio device']);
        $byShort = $this->active(['title' => 'Generic', 'short_description' => 'noise-cancelling Bluetooth']);
        $byLong = $this->active(['title' => 'Mystery', 'description' => 'Has Bluetooth and a battery']);
        $unrelated = $this->active(['title' => 'Backpack', 'description' => 'Holds books']);

        $ids = $this->app->make(CandidateRetriever::class)->candidates(
            new ChatIntent(freeText: 'bluetooth'),
            10,
        );

        $this->assertContains($byTitle->id, $ids);
        $this->assertContains($byShort->id, $ids);
        $this->assertContains($byLong->id, $ids);
        $this->assertNotContains($unrelated->id, $ids);
    }

    public function test_attributes_widen_the_text_search(): void
    {
        $match = $this->active(['title' => 'Sneakers', 'description' => 'comfortable trainers']);
        $other = $this->active(['title' => 'Boots', 'description' => 'heavy duty']);

        $ids = $this->app->make(CandidateRetriever::class)->candidates(
            new ChatIntent(freeText: 'shoes', attributes: ['sneakers']),
            10,
        );

        $this->assertContains($match->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_respects_limit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->active(['title' => "item-{$i}"]);
        }

        $ids = $this->app->make(CandidateRetriever::class)->candidates(new ChatIntent(freeText: 'item'), 3);

        $this->assertCount(3, $ids);
    }
}

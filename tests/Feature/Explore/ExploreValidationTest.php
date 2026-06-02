<?php

namespace Tests\Feature\Explore;

use App\Domain\Product\Models\Category;
use App\Domain\Store\Models\StoreCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExploreValidationTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────────────────────
    // interest_id validation (400 responses)
    // Validates: Requirement 7.3
    // ─────────────────────────────────────────────────────────────────────────

    public function test_bootstrap_returns_400_for_nonexistent_interest_id(): void
    {
        $response = $this->getJson('/api/v1/explore?interest_id=99999');

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertNotEmpty($response->json('message'));
    }

    public function test_bootstrap_returns_400_for_inactive_interest_id(): void
    {
        $category = Category::factory()->inactive()->create();

        $response = $this->getJson("/api/v1/explore?interest_id={$category->id}");

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertNotEmpty($response->json('message'));
    }

    public function test_picks_returns_400_for_nonexistent_interest_id(): void
    {
        $response = $this->getJson('/api/v1/explore/picks?interest_id=99999');

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertNotEmpty($response->json('message'));
    }

    public function test_picks_returns_400_for_inactive_interest_id(): void
    {
        $category = Category::factory()->inactive()->create();

        $response = $this->getJson("/api/v1/explore/picks?interest_id={$category->id}");

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertNotEmpty($response->json('message'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // activity_id validation (400 responses)
    // Validates: Requirement 8.3
    // ─────────────────────────────────────────────────────────────────────────

    public function test_bootstrap_returns_400_for_nonexistent_activity_id(): void
    {
        $response = $this->getJson('/api/v1/explore?activity_id=99999');

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertNotEmpty($response->json('message'));
    }

    public function test_bootstrap_returns_400_for_inactive_activity_id(): void
    {
        $storeCategory = StoreCategory::factory()->inactive()->create();

        $response = $this->getJson("/api/v1/explore?activity_id={$storeCategory->id}");

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertNotEmpty($response->json('message'));
    }

    public function test_picks_returns_400_for_nonexistent_activity_id(): void
    {
        $response = $this->getJson('/api/v1/explore/picks?activity_id=99999');

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertNotEmpty($response->json('message'));
    }

    public function test_picks_returns_400_for_inactive_activity_id(): void
    {
        $storeCategory = StoreCategory::factory()->inactive()->create();

        $response = $this->getJson("/api/v1/explore/picks?activity_id={$storeCategory->id}");

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertNotEmpty($response->json('message'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // min_discount_percent validation (422 responses)
    // Validates: Requirement 10.3
    // ─────────────────────────────────────────────────────────────────────────

    public function test_picks_returns_422_for_negative_min_discount_percent(): void
    {
        $response = $this->getJson('/api/v1/explore/picks?min_discount_percent=-1');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('min_discount_percent');
    }

    public function test_picks_returns_422_for_min_discount_percent_above_90(): void
    {
        $response = $this->getJson('/api/v1/explore/picks?min_discount_percent=91');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('min_discount_percent');
    }

    public function test_picks_returns_422_for_non_numeric_min_discount_percent(): void
    {
        $response = $this->getJson('/api/v1/explore/picks?min_discount_percent=abc');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('min_discount_percent');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // sort_by validation (422 responses)
    // Validates: Requirement 11.5
    // ─────────────────────────────────────────────────────────────────────────

    public function test_picks_returns_422_for_invalid_sort_by_value(): void
    {
        $response = $this->getJson('/api/v1/explore/picks?sort_by=invalid_value');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('sort_by');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // page/page_size validation (422 responses)
    // Validates: Requirement 12.4
    // ─────────────────────────────────────────────────────────────────────────

    public function test_picks_returns_422_for_page_zero(): void
    {
        $response = $this->getJson('/api/v1/explore/picks?page=0');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('page');
    }

    public function test_picks_returns_422_for_negative_page(): void
    {
        $response = $this->getJson('/api/v1/explore/picks?page=-1');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('page');
    }

    public function test_picks_returns_422_for_page_size_zero(): void
    {
        $response = $this->getJson('/api/v1/explore/picks?page_size=0');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('page_size');
    }

    public function test_picks_returns_422_for_page_size_above_50(): void
    {
        $response = $this->getJson('/api/v1/explore/picks?page_size=51');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('page_size');
    }

    public function test_picks_returns_422_for_non_numeric_page_size(): void
    {
        $response = $this->getJson('/api/v1/explore/picks?page_size=abc');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('page_size');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Valid interest_id and activity_id return 200 (no error)
    // Validates: Requirements 7.3, 8.3
    // ─────────────────────────────────────────────────────────────────────────

    public function test_bootstrap_returns_200_for_valid_active_interest_id(): void
    {
        $category = Category::factory()->create(['is_active' => true]);

        $response = $this->getJson("/api/v1/explore?interest_id={$category->id}");

        $response->assertOk();
    }

    public function test_picks_returns_200_for_valid_active_interest_id(): void
    {
        $category = Category::factory()->create(['is_active' => true]);

        $response = $this->getJson("/api/v1/explore/picks?interest_id={$category->id}");

        $response->assertOk();
    }

    public function test_bootstrap_returns_200_for_valid_active_activity_id(): void
    {
        $storeCategory = StoreCategory::factory()->create(['is_active' => true]);

        $response = $this->getJson("/api/v1/explore?activity_id={$storeCategory->id}");

        $response->assertOk();
    }

    public function test_picks_returns_200_for_valid_active_activity_id(): void
    {
        $storeCategory = StoreCategory::factory()->create(['is_active' => true]);

        $response = $this->getJson("/api/v1/explore/picks?activity_id={$storeCategory->id}");

        $response->assertOk();
    }
}

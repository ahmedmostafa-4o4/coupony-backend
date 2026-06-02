<?php

namespace Tests\Feature\Explore;

use App\Domain\Product\Actions\FavoriteProduct;
use App\Domain\Product\Actions\UnfavoriteProduct;
use App\Domain\Product\Models\Product;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Feature: explore-page, Property 14: Favorites Count Increment/Decrement Round-Trip
 *
 * For any product, if a user favorites it, favorites_count SHALL increase by exactly 1.
 * If a user unfavorites it, favorites_count SHALL decrease by exactly 1 (but never below 0).
 * After the sync command runs, favorites_count SHALL equal the actual count of records
 * in product_favorites for that product.
 *
 * **Validates: Requirements 13.2, 13.3, 13.4, 13.5**
 */
class FavoritesCountSyncTest extends TestCase
{
    use RefreshDatabase;

    private FavoriteProduct $favoriteAction;
    private UnfavoriteProduct $unfavoriteAction;

    protected function setUp(): void
    {
        parent::setUp();
        $this->favoriteAction = app(FavoriteProduct::class);
        $this->unfavoriteAction = app(UnfavoriteProduct::class);
    }

    /**
     * @test
     * @dataProvider favoriteSequenceDataProvider
     *
     * Property 14: After N favorite operations, favorites_count = N
     * Validates: Requirements 13.2
     */
    public function favorites_count_equals_number_of_favorites(int $numUsers): void
    {
        $store = Store::factory()->active()->create();
        $product = Product::factory()->active()->approved()->create([
            'store_id' => $store->id,
            'favorites_count' => 0,
        ]);

        $users = User::factory()->count($numUsers)->create();

        foreach ($users as $user) {
            $this->favoriteAction->execute($product, $user);
        }

        $product->refresh();
        $this->assertEquals(
            $numUsers,
            $product->favorites_count,
            "After {$numUsers} favorite operations, favorites_count should be {$numUsers}, got {$product->favorites_count}"
        );
    }

    /**
     * @test
     * @dataProvider favoriteUnfavoriteSequenceDataProvider
     *
     * Property 14: After N favorites followed by M unfavorites (M <= N), favorites_count = N - M
     * Validates: Requirements 13.2, 13.3
     */
    public function favorites_count_equals_favorites_minus_unfavorites(int $numFavorites, int $numUnfavorites): void
    {
        $store = Store::factory()->active()->create();
        $product = Product::factory()->active()->approved()->create([
            'store_id' => $store->id,
            'favorites_count' => 0,
        ]);

        $users = User::factory()->count($numFavorites)->create();

        // Favorite all users
        foreach ($users as $user) {
            $this->favoriteAction->execute($product, $user);
        }

        // Unfavorite the first M users
        $usersToUnfavorite = $users->take($numUnfavorites);
        foreach ($usersToUnfavorite as $user) {
            $this->unfavoriteAction->execute($product, $user);
        }

        $product->refresh();
        $expectedCount = $numFavorites - $numUnfavorites;
        $this->assertEquals(
            $expectedCount,
            $product->favorites_count,
            "After {$numFavorites} favorites and {$numUnfavorites} unfavorites, "
            . "favorites_count should be {$expectedCount}, got {$product->favorites_count}"
        );
    }

    /**
     * @test
     * @dataProvider excessUnfavoriteDataProvider
     *
     * Property 14: favorites_count never goes below 0 (even with more unfavorites than favorites)
     * Validates: Requirements 13.5
     */
    public function favorites_count_never_goes_below_zero(int $numFavorites, int $numExtraUnfavorites): void
    {
        $store = Store::factory()->active()->create();
        $product = Product::factory()->active()->approved()->create([
            'store_id' => $store->id,
            'favorites_count' => 0,
        ]);

        $allUsers = User::factory()->count($numFavorites + $numExtraUnfavorites)->create();
        $favoriteUsers = $allUsers->take($numFavorites);
        $extraUsers = $allUsers->slice($numFavorites);

        // Favorite with the first N users
        foreach ($favoriteUsers as $user) {
            $this->favoriteAction->execute($product, $user);
        }

        // Unfavorite all N users
        foreach ($favoriteUsers as $user) {
            $this->unfavoriteAction->execute($product, $user);
        }

        // Attempt extra unfavorites (these users never favorited, so unfavorite just deletes nothing)
        foreach ($extraUsers as $user) {
            $this->unfavoriteAction->execute($product, $user);
        }

        $product->refresh();
        $this->assertGreaterThanOrEqual(
            0,
            $product->favorites_count,
            "favorites_count should never go below 0, got {$product->favorites_count}"
        );
    }

    /**
     * @test
     * @dataProvider syncCommandDataProvider
     *
     * Property 14: After running explore:sync-favorites-count, the count matches actual records
     * Validates: Requirements 13.4
     */
    public function sync_command_produces_correct_count(int $numFavorites, int $numUnfavorites): void
    {
        $store = Store::factory()->active()->create();
        $product = Product::factory()->active()->approved()->create([
            'store_id' => $store->id,
            'favorites_count' => 0,
        ]);

        $users = User::factory()->count($numFavorites)->create();

        // Favorite all users
        foreach ($users as $user) {
            $this->favoriteAction->execute($product, $user);
        }

        // Unfavorite the first M users
        $usersToUnfavorite = $users->take($numUnfavorites);
        foreach ($usersToUnfavorite as $user) {
            $this->unfavoriteAction->execute($product, $user);
        }

        // Deliberately corrupt the favorites_count to simulate drift
        $product->update(['favorites_count' => 999]);

        // Run the sync command
        Artisan::call('explore:sync-favorites-count');

        $product->refresh();
        $expectedCount = $numFavorites - $numUnfavorites;
        $this->assertEquals(
            $expectedCount,
            $product->favorites_count,
            "After sync, favorites_count should equal actual record count ({$expectedCount}), "
            . "got {$product->favorites_count}"
        );
    }

    /**
     * Data provider generating random numbers of users for favorite operations.
     *
     * @return \Generator<string, array{int}>
     */
    public static function favoriteSequenceDataProvider(): \Generator
    {
        $faker = \Faker\Factory::create();

        for ($i = 0; $i < 20; $i++) {
            $numUsers = $faker->numberBetween(1, 8);
            yield "iteration_{$i}_favorites({$numUsers})" => [$numUsers];
        }
    }

    /**
     * Data provider generating random favorite/unfavorite sequences where M <= N.
     *
     * @return \Generator<string, array{int, int}>
     */
    public static function favoriteUnfavoriteSequenceDataProvider(): \Generator
    {
        $faker = \Faker\Factory::create();

        for ($i = 0; $i < 20; $i++) {
            $numFavorites = $faker->numberBetween(1, 8);
            $numUnfavorites = $faker->numberBetween(0, $numFavorites);
            yield "iteration_{$i}_fav({$numFavorites})_unfav({$numUnfavorites})" => [
                $numFavorites,
                $numUnfavorites,
            ];
        }
    }

    /**
     * Data provider generating scenarios with more unfavorites than favorites.
     *
     * @return \Generator<string, array{int, int}>
     */
    public static function excessUnfavoriteDataProvider(): \Generator
    {
        $faker = \Faker\Factory::create();

        for ($i = 0; $i < 20; $i++) {
            $numFavorites = $faker->numberBetween(1, 5);
            $numExtraUnfavorites = $faker->numberBetween(1, 3);
            yield "iteration_{$i}_fav({$numFavorites})_extra_unfav({$numExtraUnfavorites})" => [
                $numFavorites,
                $numExtraUnfavorites,
            ];
        }
    }

    /**
     * Data provider generating random sequences for sync command verification.
     *
     * @return \Generator<string, array{int, int}>
     */
    public static function syncCommandDataProvider(): \Generator
    {
        $faker = \Faker\Factory::create();

        for ($i = 0; $i < 20; $i++) {
            $numFavorites = $faker->numberBetween(1, 8);
            $numUnfavorites = $faker->numberBetween(0, $numFavorites);
            yield "iteration_{$i}_fav({$numFavorites})_unfav({$numUnfavorites})" => [
                $numFavorites,
                $numUnfavorites,
            ];
        }
    }
}

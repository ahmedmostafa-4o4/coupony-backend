<?php

namespace Tests\Unit;

use App\Domain\Points\Models\StorePoints;
use App\Domain\Points\Models\StorePointTransaction;
use App\Domain\Points\Models\UserPointTransaction;
use App\Domain\Points\Services\PointsService;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserPoints;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PointsServiceTest extends TestCase
{
    use RefreshDatabase;

    private PointsService $points;

    protected function setUp(): void
    {
        parent::setUp();

        $this->points = app(PointsService::class);
    }

    public function test_add_user_points_creates_row_and_increases_balance_and_lifetime_earned(): void
    {
        $user = User::factory()->create();

        $points = $this->points->addUserPoints($user, 50, 'manual_bonus');

        $this->assertSame(50, (int) $points->current_balance);
        $this->assertSame(50, (int) $points->lifetime_earned);
        $this->assertSame(0, (int) $points->lifetime_spent);
        $this->assertDatabaseHas('user_points', [
            'user_id' => $user->id,
            'current_balance' => 50,
            'lifetime_earned' => 50,
        ]);
        $this->assertSame(1, UserPointTransaction::query()->where('user_id', $user->id)->count());
    }

    public function test_get_or_create_user_points_is_idempotent(): void
    {
        $user = User::factory()->create();

        $first = $this->points->getOrCreateUserPoints($user);
        $second = $this->points->getOrCreateUserPoints($user);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, UserPoints::query()->where('user_id', $user->id)->count());
    }

    public function test_deduct_user_points_decreases_balance_and_increases_lifetime_spent(): void
    {
        $user = User::factory()->create();
        UserPoints::query()->create([
            'user_id' => $user->id,
            'current_balance' => 75,
            'lifetime_earned' => 75,
            'lifetime_spent' => 0,
        ]);

        $points = $this->points->deductUserPoints($user, 25, 'redeem_reward');

        $this->assertSame(50, (int) $points->current_balance);
        $this->assertSame(75, (int) $points->lifetime_earned);
        $this->assertSame(25, (int) $points->lifetime_spent);
        $this->assertDatabaseHas('user_point_transactions', [
            'user_id' => $user->id,
            'type' => 'spend',
            'points' => 25,
            'balance_before' => 75,
            'balance_after' => 50,
        ]);
    }

    public function test_deduct_user_points_fails_if_balance_would_become_negative(): void
    {
        $user = User::factory()->create();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('User points balance cannot be negative.');

        $this->points->deductUserPoints($user, 1, 'redeem_reward');
    }

    public function test_set_user_points_changes_balance_and_records_transaction(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();
        $this->points->addUserPoints($user, 40, 'initial');

        $points = $this->points->setUserPoints($user, 100, 'admin_set', $admin, 'Correction', ['ticket' => 'A-1']);

        $this->assertSame(100, (int) $points->current_balance);
        $this->assertSame(100, (int) $points->lifetime_earned);
        $this->assertDatabaseHas('user_point_transactions', [
            'user_id' => $user->id,
            'admin_user_id' => $admin->id,
            'type' => 'set',
            'points' => 60,
            'balance_before' => 40,
            'balance_after' => 100,
            'reason' => 'admin_set',
            'note' => 'Correction',
        ]);
        $this->assertSame(2, UserPointTransaction::query()->where('user_id', $user->id)->count());
    }

    public function test_every_user_mutation_creates_transaction(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();

        $this->points->addUserPoints($user, 30, 'add');
        $this->points->deductUserPoints($user, 10, 'deduct');
        $this->points->setUserPoints($user, 25, 'set', $admin);

        $this->assertSame(3, UserPointTransaction::query()->where('user_id', $user->id)->count());
    }

    public function test_add_store_points_creates_row_and_increases_balance_and_lifetime_earned(): void
    {
        $store = Store::factory()->create();

        $points = $this->points->addStorePoints($store, 40, 'offer_redeemed');

        $this->assertSame(40, (int) $points->current_balance);
        $this->assertSame(40, (int) $points->lifetime_earned);
        $this->assertSame(0, (int) $points->lifetime_spent);
        $this->assertDatabaseHas('store_points', [
            'store_id' => $store->id,
            'current_balance' => 40,
            'lifetime_earned' => 40,
        ]);
        $this->assertSame(1, StorePointTransaction::query()->where('store_id', $store->id)->count());
    }

    public function test_get_or_create_store_points_is_idempotent(): void
    {
        $store = Store::factory()->create();

        $first = $this->points->getOrCreateStorePoints($store);
        $second = $this->points->getOrCreateStorePoints($store);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, StorePoints::query()->where('store_id', $store->id)->count());
    }

    public function test_deduct_store_points_decreases_balance_and_increases_lifetime_spent(): void
    {
        $store = Store::factory()->create();
        StorePoints::query()->create([
            'store_id' => $store->id,
            'current_balance' => 80,
            'lifetime_earned' => 80,
            'lifetime_spent' => 0,
        ]);

        $points = $this->points->deductStorePoints($store, 30, 'penalty');

        $this->assertSame(50, (int) $points->current_balance);
        $this->assertSame(80, (int) $points->lifetime_earned);
        $this->assertSame(30, (int) $points->lifetime_spent);
        $this->assertDatabaseHas('store_point_transactions', [
            'store_id' => $store->id,
            'type' => 'spend',
            'points' => 30,
            'balance_before' => 80,
            'balance_after' => 50,
        ]);
    }

    public function test_deduct_store_points_fails_if_balance_would_become_negative(): void
    {
        $store = Store::factory()->create();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Store points balance cannot be negative.');

        $this->points->deductStorePoints($store, 1, 'penalty');
    }

    public function test_set_store_points_records_transaction(): void
    {
        $store = Store::factory()->create();
        $admin = User::factory()->create();
        $this->points->addStorePoints($store, 50, 'initial');

        $points = $this->points->setStorePoints($store, 20, 'admin_set', $admin);

        $this->assertSame(20, (int) $points->current_balance);
        $this->assertSame(50, (int) $points->lifetime_earned);
        $this->assertSame(30, (int) $points->lifetime_spent);
        $this->assertDatabaseHas('store_point_transactions', [
            'store_id' => $store->id,
            'admin_user_id' => $admin->id,
            'type' => 'set',
            'points' => 30,
            'balance_before' => 50,
            'balance_after' => 20,
            'reason' => 'admin_set',
        ]);
    }

    public function test_every_store_mutation_creates_transaction(): void
    {
        $store = Store::factory()->create();
        $admin = User::factory()->create();

        $this->points->addStorePoints($store, 30, 'add');
        $this->points->deductStorePoints($store, 10, 'deduct');
        $this->points->setStorePoints($store, 25, 'set', $admin);

        $this->assertSame(3, StorePointTransaction::query()->where('store_id', $store->id)->count());
    }
}

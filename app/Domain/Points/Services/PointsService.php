<?php

namespace App\Domain\Points\Services;

use App\Domain\Points\Models\StorePoints;
use App\Domain\Points\Models\StorePointTransaction;
use App\Domain\Points\Models\UserPointTransaction;
use App\Domain\Product\Models\OfferClaim;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserPoints;
use DomainException;
use Illuminate\Support\Facades\DB;

class PointsService
{
    public function getOrCreateUserPoints(User $user): UserPoints
    {
        return DB::transaction(fn () => $this->lockUserPoints($user)->fresh());
    }

    public function addUserPoints(
        User $user,
        int $points,
        string $reason,
        ?User $admin = null,
        ?Store $store = null,
        ?OfferClaim $claim = null,
        ?string $note = null,
        array $meta = []
    ): UserPoints {
        $this->ensurePositivePoints($points);

        return DB::transaction(function () use ($user, $points, $reason, $admin, $store, $claim, $note, $meta) {
            $userPoints = $this->lockUserPoints($user);
            $balanceBefore = (int) $userPoints->current_balance;
            $balanceAfter = $balanceBefore + $points;

            $userPoints->update([
                'current_balance' => $balanceAfter,
                'lifetime_earned' => (int) $userPoints->lifetime_earned + $points,
            ]);

            $this->recordUserTransaction(
                $user,
                'earn',
                $points,
                $balanceBefore,
                $balanceAfter,
                $reason,
                $admin,
                $store,
                $claim,
                $note,
                $meta
            );

            return $userPoints->fresh();
        });
    }

    public function deductUserPoints(
        User $user,
        int $points,
        string $reason,
        ?User $admin = null,
        ?Store $store = null,
        ?OfferClaim $claim = null,
        ?string $note = null,
        array $meta = []
    ): UserPoints {
        $this->ensurePositivePoints($points);

        return DB::transaction(function () use ($user, $points, $reason, $admin, $store, $claim, $note, $meta) {
            $userPoints = $this->lockUserPoints($user);
            $balanceBefore = (int) $userPoints->current_balance;
            $balanceAfter = $balanceBefore - $points;

            if ($balanceAfter < 0) {
                throw new DomainException('User points balance cannot be negative.');
            }

            $userPoints->update([
                'current_balance' => $balanceAfter,
                'lifetime_spent' => (int) $userPoints->lifetime_spent + $points,
            ]);

            $this->recordUserTransaction(
                $user,
                'spend',
                $points,
                $balanceBefore,
                $balanceAfter,
                $reason,
                $admin,
                $store,
                $claim,
                $note,
                $meta
            );

            return $userPoints->fresh();
        });
    }

    public function setUserPoints(
        User $user,
        int $newBalance,
        string $reason,
        User $admin,
        ?string $note = null,
        array $meta = []
    ): UserPoints {
        $this->ensureNonNegativeBalance($newBalance);

        return DB::transaction(function () use ($user, $newBalance, $reason, $admin, $note, $meta) {
            $userPoints = $this->lockUserPoints($user);
            $balanceBefore = (int) $userPoints->current_balance;
            $difference = $newBalance - $balanceBefore;

            $updates = [
                'current_balance' => $newBalance,
            ];

            if ($difference > 0) {
                $updates['lifetime_earned'] = (int) $userPoints->lifetime_earned + $difference;
            } elseif ($difference < 0) {
                $updates['lifetime_spent'] = (int) $userPoints->lifetime_spent + abs($difference);
            }

            $userPoints->update($updates);

            $this->recordUserTransaction(
                $user,
                'set',
                abs($difference),
                $balanceBefore,
                $newBalance,
                $reason,
                $admin,
                null,
                null,
                $note,
                $meta
            );

            return $userPoints->fresh();
        });
    }

    public function getOrCreateStorePoints(Store $store): StorePoints
    {
        return DB::transaction(fn () => $this->lockStorePoints($store)->fresh());
    }

    public function addStorePoints(
        Store $store,
        int $points,
        string $reason,
        ?User $admin = null,
        ?User $user = null,
        ?OfferClaim $claim = null,
        ?string $note = null,
        array $meta = []
    ): StorePoints {
        $this->ensurePositivePoints($points);

        return DB::transaction(function () use ($store, $points, $reason, $admin, $user, $claim, $note, $meta) {
            $storePoints = $this->lockStorePoints($store);
            $balanceBefore = (int) $storePoints->current_balance;
            $balanceAfter = $balanceBefore + $points;

            $storePoints->update([
                'current_balance' => $balanceAfter,
                'lifetime_earned' => (int) $storePoints->lifetime_earned + $points,
            ]);

            $this->recordStoreTransaction(
                $store,
                'earn',
                $points,
                $balanceBefore,
                $balanceAfter,
                $reason,
                $admin,
                $user,
                $claim,
                $note,
                $meta
            );

            return $storePoints->fresh();
        });
    }

    public function deductStorePoints(
        Store $store,
        int $points,
        string $reason,
        ?User $admin = null,
        ?User $user = null,
        ?OfferClaim $claim = null,
        ?string $note = null,
        array $meta = []
    ): StorePoints {
        $this->ensurePositivePoints($points);

        return DB::transaction(function () use ($store, $points, $reason, $admin, $user, $claim, $note, $meta) {
            $storePoints = $this->lockStorePoints($store);
            $balanceBefore = (int) $storePoints->current_balance;
            $balanceAfter = $balanceBefore - $points;

            if ($balanceAfter < 0) {
                throw new DomainException('Store points balance cannot be negative.');
            }

            $storePoints->update([
                'current_balance' => $balanceAfter,
                'lifetime_spent' => (int) $storePoints->lifetime_spent + $points,
            ]);

            $this->recordStoreTransaction(
                $store,
                'spend',
                $points,
                $balanceBefore,
                $balanceAfter,
                $reason,
                $admin,
                $user,
                $claim,
                $note,
                $meta
            );

            return $storePoints->fresh();
        });
    }

    public function setStorePoints(
        Store $store,
        int $newBalance,
        string $reason,
        User $admin,
        ?string $note = null,
        array $meta = []
    ): StorePoints {
        $this->ensureNonNegativeBalance($newBalance);

        return DB::transaction(function () use ($store, $newBalance, $reason, $admin, $note, $meta) {
            $storePoints = $this->lockStorePoints($store);
            $balanceBefore = (int) $storePoints->current_balance;
            $difference = $newBalance - $balanceBefore;

            $updates = [
                'current_balance' => $newBalance,
            ];

            if ($difference > 0) {
                $updates['lifetime_earned'] = (int) $storePoints->lifetime_earned + $difference;
            } elseif ($difference < 0) {
                $updates['lifetime_spent'] = (int) $storePoints->lifetime_spent + abs($difference);
            }

            $storePoints->update($updates);

            $this->recordStoreTransaction(
                $store,
                'set',
                abs($difference),
                $balanceBefore,
                $newBalance,
                $reason,
                $admin,
                null,
                null,
                $note,
                $meta
            );

            return $storePoints->fresh();
        });
    }

    private function lockUserPoints(User $user): UserPoints
    {
        $userPoints = UserPoints::query()
            ->where('user_id', $user->id)
            ->lockForUpdate()
            ->first();

        if ($userPoints instanceof UserPoints) {
            return $userPoints;
        }

        UserPoints::query()->create([
            'user_id' => $user->id,
        ]);

        return UserPoints::query()
            ->where('user_id', $user->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function lockStorePoints(Store $store): StorePoints
    {
        $storePoints = StorePoints::query()
            ->where('store_id', $store->id)
            ->lockForUpdate()
            ->first();

        if ($storePoints instanceof StorePoints) {
            return $storePoints;
        }

        StorePoints::query()->create([
            'store_id' => $store->id,
        ]);

        return StorePoints::query()
            ->where('store_id', $store->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function recordUserTransaction(
        User $user,
        string $type,
        int $points,
        int $balanceBefore,
        int $balanceAfter,
        string $reason,
        ?User $admin = null,
        ?Store $store = null,
        ?OfferClaim $claim = null,
        ?string $note = null,
        array $meta = []
    ): UserPointTransaction {
        return UserPointTransaction::query()->create([
            'user_id' => $user->id,
            'admin_user_id' => $admin?->id,
            'store_id' => $store?->id,
            'offer_claim_id' => $claim?->id,
            'type' => $type,
            'points' => $points,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reason' => $reason,
            'note' => $note,
            'meta' => $meta === [] ? null : $meta,
        ]);
    }

    private function recordStoreTransaction(
        Store $store,
        string $type,
        int $points,
        int $balanceBefore,
        int $balanceAfter,
        string $reason,
        ?User $admin = null,
        ?User $user = null,
        ?OfferClaim $claim = null,
        ?string $note = null,
        array $meta = []
    ): StorePointTransaction {
        return StorePointTransaction::query()->create([
            'store_id' => $store->id,
            'admin_user_id' => $admin?->id,
            'user_id' => $user?->id,
            'offer_claim_id' => $claim?->id,
            'type' => $type,
            'points' => $points,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reason' => $reason,
            'note' => $note,
            'meta' => $meta === [] ? null : $meta,
        ]);
    }

    private function ensurePositivePoints(int $points): void
    {
        if ($points <= 0) {
            throw new DomainException('Points amount must be greater than zero.');
        }
    }

    private function ensureNonNegativeBalance(int $balance): void
    {
        if ($balance < 0) {
            throw new DomainException('Points balance cannot be negative.');
        }
    }
}

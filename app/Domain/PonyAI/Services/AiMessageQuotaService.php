<?php

namespace App\Domain\PonyAI\Services;

use App\Domain\PonyAI\DTOs\AiQuotaReservation;
use App\Domain\PonyAI\Exceptions\AiDailyLimitReachedException;
use App\Domain\PonyAI\Models\AiMessageUsage;
use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Repositories\SubscriptionRepository;
use App\Domain\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiMessageQuotaService
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptions,
    ) {}

    public function reserveCustomer(User $user): AiQuotaReservation
    {
        if (! app()->environment('production')) {
            return $this->unlimitedReservation('customer', $user->id);
        }

        return $this->reserve(
            'customer',
            $user->id,
            (int) config('pony.quotas.customer_daily_limit', 15)
        );
    }

    public function reserveStore(Store $store): AiQuotaReservation
    {
        return $this->reserve('store', $store->id, $this->storeLimit($store));
    }

    public function release(AiQuotaReservation $reservation): void
    {
        if (! $reservation->reserved) {
            return;
        }

        DB::transaction(function () use ($reservation): void {
            $usage = $this->usageQuery(
                $reservation->usageDate,
                $reservation->subjectType,
                $reservation->subjectId
            )->lockForUpdate()->first();

            if ($usage === null) {
                return;
            }

            $tokenIndex = array_search(
                $reservation->reservationToken,
                $usage->reservation_tokens ?? [],
                true
            );

            if ($tokenIndex === false) {
                return;
            }

            $tokens = $usage->reservation_tokens;
            unset($tokens[$tokenIndex]);
            $usage->reservation_tokens = array_values($tokens);
            $usage->used = max(0, $usage->used - 1);
            $usage->save();
        });
    }

    /**
     * @return array{limit: ?int, used: int, remaining: ?int, resets_at: ?string}
     */
    public function storeQuota(Store $store): array
    {
        $limit = $this->storeLimit($store);
        $usageDate = $this->usageDate();
        $used = (int) $this->usageQuery($usageDate, 'store', $store->id)->value('used');

        return $this->quota($limit, $used, $usageDate);
    }

    private function reserve(string $subjectType, string $subjectId, int $limit): AiQuotaReservation
    {
        $usageDate = $this->usageDate();
        $reservationToken = (string) Str::uuid();

        return DB::transaction(function () use ($subjectType, $subjectId, $limit, $usageDate, $reservationToken) {
            $this->insertUsageRow($usageDate, $subjectType, $subjectId);
            $usage = $this->usageQuery($usageDate, $subjectType, $subjectId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($usage->used >= $limit) {
                throw new AiDailyLimitReachedException($this->quota($limit, $usage->used, $usageDate));
            }

            $tokens = $usage->reservation_tokens ?? [];
            $tokens[] = $reservationToken;
            $usage->reservation_tokens = $tokens;
            $usage->used++;
            $usage->save();

            return new AiQuotaReservation(
                $subjectType,
                $subjectId,
                $usageDate,
                $reservationToken,
                true,
                $this->quota($limit, $usage->used, $usageDate),
            );
        });
    }

    private function insertUsageRow(
        CarbonImmutable $usageDate,
        string $subjectType,
        string $subjectId
    ): void {
        $timestamp = now();
        AiMessageUsage::query()->insertOrIgnore([
            'usage_date' => $usageDate->toDateString(),
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'used' => 0,
            'reservation_tokens' => json_encode([]),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    private function usageQuery(
        CarbonImmutable $usageDate,
        string $subjectType,
        string $subjectId
    ): Builder {
        return AiMessageUsage::query()
            ->where('usage_date', $usageDate->toDateString())
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId);
    }

    private function storeLimit(Store $store): int
    {
        $subscription = $this->subscriptions->findByStore($store->id);
        if ($subscription === null || ! in_array($subscription->status, [
            SubscriptionStatus::TRIAL,
            SubscriptionStatus::ACTIVE,
            SubscriptionStatus::GRACE,
            SubscriptionStatus::DEGRADED,
        ], true)) {
            return 0;
        }

        return (int) ($subscription->plan?->max_ai_messages_per_day ?? 0);
    }

    private function usageDate(): CarbonImmutable
    {
        return CarbonImmutable::now(config('app.timezone'))->startOfDay();
    }

    private function unlimitedReservation(string $subjectType, string $subjectId): AiQuotaReservation
    {
        return new AiQuotaReservation(
            $subjectType,
            $subjectId,
            $this->usageDate(),
            (string) Str::uuid(),
            false,
            $this->unlimitedQuota(),
        );
    }

    /**
     * @return array{limit: null, used: int, remaining: null, resets_at: null}
     */
    private function unlimitedQuota(): array
    {
        return [
            'limit' => null,
            'used' => 0,
            'remaining' => null,
            'resets_at' => null,
        ];
    }

    /**
     * @return array{limit: int, used: int, remaining: int, resets_at: string}
     */
    private function quota(int $limit, int $used, CarbonImmutable $usageDate): array
    {
        return [
            'limit' => $limit,
            'used' => $used,
            'remaining' => max(0, $limit - $used),
            'resets_at' => $usageDate->addDay()->toIso8601String(),
        ];
    }
}

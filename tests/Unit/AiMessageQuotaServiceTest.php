<?php

namespace Tests\Unit;

use App\Domain\PonyAI\Exceptions\AiDailyLimitReachedException;
use App\Domain\PonyAI\Models\AiMessageUsage;
use App\Domain\PonyAI\Services\AiMessageQuotaService;
use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionPlan;
use App\Domain\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class AiMessageQuotaServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasColumn('subscription_plans', 'max_ai_messages_per_day')) {
            Schema::table('subscription_plans', function (Blueprint $table): void {
                $table->unsignedInteger('max_ai_messages_per_day')->nullable();
            });
        }

        config([
            'app.timezone' => 'Africa/Cairo',
            'pony.quotas.customer_daily_limit' => 2,
        ]);
        $this->app->detectEnvironment(fn (): string => 'production');
        CarbonImmutable::setTestNow('2026-07-02 12:00:00', 'Africa/Cairo');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_production_customer_reservations_stop_at_daily_limit(): void
    {
        $user = User::factory()->create();
        $service = app(AiMessageQuotaService::class);

        $first = $service->reserveCustomer($user);
        $second = $service->reserveCustomer($user);

        $this->assertTrue($first->reserved);
        $this->assertSame(1, $first->quota['used']);
        $this->assertSame(0, $second->quota['remaining']);

        try {
            $service->reserveCustomer($user);
            $this->fail('Expected the daily quota to be exhausted.');
        } catch (AiDailyLimitReachedException $exception) {
            $this->assertSame(2, $exception->quota['limit']);
            $this->assertSame(2, $exception->quota['used']);
            $this->assertSame(0, $exception->quota['remaining']);
        }

        $this->assertDatabaseHas('ai_message_usages', [
            'subject_type' => 'customer',
            'subject_id' => $user->id,
            'used' => 2,
        ]);
    }

    public function test_releasing_same_reservation_twice_does_not_release_another_reservation(): void
    {
        $service = app(AiMessageQuotaService::class);
        $user = User::factory()->create();
        $firstReservation = $service->reserveCustomer($user);
        $service->reserveCustomer($user);

        $service->release($firstReservation);
        $service->release($firstReservation);

        $this->assertSame(1, AiMessageUsage::query()->sole()->used);
    }

    public function test_customer_usage_is_isolated_by_user(): void
    {
        $service = app(AiMessageQuotaService::class);
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $service->reserveCustomer($firstUser);
        $secondReservation = $service->reserveCustomer($secondUser);

        $this->assertSame(1, $secondReservation->quota['used']);
        $this->assertCount(2, AiMessageUsage::all());
    }

    public function test_store_usage_is_shared_within_a_store_and_isolated_between_stores(): void
    {
        $service = app(AiMessageQuotaService::class);
        $firstStore = $this->createSubscribedStore(3);
        $secondStore = $this->createSubscribedStore(3);

        $service->reserveStore($firstStore);
        $sharedReservation = $service->reserveStore($firstStore->fresh());
        $isolatedReservation = $service->reserveStore($secondStore);

        $this->assertSame(2, $sharedReservation->quota['used']);
        $this->assertSame(1, $isolatedReservation->quota['used']);
        $this->assertCount(2, AiMessageUsage::where('subject_type', 'store')->get());
    }

    public function test_store_quota_reports_current_plan_limit_and_usage(): void
    {
        $store = $this->createSubscribedStore(3);
        $service = app(AiMessageQuotaService::class);
        $service->reserveStore($store);

        $quota = $service->storeQuota($store);

        $this->assertSame(3, $quota['limit']);
        $this->assertSame(1, $quota['used']);
        $this->assertSame(2, $quota['remaining']);
        $this->assertSame('2026-07-03T00:00:00+03:00', $quota['resets_at']);
    }

    public function test_store_without_subscription_has_zero_quota(): void
    {
        $this->assertStoreIsBlocked(Store::factory()->create());
    }

    #[DataProvider('blockedSubscriptionStatusProvider')]
    public function test_store_with_blocked_subscription_status_has_zero_quota(SubscriptionStatus $status): void
    {
        $this->assertStoreIsBlocked($this->createSubscribedStore(3, $status));
    }

    public static function blockedSubscriptionStatusProvider(): array
    {
        return [
            'suspended' => [SubscriptionStatus::SUSPENDED],
            'archived' => [SubscriptionStatus::ARCHIVED],
        ];
    }

    public function test_store_with_zero_plan_limit_has_zero_quota(): void
    {
        $this->assertStoreIsBlocked($this->createSubscribedStore(0));
    }

    #[DataProvider('eligibleSubscriptionStatusProvider')]
    public function test_store_with_eligible_subscription_status_uses_plan_quota(SubscriptionStatus $status): void
    {
        $quota = app(AiMessageQuotaService::class)->storeQuota(
            $this->createSubscribedStore(3, $status)
        );

        $this->assertSame(3, $quota['limit']);
    }

    public static function eligibleSubscriptionStatusProvider(): array
    {
        return [
            'trial' => [SubscriptionStatus::TRIAL],
            'active' => [SubscriptionStatus::ACTIVE],
            'grace' => [SubscriptionStatus::GRACE],
            'degraded' => [SubscriptionStatus::DEGRADED],
        ];
    }

    public function test_cancelled_active_subscription_remains_eligible_until_status_changes(): void
    {
        $store = $this->createSubscribedStore(3);
        $store->subscription()->update(['cancelled_at' => now()]);

        $this->assertSame(3, app(AiMessageQuotaService::class)->storeQuota($store)['limit']);
    }

    public function test_usage_resets_at_midnight_in_application_timezone(): void
    {
        $user = User::factory()->create();
        $service = app(AiMessageQuotaService::class);
        CarbonImmutable::setTestNow('2026-07-02 23:59:59', 'Africa/Cairo');
        $service->reserveCustomer($user);

        CarbonImmutable::setTestNow('2026-07-03 00:00:00', 'Africa/Cairo');
        $reservation = $service->reserveCustomer($user);

        $this->assertSame('2026-07-03', $reservation->usageDate->toDateString());
        $this->assertSame(1, $reservation->quota['used']);
        $this->assertCount(2, AiMessageUsage::all());
    }

    public function test_non_production_customer_quota_is_unlimited_and_not_persisted(): void
    {
        $this->app->detectEnvironment(fn (): string => 'testing');
        $service = app(AiMessageQuotaService::class);

        $reservation = $service->reserveCustomer(User::factory()->create());

        $this->assertFalse($reservation->reserved);
        $this->assertSame([
            'limit' => null,
            'used' => 0,
            'remaining' => null,
            'resets_at' => null,
        ], $reservation->quota);
        $this->assertDatabaseCount('ai_message_usages', 0);
    }

    public function test_competing_reservations_cannot_exceed_limit_or_duplicate_subject_day_row(): void
    {
        $databasePath = tempnam(sys_get_temp_dir(), 'ai-quota-');
        $startSignalPath = $databasePath.'.start';
        $readySignalPrefix = $databasePath.'.ready';
        $subjectId = (string) \Illuminate\Support\Str::uuid();
        $workers = [];

        $database = new PDO('sqlite:'.$databasePath);
        $database->exec($this->concurrencyTableSql());

        try {
            // This verifies atomic outcomes on SQLite, the database supported by the local test suite.
            $workers = $this->startReservationWorkers(
                $databasePath,
                $startSignalPath,
                $readySignalPrefix,
                $subjectId,
                6
            );
            $this->waitForWorkersReady($readySignalPrefix, 6);
            touch($startSignalPath);
            $outcomes = array_map(function (Process $worker): string {
                $worker->wait();
                $this->assertTrue($worker->isSuccessful(), $worker->getErrorOutput());

                return trim($worker->getOutput());
            }, $workers);

            $usage = $database->query('SELECT used FROM ai_message_usages')->fetchColumn();
            $rowCount = $database->query('SELECT COUNT(*) FROM ai_message_usages')->fetchColumn();

            $this->assertSame(1, count(array_filter($outcomes, fn (string $outcome) => $outcome === 'reserved')));
            $this->assertSame(5, count(array_filter($outcomes, fn (string $outcome) => $outcome === 'limited')));
            $this->assertSame(1, (int) $rowCount);
            $this->assertSame(1, (int) $usage);
        } finally {
            foreach ($workers as $worker) {
                if ($worker->isRunning()) {
                    $worker->stop(1);
                }
            }

            $database = null;
            @unlink($startSignalPath);
            foreach (glob($readySignalPrefix.'.*') ?: [] as $readySignalPath) {
                @unlink($readySignalPath);
            }
            @unlink($databasePath);
            @unlink($databasePath.'-shm');
            @unlink($databasePath.'-wal');
        }
    }

    /**
     * @return list<Process>
     */
    private function startReservationWorkers(
        string $databasePath,
        string $startSignalPath,
        string $readySignalPrefix,
        string $subjectId,
        int $count
    ): array {
        $workers = [];

        foreach (range(1, $count) as $workerNumber) {
            $worker = new Process([PHP_BINARY, '-r', $this->reservationWorkerCode(
                $databasePath,
                $startSignalPath,
                $readySignalPrefix.'.'.$workerNumber,
                $subjectId
            )], base_path());
            $worker->setTimeout(20);
            $worker->start();
            $workers[] = $worker;
        }

        return $workers;
    }

    private function reservationWorkerCode(
        string $databasePath,
        string $startSignalPath,
        string $readySignalPath,
        string $subjectId
    ): string {
        $autoloadPath = var_export(base_path('vendor/autoload.php'), true);
        $bootstrapPath = var_export(base_path('bootstrap/app.php'), true);
        $databasePath = var_export($databasePath, true);
        $startSignalPath = var_export($startSignalPath, true);
        $readySignalPath = var_export($readySignalPath, true);
        $subjectId = var_export($subjectId, true);

        return <<<PHP
        require {$autoloadPath};
        \$app = require {$bootstrapPath};
        \$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
        \$app->detectEnvironment(fn () => 'production');
        config([
            'app.timezone' => 'UTC',
            'pony.quotas.customer_daily_limit' => 1,
            'database.default' => 'quota_concurrency',
            'database.connections.quota_concurrency' => [
                'driver' => 'sqlite',
                'database' => {$databasePath},
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
        ]);
        Illuminate\\Support\\Facades\\DB::purge('quota_concurrency');
        Illuminate\\Support\\Facades\\DB::connection()->statement('PRAGMA busy_timeout = 10000');
        touch({$readySignalPath});
        while (! file_exists({$startSignalPath})) { usleep(1000); }
        \$user = new App\\Domain\\User\\Models\\User();
        \$user->id = {$subjectId};
        try {
            \$app->make(App\\Domain\\PonyAI\\Services\\AiMessageQuotaService::class)->reserveCustomer(\$user);
            echo 'reserved';
        } catch (App\\Domain\\PonyAI\\Exceptions\\AiDailyLimitReachedException) {
            echo 'limited';
        }
        PHP;
    }

    private function waitForWorkersReady(string $readySignalPrefix, int $workerCount): void
    {
        $deadline = microtime(true) + 10;

        while (count(glob($readySignalPrefix.'.*') ?: []) < $workerCount) {
            if (microtime(true) >= $deadline) {
                $this->fail('Timed out waiting for quota workers to become ready.');
            }

            usleep(1000);
        }
    }

    private function concurrencyTableSql(): string
    {
        return <<<'SQL'
        CREATE TABLE ai_message_usages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usage_date DATE NOT NULL,
            subject_type VARCHAR(20) NOT NULL,
            subject_id CHAR(36) NOT NULL,
            used INTEGER NOT NULL DEFAULT 0,
            reservation_tokens TEXT,
            created_at DATETIME,
            updated_at DATETIME,
            CONSTRAINT ai_usage_subject_day_unique UNIQUE (usage_date, subject_type, subject_id)
        )
        SQL;
    }

    private function assertStoreIsBlocked(Store $store): void
    {
        $service = app(AiMessageQuotaService::class);

        $this->assertSame([
            'limit' => 0,
            'used' => 0,
            'remaining' => 0,
            'resets_at' => '2026-07-03T00:00:00+03:00',
        ], $service->storeQuota($store));

        try {
            $service->reserveStore($store);
            $this->fail('Expected store quota to be blocked.');
        } catch (AiDailyLimitReachedException $exception) {
            $this->assertSame(0, $exception->quota['limit']);
        }
    }

    private function createSubscribedStore(
        int $dailyLimit,
        SubscriptionStatus $status = SubscriptionStatus::ACTIVE
    ): Store {
        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create([
            'max_ai_messages_per_day' => $dailyLimit,
        ]);

        Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'status' => $status,
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        return $store;
    }
}

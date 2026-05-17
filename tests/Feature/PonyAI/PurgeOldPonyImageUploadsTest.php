<?php

namespace Tests\Feature\PonyAI;

use App\Domain\PonyAI\Jobs\PurgeOldPonyImageUploadsJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurgeOldPonyImageUploadsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function makeFile(string $path, int $modifiedDaysAgo): void
    {
        Storage::disk('local')->put($path, 'bytes');

        $absolute = Storage::disk('local')->path($path);
        touch($absolute, now()->subDays($modifiedDaysAgo)->getTimestamp());
        clearstatcache(true, $absolute);
    }

    public function test_purge_removes_files_older_than_retention_and_keeps_fresh_ones(): void
    {
        $this->makeFile('pony/queries/user-1/old.jpg', 30);
        $this->makeFile('pony/queries/user-1/older.jpg', 60);
        $this->makeFile('pony/queries/user-2/recent.jpg', 1);

        $deleted = (new PurgeOldPonyImageUploadsJob(retentionDays: 14))->handle();

        $this->assertSame(2, $deleted);
        $this->assertFalse(Storage::disk('local')->exists('pony/queries/user-1/old.jpg'));
        $this->assertFalse(Storage::disk('local')->exists('pony/queries/user-1/older.jpg'));
        $this->assertTrue(Storage::disk('local')->exists('pony/queries/user-2/recent.jpg'));
    }

    public function test_purge_never_touches_files_outside_pony_queries_prefix(): void
    {
        $this->makeFile('other/area/ancient.jpg', 365);
        $this->makeFile('pony/queries/user-x/old.jpg', 30);

        (new PurgeOldPonyImageUploadsJob(retentionDays: 14))->handle();

        $this->assertTrue(Storage::disk('local')->exists('other/area/ancient.jpg'));
        $this->assertFalse(Storage::disk('local')->exists('pony/queries/user-x/old.jpg'));
    }

    public function test_purge_is_idempotent_when_directory_does_not_exist(): void
    {
        $deleted = (new PurgeOldPonyImageUploadsJob())->handle();

        $this->assertSame(0, $deleted);
    }

    public function test_zero_retention_removes_everything_under_pony_queries(): void
    {
        $this->makeFile('pony/queries/user-1/just-now.jpg', 0);
        $this->makeFile('pony/queries/user-2/old.jpg', 30);

        // Use 0 days but also reach back a moment so newly-created files are eligible.
        touch(Storage::disk('local')->path('pony/queries/user-1/just-now.jpg'), now()->subSeconds(2)->getTimestamp());

        $deleted = (new PurgeOldPonyImageUploadsJob(retentionDays: 0))->handle();

        $this->assertSame(2, $deleted);
    }

    public function test_command_dispatches_the_job_by_default(): void
    {
        Bus::fake();

        $this->artisan('pony:purge-image-queries')
            ->expectsOutputToContain('Purge job dispatched.')
            ->assertSuccessful();

        Bus::assertDispatched(PurgeOldPonyImageUploadsJob::class);
    }

    public function test_command_sync_flag_runs_inline_and_reports_count(): void
    {
        $this->makeFile('pony/queries/user-1/a.jpg', 30);
        $this->makeFile('pony/queries/user-1/b.jpg', 30);

        $this->artisan('pony:purge-image-queries', ['--sync' => true, '--days' => 14])
            ->expectsOutputToContain('Deleted 2 file(s).')
            ->assertSuccessful();
    }

    public function test_days_option_overrides_config(): void
    {
        config()->set('pony.image_retention_days', 365);

        $this->makeFile('pony/queries/user-1/three-day-old.jpg', 3);

        $this->artisan('pony:purge-image-queries', ['--sync' => true, '--days' => 1])
            ->assertSuccessful();

        $this->assertFalse(Storage::disk('local')->exists('pony/queries/user-1/three-day-old.jpg'));
    }
}

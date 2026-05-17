<?php

namespace App\Application\Console\Commands;

use App\Domain\PonyAI\Jobs\PurgeOldPonyImageUploadsJob;
use Illuminate\Console\Command;

class PonyPurgeImageQueries extends Command
{
    protected $signature = 'pony:purge-image-queries
        {--days= : Override the retention window (defaults to pony.image_retention_days)}
        {--sync : Run the job inline instead of dispatching it onto the queue}';

    protected $description = 'Delete Pony AI query-image uploads older than the configured retention window.';

    public function handle(): int
    {
        $days = $this->option('days');
        $retention = $days !== null ? (int) $days : null;

        if ($this->option('sync')) {
            $deleted = (new PurgeOldPonyImageUploadsJob($retention))->handle();
            $this->info("Deleted {$deleted} file(s).");

            return self::SUCCESS;
        }

        PurgeOldPonyImageUploadsJob::dispatch($retention);
        $this->info('Purge job dispatched.');

        return self::SUCCESS;
    }
}

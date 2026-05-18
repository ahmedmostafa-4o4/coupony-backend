<?php

namespace App\Domain\PonyAI\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Walks the LOCAL disk pony/queries/* tree and removes files older than the
 * configured retention. Safe to run multiple times - it only ever touches the
 * pony/queries/ prefix and never the rest of the application's storage.
 *
 * Run via `php artisan pony:purge-image-queries` (one-shot) or schedule the
 * command in your cron / Laravel scheduler.
 */
class PurgeOldPonyImageUploadsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 300;

    public function __construct(public readonly ?int $retentionDays = null) {}

    public function handle(): int
    {
        $disk = Storage::disk('local');

        if (! $disk->exists('pony/queries')) {
            return 0;
        }

        $days = max(0, (int) ($this->retentionDays ?? config('pony.image_retention_days', 14)));
        $cutoff = Carbon::now()->subDays($days)->getTimestamp();

        $deleted = 0;
        $kept = 0;

        foreach ($disk->allFiles('pony/queries') as $file) {
            $modifiedAt = (int) $disk->lastModified($file);

            if ($modifiedAt > $cutoff) {
                $kept++;

                continue;
            }

            if ($disk->delete($file)) {
                $deleted++;
            }
        }

        $channel = (string) config('pony.logging.channel', 'pony');
        Log::channel($channel)->info('pony.purge_image_queries', [
            'retention_days' => $days,
            'deleted' => $deleted,
            'kept' => $kept,
        ]);

        return $deleted;
    }
}

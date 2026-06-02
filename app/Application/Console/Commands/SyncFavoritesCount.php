<?php

namespace App\Application\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncFavoritesCount extends Command
{
    protected $signature = 'explore:sync-favorites-count';

    protected $description = 'Synchronize favorites_count column with actual counts from product_favorites table';

    public function handle(): int
    {
        $this->info('Syncing favorites_count for all products...');

        $updated = 0;

        DB::table('products')->orderBy('id')->chunk(1000, function ($products) use (&$updated) {
            $productIds = $products->pluck('id')->toArray();

            $counts = DB::table('product_favorites')
                ->whereIn('product_id', $productIds)
                ->groupBy('product_id')
                ->select('product_id', DB::raw('COUNT(*) as favorites_count'))
                ->pluck('favorites_count', 'product_id');

            foreach ($products as $product) {
                $actualCount = $counts[$product->id] ?? 0;

                if ((int) $product->favorites_count !== $actualCount) {
                    DB::table('products')
                        ->where('id', $product->id)
                        ->update(['favorites_count' => $actualCount]);

                    $updated++;
                }
            }
        });

        $this->info("Done. Updated {$updated} products.");

        return self::SUCCESS;
    }
}

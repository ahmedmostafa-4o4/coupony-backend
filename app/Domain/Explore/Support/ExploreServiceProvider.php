<?php

namespace App\Domain\Explore\Support;

use App\Application\Console\Commands\SyncFavoritesCount;
use Illuminate\Support\ServiceProvider;

class ExploreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncFavoritesCount::class,
            ]);
        }
    }
}

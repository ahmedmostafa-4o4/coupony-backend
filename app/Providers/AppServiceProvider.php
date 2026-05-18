<?php

namespace App\Providers;

use App\Domain\Product\Models\Product;
use App\Domain\Store\Models\Store;
use App\Policies\PonyAISellerPolicy;
use App\Policies\ProductPolicy;
use App\Policies\StorePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Store::class, StorePolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);

        Gate::define('pony-ai-seller-chat', [PonyAISellerPolicy::class, 'chat']);
    }
}

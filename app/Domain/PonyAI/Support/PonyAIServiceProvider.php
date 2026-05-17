<?php

namespace App\Domain\PonyAI\Support;

use App\Application\Console\Commands\PonyEmbedImages;
use App\Application\Console\Commands\PonyEmbedProducts;
use App\Application\Console\Commands\PonyGeminiPing;
use App\Application\Console\Commands\PonyPurgeImageQueries;
use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\Listeners\QueueProductReembeddingOnRevisionApproval;
use App\Domain\PonyAI\Services\GeminiFakeClient;
use App\Domain\PonyAI\Services\GeminiHttpClient;
use App\Domain\Product\Events\ProductRevisionApproved;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class PonyAIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GeminiClient::class, function (Application $app): GeminiClient {
            $config = (array) $app['config']->get('services.gemini', []);
            $shouldFake = $app->environment('testing') || (bool) ($config['fake'] ?? false);

            if ($shouldFake) {
                return new GeminiFakeClient();
            }

            return new GeminiHttpClient($config);
        });

        $this->app->alias(GeminiClient::class, 'pony.gemini');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PonyGeminiPing::class,
                PonyEmbedProducts::class,
                PonyEmbedImages::class,
                PonyPurgeImageQueries::class,
            ]);
        }

        Event::listen(
            ProductRevisionApproved::class,
            [QueueProductReembeddingOnRevisionApproval::class, 'handle'],
        );
    }
}

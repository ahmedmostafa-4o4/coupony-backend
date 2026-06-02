<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\TelescopeServiceProvider::class,
    App\Domain\PonyAI\Support\PonyAIServiceProvider::class,
    App\Domain\Subscription\Support\SubscriptionServiceProvider::class,
    App\Domain\Explore\Support\ExploreServiceProvider::class,
];

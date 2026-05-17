<?php

namespace Tests\Feature\PonyAI;

use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\Services\GeminiFakeClient;
use App\Domain\PonyAI\Services\GeminiHttpClient;
use Tests\TestCase;

class ServiceProviderBindingTest extends TestCase
{
    public function test_container_resolves_fake_client_in_testing_environment(): void
    {
        $resolved = $this->app->make(GeminiClient::class);

        $this->assertInstanceOf(GeminiFakeClient::class, $resolved);
    }

    public function test_singleton_returns_the_same_instance(): void
    {
        $first = $this->app->make(GeminiClient::class);
        $second = $this->app->make(GeminiClient::class);

        $this->assertSame($first, $second);
    }

    public function test_alias_is_registered(): void
    {
        $this->assertSame(
            $this->app->make(GeminiClient::class),
            $this->app->make('pony.gemini'),
        );
    }

    public function test_http_client_resolves_when_fake_flag_is_off_and_not_testing(): void
    {
        $this->app['env'] = 'production';

        config()->set('services.gemini.fake', false);
        config()->set('services.gemini.api_key', 'whatever');

        $this->app->forgetInstance(GeminiClient::class);

        $resolved = $this->app->make(GeminiClient::class);

        $this->assertInstanceOf(GeminiHttpClient::class, $resolved);
    }

    public function test_gemini_ping_command_succeeds_against_fake(): void
    {
        /** @var GeminiFakeClient $fake */
        $fake = $this->app->make(GeminiClient::class);
        $fake->queueText('pong');

        $this->artisan('pony:gemini-ping', ['--prompt' => 'Reply: pong'])
            ->expectsOutputToContain('Pony AI Gemini ping succeeded.')
            ->expectsOutputToContain('Reply: pong')
            ->assertSuccessful();
    }
}

<?php

namespace Tests\Unit\PonyAI;

use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\Services\GeminiFakeClient;
use App\Domain\PonyAI\Services\Pipeline\AnswerComposer;
use App\Domain\Product\Models\Product;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnswerComposerTest extends TestCase
{
    use RefreshDatabase;

    private function fake(): GeminiFakeClient
    {
        /** @var GeminiFakeClient $client */
        $client = $this->app->make(GeminiClient::class);

        return $client;
    }

    private function product(): Product
    {
        $store = Store::factory()->create([
            'owner_user_id' => User::factory()->create()->id,
        ]);

        return Product::factory()->create([
            'store_id' => $store->id,
            'title' => 'Test Product',
            'base_price' => 200,
            'currency' => 'EGP',
        ])->fresh(['offer']);
    }

    public function test_compose_passes_candidate_block_and_system_prompt(): void
    {
        $candidate = $this->product();

        $this->fake()->queueJson([
            'message' => 'Here is a recommendation.',
            'product_ids' => [$candidate->id],
            'offer_ids' => [],
        ]);

        $reply = $this->app->make(AnswerComposer::class)->compose(
            'I want something nice',
            collect([$candidate]),
        );

        $this->assertSame('Here is a recommendation.', $reply['message']);
        $this->assertSame([$candidate->id], $reply['product_ids']);

        $call = $this->fake()->calls[0];
        $this->assertSame('generateJson', $call['method']);
        $this->assertStringContainsString($candidate->id, $call['prompt']);
        $this->assertStringContainsString('Test Product', $call['prompt']);
        $this->assertStringContainsString('200', $call['prompt']);
        $this->assertStringContainsString('Pony', $call['options']['system_instruction']);
        $this->assertStringContainsString('only recommend products from the candidate list', $call['options']['system_instruction']);
    }

    public function test_dedupes_and_drops_blank_ids_from_payload(): void
    {
        $a = $this->product();
        $b = $this->product();

        $this->fake()->queueJson([
            'message' => 'Two picks.',
            'product_ids' => [$a->id, $a->id, '', $b->id, '   '],
            'offer_ids' => [],
        ]);

        $reply = $this->app->make(AnswerComposer::class)->compose('hi', collect([$a, $b]));

        $this->assertSame([$a->id, $b->id], $reply['product_ids']);
    }

    public function test_empty_message_falls_back_to_safe_text_and_keeps_candidate_ids(): void
    {
        $a = $this->product();

        $this->fake()->queueJson([
            'message' => '',
            'product_ids' => [$a->id],
            'offer_ids' => [],
        ]);

        $reply = $this->app->make(AnswerComposer::class)->compose('hi', collect([$a]));

        $this->assertNotSame('', $reply['message']);
        $this->assertSame([$a->id], $reply['product_ids']);
    }

    public function test_empty_candidates_yields_polite_no_match_message(): void
    {
        $reply = $this->app->make(AnswerComposer::class)->compose('something rare', collect());

        $this->assertNotSame('', $reply['message']);
        $this->assertSame([], $reply['product_ids']);
        $this->assertSame([], $reply['offer_ids']);
        // No Gemini call should have been made for an empty candidate set... actually composer always
        // calls Gemini; this just confirms the fallback path when payload returns nothing useful.
    }
}

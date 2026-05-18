<?php

namespace Tests\Unit\PonyAI;

use App\Domain\PonyAI\Services\GeminiFakeClient;
use Tests\TestCase;

class GeminiFakeClientTest extends TestCase
{
    public function test_queued_text_is_returned_in_order(): void
    {
        $client = new GeminiFakeClient;
        $client->queueText('first')->queueText('second');

        $this->assertSame('first', $client->generateText('a')->text);
        $this->assertSame('second', $client->generateText('b')->text);
    }

    public function test_queued_json_is_decoded(): void
    {
        $client = new GeminiFakeClient;
        $client->queueJson(['intent' => 'search', 'category' => 'shoes']);

        $result = $client->generateJson('extract');

        $this->assertSame(['intent' => 'search', 'category' => 'shoes'], $result->decodeJson());
    }

    public function test_queued_embedding_returns_floats(): void
    {
        $client = new GeminiFakeClient;
        $client->queueEmbedding([0.1, 0.2, 0.3]);

        $this->assertSame([0.1, 0.2, 0.3], $client->embedText('hi'));
    }

    public function test_default_fallback_is_deterministic(): void
    {
        $client = new GeminiFakeClient;

        $first = $client->embedText('coupony');
        $second = (new GeminiFakeClient)->embedText('coupony');

        $this->assertSame($first, $second);
        $this->assertCount(16, $first);
        $this->assertContainsOnly('float', $first);
    }

    public function test_calls_are_tracked(): void
    {
        $client = new GeminiFakeClient;
        $client->generateText('hello');
        $client->describeImage('bytes', 'image/png', 'describe please');

        $this->assertCount(2, $client->calls);
        $this->assertSame('generateText', $client->calls[0]['method']);
        $this->assertSame('hello', $client->calls[0]['prompt']);
        $this->assertSame('describeImage', $client->calls[1]['method']);
        $this->assertSame('image/png', $client->calls[1]['mime']);
        $this->assertSame(5, $client->calls[1]['bytes_length']);
    }

    public function test_embed_image_falls_back_to_deterministic_vector(): void
    {
        $client = new GeminiFakeClient;

        $vector = $client->embedImage('rawbytes', 'image/jpeg');

        $this->assertCount(16, $vector);
        $this->assertContainsOnly('float', $vector);
    }
}

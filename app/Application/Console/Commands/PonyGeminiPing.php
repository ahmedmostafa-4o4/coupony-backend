<?php

namespace App\Application\Console\Commands;

use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\Exceptions\GeminiException;
use Illuminate\Console\Command;

class PonyGeminiPing extends Command
{
    protected $signature = 'pony:gemini-ping {--prompt=Reply with the single word: pong.}';

    protected $description = 'Smoke-test the Pony AI Gemini client by sending a short prompt and printing the reply.';

    public function handle(GeminiClient $client): int
    {
        $prompt = (string) $this->option('prompt');

        try {
            $result = $client->generateText($prompt, ['max_output_tokens' => 32]);
        } catch (GeminiException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Pony AI Gemini ping succeeded.');
        $this->line('Model: '.($result->model ?? 'unknown'));
        $this->line('Reply: '.trim($result->text));

        if ($result->promptTokens !== null || $result->completionTokens !== null) {
            $this->line(sprintf(
                'Tokens: prompt=%s, completion=%s',
                $result->promptTokens ?? '?',
                $result->completionTokens ?? '?',
            ));
        }

        return self::SUCCESS;
    }
}

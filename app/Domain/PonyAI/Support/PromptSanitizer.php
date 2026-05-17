<?php

namespace App\Domain\PonyAI\Support;

/**
 * Sanitize free-text the user typed before it is forwarded to Gemini.
 *
 * The goal is to make obvious prompt-injection attempts look uninteresting
 * to the model - control bytes are stripped, known role/system tokens are
 * neutralised, and a few common jailbreak markers ("ignore previous
 * instructions", "you are now a ...", etc.) are softened.
 *
 * This does NOT replace the grounding guarantee (the model is still allowed
 * to recommend only ids in the candidate set), it just keeps the prompt
 * itself clean so the model doesn't burn tokens on adversarial wording.
 *
 * The user's original message is still persisted verbatim to
 * pony_messages.content - sanitization only applies to the prompt that
 * actually leaves the backend.
 */
class PromptSanitizer
{
    /**
     * Tokens and phrases that should be neutralised when they appear in user input.
     * Each pattern uses /u (unicode) and /i (case-insensitive); we replace matches
     * with "[redacted]" rather than deleting them so the result is still readable
     * and a human can audit the log.
     */
    private const PATTERNS = [
        // Common model-turn / role markers
        '/<\|im_start\|>|<\|im_end\|>/u',
        '/<\|system\|>|<\|user\|>|<\|assistant\|>/u',
        '/<\/?system>|<\/?assistant>|<\/?user>/iu',
        '/<\/s>|<s>/u',
        '/\[\/?INST\]/iu',

        // Common jailbreak phrasing
        '/ignore (all )?(previous|prior|earlier) (instructions|prompts|rules)/iu',
        '/disregard (the |any |all )?(previous|prior|earlier) (instructions|prompts|rules)/iu',
        '/forget (the |any |all )?(previous|prior|earlier) (instructions|prompts|rules)/iu',
        '/you are (now )?(a |an )?(?:DAN|jailbroken|unrestricted|developer mode)/iu',
        '/act as (a |an )?(?:admin|system|developer|root)/iu',
        '/(?:reveal|print|show)(?: me)? (?:the |your )?(?:system prompt|instructions|rules)/iu',
    ];

    public function sanitize(string $input): string
    {
        // Strip C0 control bytes (except tab + newline) and the DEL byte.
        $stripped = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $input) ?? $input;

        foreach (self::PATTERNS as $pattern) {
            $stripped = preg_replace($pattern, '[redacted]', $stripped) ?? $stripped;
        }

        // Collapse multiple spaces created by replacements; preserve newlines.
        $stripped = preg_replace('/[ \t]{2,}/u', ' ', $stripped) ?? $stripped;

        return trim($stripped);
    }
}

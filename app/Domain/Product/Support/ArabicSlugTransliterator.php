<?php

namespace App\Domain\Product\Support;

class ArabicSlugTransliterator
{
    public function transliterate(?string $value, string $separator = '-'): string
    {
        if (! is_string($value) || trim($value) === '') {
            return '';
        }

        $normalized = $this->normalize($value);

        if ($normalized === '') {
            return '';
        }

        $tokens = preg_split('/[\s\-_]+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $transliterated = [];

        foreach ($tokens as $token) {
            $token = $this->transliterateToken($token);

            if ($token !== '') {
                $transliterated[] = $token;
            }
        }

        return implode($separator, $transliterated);
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = str_replace(['أ', 'إ', 'آ'], 'ا', $value);
        $value = preg_replace('/[^\p{Arabic}\pL\pN\s\-_]+/u', ' ', $value) ?? '';
        $value = preg_replace('/[\s_]+/u', ' ', $value) ?? '';

        return trim($value);
    }

    private function transliterateToken(string $token): string
    {
        $override = config("product_identifiers.token_overrides.{$token}");

        if (is_string($override) && $override !== '') {
            return $this->sanitizeAscii($override);
        }

        $result = '';

        foreach (mb_str_split($token) as $character) {
            if (preg_match('/[a-z0-9]/', $character) === 1) {
                $result .= $character;

                continue;
            }

            $result .= $this->characterMap()[$character] ?? '';
        }

        return $this->sanitizeAscii($result);
    }

    private function sanitizeAscii(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '', $value) ?? '';

        return trim($value);
    }

    private function characterMap(): array
    {
        return [
            'ا' => 'a',
            'ب' => 'b',
            'ت' => 't',
            'ث' => 'th',
            'ج' => 'g',
            'ح' => 'h',
            'خ' => 'kh',
            'د' => 'd',
            'ذ' => 'z',
            'ر' => 'r',
            'ز' => 'z',
            'س' => 's',
            'ش' => 'sh',
            'ص' => 's',
            'ض' => 'd',
            'ط' => 't',
            'ظ' => 'z',
            'ع' => 'a',
            'غ' => 'gh',
            'ف' => 'f',
            'ق' => 'q',
            'ك' => 'k',
            'ل' => 'l',
            'م' => 'm',
            'ن' => 'n',
            'ه' => 'h',
            'و' => 'w',
            'ؤ' => 'w',
            'ي' => 'y',
            'ئ' => 'y',
            'ى' => 'a',
            'ة' => 'a',
            'ء' => '',
        ];
    }
}

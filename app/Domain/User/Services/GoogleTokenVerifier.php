<?php

namespace App\Domain\User\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class GoogleTokenVerifier
{
    public function verifyIdToken(string $idToken): array
    {
        if (!$this->looksLikeJwt($idToken)) {
            Log::warning('Google token verification failed: malformed id token', [
                'token_preview' => $this->tokenPreview($idToken),
            ]);

            throw ValidationException::withMessages([
                'id_token' => [__('api.auth.invalid_credentials')],
            ]);
        }

        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->get('https://oauth2.googleapis.com/tokeninfo', [
                    'id_token' => $idToken,
                ]);
            Log::info('Google token verification response', [
                'status' => $response->status(),
                'token_preview' => $this->tokenPreview($idToken),
                'body' => $response->json() ?? $response->body(),
            ]);
        } catch (ConnectionException $e) {
            Log::error('Failed to reach Google token verification endpoint', [
                'error' => $e->getMessage(),
                'token_preview' => $this->tokenPreview($idToken),
            ]);

            throw new \RuntimeException('Google token verification is unavailable.', previous: $e);
        }
        if ($response->status() != 200) {
            Log::warning('Google token verification rejected by Google', [
                'status' => $response->status(),
                'token_preview' => $this->tokenPreview($idToken),
                'body' => $response->json() ?? $response->body(),
            ]);

            throw ValidationException::withMessages([
                'id_token' => [__('api.auth.invalid_credentials')],
            ]);
        }

        $payload = $response->json();

        $clientId = config('services.google.client_id');
        $emailVerified = filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (
            !is_array($payload)
            || empty($payload['sub'])
            || empty($payload['email'])
            || !$emailVerified
            || ($clientId && ($payload['aud'] ?? null) !== $clientId)
        ) {
            Log::warning('Google token verification failed: invalid payload', [
                'payload' => $payload,
                'sub_exists' => !empty($payload['sub']),
                'email_exists' => !empty($payload['email']),
                'email_verified' => $emailVerified,
                'aud_matches' => $clientId && (($payload['aud'] ?? null) === $clientId),
            ]);
            throw ValidationException::withMessages([
                'id_token' => [__('api.auth.invalid_credentials')],
            ]);
        }

        return $payload;
    }

    private function looksLikeJwt(string $idToken): bool
    {
        return preg_match('/^[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+$/', $idToken) === 1;
    }

    private function tokenPreview(string $idToken): string
    {
        return substr($idToken, 0, 12) . '...';
    }
}

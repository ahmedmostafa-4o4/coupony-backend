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

        if (!is_array($payload)) {
            Log::warning('Google token verification failed: payload is not an array', [
                'payload' => $payload,
                'token_preview' => $this->tokenPreview($idToken),
            ]);

            throw ValidationException::withMessages([
                'id_token' => [__('api.auth.invalid_credentials')],
            ]);
        }

        $clientId = $this->normalizeGoogleIdentifier(config('services.google.client_id'));
        $audience = $this->normalizeGoogleIdentifier($payload['aud'] ?? null);
        $authorizedParty = $this->normalizeGoogleIdentifier($payload['azp'] ?? null);
        $emailVerified = filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $clientIdMatches = $clientId === '' || in_array($clientId, array_filter([
            $audience,
            $authorizedParty,
        ]), true);

        if (
            empty($payload['sub'])
            || empty($payload['email'])
            || !$emailVerified
            || !$clientIdMatches
        ) {
            Log::warning('Google token verification failed: invalid payload', [
                'payload' => $payload,
                'sub_exists' => !empty($payload['sub']),
                'email_exists' => !empty($payload['email']),
                'email_verified' => $emailVerified,
                'client_id' => $clientId,
                'client_id_length' => strlen($clientId),
                'aud' => $audience,
                'aud_length' => strlen($audience),
                'azp' => $authorizedParty,
                'azp_length' => strlen($authorizedParty),
                'client_id_matches' => $clientIdMatches,
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

    private function normalizeGoogleIdentifier(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }
}

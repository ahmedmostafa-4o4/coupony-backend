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
        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->get('https://oauth2.googleapis.com/tokeninfo', [
                    'id_token' => $idToken,
                ]);
            Log::info('Google token verification response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (ConnectionException $e) {
            Log::error('Failed to reach Google token verification endpoint', [
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Google token verification is unavailable.', previous: $e);
        }
        if ($response->status() != 200) {
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
                'payload' => !is_array($payload),
                'sub_exists' => !empty($payload['sub']),
                'email_exists' => !empty($payload['email']),
                'email_verified' => $emailVerified,
                'aud_matches' => $clientId ? (($payload['aud'] ?? null) === $clientId) : 'not checked',
            ]);
            throw ValidationException::withMessages([
                'id_token' => [__('api.auth.invalid_credentials')],
            ]);
        }

        return $payload;
    }
}

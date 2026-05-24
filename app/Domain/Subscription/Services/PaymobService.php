<?php

namespace App\Domain\Subscription\Services;

use App\Domain\Subscription\Exceptions\PaymobApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class PaymobService
{
    private string $baseUrl;

    private string $secretKey;

    private string $publicKey;

    private string $apiKey;

    private string $integrationId;

    private string $hmacSecret;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('subscription.paymob.base_url'), '/');
        $this->secretKey = (string) config('subscription.paymob.secret_key');
        $this->publicKey = (string) config('subscription.paymob.public_key');
        $this->apiKey = (string) config('subscription.paymob.api_key');
        $this->integrationId = (string) config('subscription.paymob.integration_id');
        $this->hmacSecret = (string) config('subscription.paymob.hmac_secret');
    }

    /**
     * Create a Payment Intention using Paymob's Intention API.
     *
     * Returns the full intention response including client_secret.
     *
     * @return array{client_secret: string, id: string, payment_keys: array}
     *
     * @throws PaymobApiException
     */
    public function createIntention(int $amountCents, string $currency, array $billingData, array $extras = []): array
    {
        try {
            $response = Http::baseUrl($this->baseUrl)
                ->withToken($this->secretKey)
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->post('/v1/intention/', [
                    'amount' => $amountCents,
                    'currency' => $currency,
                    'payment_methods' => [(int) $this->integrationId],
                    'billing_data' => $billingData,
                    'extras' => $extras,
                ]);
        } catch (ConnectionException $exception) {
            throw PaymobApiException::networkError($exception->getMessage());
        } catch (Throwable $exception) {
            throw PaymobApiException::networkError($exception->getMessage());
        }

        if (! $response->successful()) {
            throw PaymobApiException::invalidResponse($response->status(), (string) $response->body());
        }

        $decoded = $response->json();

        if (! is_array($decoded)) {
            throw PaymobApiException::invalidResponse($response->status(), 'Non-JSON response body');
        }

        $clientSecret = $decoded['client_secret'] ?? null;

        if (! is_string($clientSecret) || $clientSecret === '') {
            throw PaymobApiException::paymentKeyGenerationFailed('No client_secret in intention response');
        }

        return $decoded;
    }

    /**
     * Get the public key for the Flutter SDK.
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Validate the HMAC signature from a Paymob webhook payload.
     *
     * Paymob's documented HMAC calculation concatenates specific transaction
     * callback fields in a defined order, then computes HMAC-SHA512.
     */
    public function validateHmac(array $payload, string $signature): bool
    {
        $hmacFields = [
            'amount_cents',
            'created_at',
            'currency',
            'error_occured',
            'has_parent_transaction',
            'id',
            'integration_id',
            'is_3d_secure',
            'is_auth',
            'is_capture',
            'is_refunded',
            'is_standalone_payment',
            'is_voided',
            'order.id',
            'owner',
            'pending',
            'source_data.pan',
            'source_data.sub_type',
            'source_data.type',
            'success',
        ];

        $concatenated = '';

        foreach ($hmacFields as $field) {
            $concatenated .= $this->extractNestedValue($payload, $field);
        }

        $computedHmac = hash_hmac('sha512', $concatenated, $this->hmacSecret);

        return hash_equals($computedHmac, $signature);
    }

    /**
     * Extract a nested value from the payload using dot notation.
     */
    private function extractNestedValue(array $payload, string $key): string
    {
        $keys = explode('.', $key);
        $value = $payload;

        foreach ($keys as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return '';
            }
            $value = $value[$segment];
        }

        // Paymob sends booleans as strings in HMAC calculation
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }
}

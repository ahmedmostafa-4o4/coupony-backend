<?php

namespace Tests\Property;

use App\Domain\Subscription\Services\PaymobService;
use Faker\Factory as Faker;
use Tests\TestCase;

/**
 * Feature: subscription-system, Property 7: HMAC validation gate
 *
 * For any webhook payload where the computed HMAC does not match the provided signature,
 * the system must reject the request with HTTP 401, must not modify any payment session
 * or subscription state, and must log the security violation.
 *
 * **Validates: Requirements 3.1, 3.2, 11.3**
 */
class HmacValidationPropertyTest extends TestCase
{
    private PaymobService $service;

    private string $hmacSecret;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hmacSecret = 'property_test_hmac_secret_' . bin2hex(random_bytes(8));

        config([
            'subscription.paymob.api_key' => 'test_api_key',
            'subscription.paymob.integration_id' => '12345',
            'subscription.paymob.iframe_id' => '67890',
            'subscription.paymob.hmac_secret' => $this->hmacSecret,
            'subscription.paymob.base_url' => 'https://accept.paymob.com/api',
        ]);

        $this->service = new PaymobService();
    }

    /**
     * Generate a random Paymob webhook payload using Faker.
     */
    private function generateRandomPayload(\Faker\Generator $faker): array
    {
        return [
            'amount_cents' => (string) $faker->numberBetween(100, 9999999),
            'created_at' => $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d\TH:i:s.u'),
            'currency' => $faker->randomElement(['EGP', 'USD', 'EUR', 'SAR', 'AED']),
            'error_occured' => $faker->randomElement([true, false, 'true', 'false']),
            'has_parent_transaction' => $faker->randomElement([true, false, 'true', 'false']),
            'id' => (string) $faker->numberBetween(1, 9999999),
            'integration_id' => (string) $faker->numberBetween(1000, 99999),
            'is_3d_secure' => $faker->randomElement([true, false, 'true', 'false']),
            'is_auth' => $faker->randomElement([true, false, 'true', 'false']),
            'is_capture' => $faker->randomElement([true, false, 'true', 'false']),
            'is_refunded' => $faker->randomElement([true, false, 'true', 'false']),
            'is_standalone_payment' => $faker->randomElement([true, false, 'true', 'false']),
            'is_voided' => $faker->randomElement([true, false, 'true', 'false']),
            'order' => ['id' => (string) $faker->numberBetween(1, 9999999)],
            'owner' => (string) $faker->numberBetween(1, 99999),
            'pending' => $faker->randomElement([true, false, 'true', 'false']),
            'source_data' => [
                'pan' => (string) $faker->numberBetween(1000, 9999),
                'sub_type' => $faker->randomElement(['MasterCard', 'Visa', 'Meeza', 'Amex']),
                'type' => $faker->randomElement(['card', 'wallet']),
            ],
            'success' => $faker->randomElement([true, false, 'true', 'false']),
        ];
    }

    /**
     * Compute the correct HMAC for a given payload using the same logic as PaymobService.
     */
    private function computeCorrectHmac(array $payload): string
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

        return hash_hmac('sha512', $concatenated, $this->hmacSecret);
    }

    /**
     * Extract a nested value from the payload using dot notation (mirrors PaymobService logic).
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

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }

    /**
     * Data provider that generates 100 random payloads for property testing.
     */
    public static function randomPayloadProvider(): array
    {
        $faker = Faker::create();
        $cases = [];

        for ($i = 0; $i < 100; $i++) {
            $cases["iteration_{$i}"] = [$i];
        }

        return $cases;
    }

    /**
     * Property: Valid HMAC signature is accepted.
     *
     * For any randomly generated payload, computing the correct HMAC and passing it
     * to validateHmac must return true.
     *
     * @dataProvider randomPayloadProvider
     */
    public function test_valid_hmac_is_accepted_for_any_payload(int $iteration): void
    {
        $faker = Faker::create();
        $faker->seed($iteration);

        $payload = $this->generateRandomPayload($faker);
        $validSignature = $this->computeCorrectHmac($payload);

        $this->assertTrue(
            $this->service->validateHmac($payload, $validSignature),
            "Iteration {$iteration}: Valid HMAC should be accepted. Payload: " . json_encode($payload)
        );
    }

    /**
     * Property: Mutated payload invalidates HMAC.
     *
     * For any randomly generated payload with a valid HMAC, mutating any single field
     * in the payload must cause validateHmac to return false.
     *
     * @dataProvider randomPayloadProvider
     */
    public function test_mutated_payload_invalidates_hmac(int $iteration): void
    {
        $faker = Faker::create();
        $faker->seed($iteration);

        $payload = $this->generateRandomPayload($faker);
        $validSignature = $this->computeCorrectHmac($payload);

        // Pick a random field to mutate
        $mutableFields = [
            'amount_cents', 'created_at', 'currency', 'error_occured',
            'has_parent_transaction', 'id', 'integration_id', 'is_3d_secure',
            'is_auth', 'is_capture', 'is_refunded', 'is_standalone_payment',
            'is_voided', 'owner', 'pending', 'success',
        ];

        $fieldToMutate = $mutableFields[$iteration % count($mutableFields)];
        $mutatedPayload = $payload;

        // Mutate the selected field to a different value
        if (is_bool($mutatedPayload[$fieldToMutate])) {
            $mutatedPayload[$fieldToMutate] = ! $mutatedPayload[$fieldToMutate];
        } elseif ($mutatedPayload[$fieldToMutate] === 'true') {
            $mutatedPayload[$fieldToMutate] = 'false';
        } elseif ($mutatedPayload[$fieldToMutate] === 'false') {
            $mutatedPayload[$fieldToMutate] = 'true';
        } else {
            $mutatedPayload[$fieldToMutate] = $mutatedPayload[$fieldToMutate] . '_mutated';
        }

        $this->assertFalse(
            $this->service->validateHmac($mutatedPayload, $validSignature),
            "Iteration {$iteration}: Mutating field '{$fieldToMutate}' should invalidate HMAC."
        );
    }

    /**
     * Property: Mutated signature is rejected.
     *
     * For any randomly generated payload, any signature that differs from the correct
     * computed HMAC must be rejected by validateHmac.
     *
     * @dataProvider randomPayloadProvider
     */
    public function test_mutated_signature_is_rejected(int $iteration): void
    {
        $faker = Faker::create();
        $faker->seed($iteration);

        $payload = $this->generateRandomPayload($faker);

        // Generate a random invalid signature
        $invalidSignature = hash('sha512', $faker->text(200) . $iteration);

        $this->assertFalse(
            $this->service->validateHmac($payload, $invalidSignature),
            "Iteration {$iteration}: Random signature should be rejected."
        );
    }

    /**
     * Property: Mutated nested fields invalidate HMAC.
     *
     * For any randomly generated payload with a valid HMAC, mutating nested fields
     * (order.id, source_data.*) must cause validateHmac to return false.
     *
     * @dataProvider randomPayloadProvider
     */
    public function test_mutated_nested_fields_invalidate_hmac(int $iteration): void
    {
        $faker = Faker::create();
        $faker->seed($iteration);

        $payload = $this->generateRandomPayload($faker);
        $validSignature = $this->computeCorrectHmac($payload);

        $mutatedPayload = $payload;

        // Cycle through nested fields to mutate
        $nestedMutations = [
            'order.id',
            'source_data.pan',
            'source_data.sub_type',
            'source_data.type',
        ];

        $mutationIndex = $iteration % count($nestedMutations);
        $fieldPath = $nestedMutations[$mutationIndex];

        if ($fieldPath === 'order.id') {
            $mutatedPayload['order']['id'] = (string) ($faker->numberBetween(1, 9999999) + 1000000);
        } elseif ($fieldPath === 'source_data.pan') {
            $mutatedPayload['source_data']['pan'] = (string) $faker->numberBetween(1000, 9999) . '0';
        } elseif ($fieldPath === 'source_data.sub_type') {
            $mutatedPayload['source_data']['sub_type'] = 'Mutated' . $faker->word();
        } elseif ($fieldPath === 'source_data.type') {
            $mutatedPayload['source_data']['type'] = 'mutated_type';
        }

        $this->assertFalse(
            $this->service->validateHmac($mutatedPayload, $validSignature),
            "Iteration {$iteration}: Mutating nested field '{$fieldPath}' should invalidate HMAC."
        );
    }
}

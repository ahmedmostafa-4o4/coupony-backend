<?php

namespace Tests\Unit;

use App\Domain\Subscription\Exceptions\PaymobApiException;
use App\Domain\Subscription\Services\PaymobService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymobServiceTest extends TestCase
{
    private PaymobService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'subscription.paymob.secret_key' => 'test_secret_key_123',
            'subscription.paymob.public_key' => 'egy_pk_test_abc123',
            'subscription.paymob.api_key' => 'test_api_key_123',
            'subscription.paymob.integration_id' => '12345',
            'subscription.paymob.hmac_secret' => 'test_hmac_secret',
            'subscription.paymob.base_url' => 'https://accept.paymob.com',
        ]);

        $this->service = new PaymobService();
    }

    public function test_create_intention_returns_client_secret_on_success(): void
    {
        Http::fake([
            'accept.paymob.com/v1/intention/' => Http::response([
                'id' => 'intention_abc123',
                'client_secret' => 'pk_test_client_secret_xyz789',
                'payment_keys' => [
                    ['key' => 'key_1', 'integration' => 12345],
                ],
            ], 200),
        ]);

        $billingData = [
            'first_name' => 'Store',
            'last_name' => 'Owner',
            'email' => 'payment@coupony.app',
            'phone_number' => '+201000000000',
        ];

        $result = $this->service->createIntention(9900, 'EGP', $billingData);

        $this->assertEquals('pk_test_client_secret_xyz789', $result['client_secret']);
        $this->assertEquals('intention_abc123', $result['id']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://accept.paymob.com/v1/intention/'
                && $request->hasHeader('Authorization', 'Bearer test_secret_key_123')
                && $request['amount'] === 9900
                && $request['currency'] === 'EGP'
                && $request['payment_methods'] === [12345]
                && $request['billing_data']['first_name'] === 'Store';
        });
    }

    public function test_create_intention_sends_extras(): void
    {
        Http::fake([
            'accept.paymob.com/v1/intention/' => Http::response([
                'id' => 'intention_abc123',
                'client_secret' => 'pk_test_client_secret_xyz789',
                'payment_keys' => [],
            ], 200),
        ]);

        $billingData = [
            'first_name' => 'Store',
            'last_name' => 'Owner',
            'email' => 'payment@coupony.app',
            'phone_number' => '+201000000000',
        ];

        $extras = [
            'store_id' => 'store-uuid-123',
            'plan_id' => 'plan-uuid-456',
            'billing_cycle' => 'monthly',
        ];

        $this->service->createIntention(9900, 'EGP', $billingData, $extras);

        Http::assertSent(function ($request) {
            return $request['extras']['store_id'] === 'store-uuid-123'
                && $request['extras']['plan_id'] === 'plan-uuid-456'
                && $request['extras']['billing_cycle'] === 'monthly';
        });
    }

    public function test_create_intention_throws_exception_on_api_failure(): void
    {
        Http::fake([
            'accept.paymob.com/v1/intention/' => Http::response(
                ['detail' => 'Unauthorized'],
                401
            ),
        ]);

        $this->expectException(PaymobApiException::class);

        $this->service->createIntention(9900, 'EGP', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone_number' => '+201000000000',
        ]);
    }

    public function test_create_intention_throws_exception_when_no_client_secret(): void
    {
        Http::fake([
            'accept.paymob.com/v1/intention/' => Http::response([
                'id' => 'intention_abc123',
                'payment_keys' => [],
            ], 200),
        ]);

        $this->expectException(PaymobApiException::class);
        $this->expectExceptionMessage('No client_secret in intention response');

        $this->service->createIntention(9900, 'EGP', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone_number' => '+201000000000',
        ]);
    }

    public function test_create_intention_throws_exception_on_network_error(): void
    {
        Http::fake([
            'accept.paymob.com/v1/intention/' => Http::response(null, 500),
        ]);

        $this->expectException(PaymobApiException::class);

        $this->service->createIntention(9900, 'EGP', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone_number' => '+201000000000',
        ]);
    }

    public function test_get_public_key_returns_configured_key(): void
    {
        $this->assertEquals('egy_pk_test_abc123', $this->service->getPublicKey());
    }

    public function test_validate_hmac_returns_true_for_valid_signature(): void
    {
        $payload = [
            'amount_cents' => '10000',
            'created_at' => '2024-01-15T10:00:00.000000',
            'currency' => 'EGP',
            'error_occured' => 'false',
            'has_parent_transaction' => 'false',
            'id' => '123456',
            'integration_id' => '12345',
            'is_3d_secure' => 'true',
            'is_auth' => 'false',
            'is_capture' => 'false',
            'is_refunded' => 'false',
            'is_standalone_payment' => 'true',
            'is_voided' => 'false',
            'order' => ['id' => '99001'],
            'owner' => '1001',
            'pending' => 'false',
            'source_data' => [
                'pan' => '2346',
                'sub_type' => 'MasterCard',
                'type' => 'card',
            ],
            'success' => 'true',
        ];

        // Compute the expected HMAC
        $concatenated = '10000'  // amount_cents
            . '2024-01-15T10:00:00.000000'  // created_at
            . 'EGP'  // currency
            . 'false'  // error_occured
            . 'false'  // has_parent_transaction
            . '123456'  // id
            . '12345'  // integration_id
            . 'true'  // is_3d_secure
            . 'false'  // is_auth
            . 'false'  // is_capture
            . 'false'  // is_refunded
            . 'true'  // is_standalone_payment
            . 'false'  // is_voided
            . '99001'  // order.id
            . '1001'  // owner
            . 'false'  // pending
            . '2346'  // source_data.pan
            . 'MasterCard'  // source_data.sub_type
            . 'card'  // source_data.type
            . 'true';  // success

        $validSignature = hash_hmac('sha512', $concatenated, 'test_hmac_secret');

        $this->assertTrue($this->service->validateHmac($payload, $validSignature));
    }

    public function test_validate_hmac_returns_false_for_invalid_signature(): void
    {
        $payload = [
            'amount_cents' => '10000',
            'created_at' => '2024-01-15T10:00:00.000000',
            'currency' => 'EGP',
            'error_occured' => 'false',
            'has_parent_transaction' => 'false',
            'id' => '123456',
            'integration_id' => '12345',
            'is_3d_secure' => 'true',
            'is_auth' => 'false',
            'is_capture' => 'false',
            'is_refunded' => 'false',
            'is_standalone_payment' => 'true',
            'is_voided' => 'false',
            'order' => ['id' => '99001'],
            'owner' => '1001',
            'pending' => 'false',
            'source_data' => [
                'pan' => '2346',
                'sub_type' => 'MasterCard',
                'type' => 'card',
            ],
            'success' => 'true',
        ];

        $this->assertFalse($this->service->validateHmac($payload, 'invalid_signature_here'));
    }

    public function test_validate_hmac_handles_boolean_values_correctly(): void
    {
        $payload = [
            'amount_cents' => '5000',
            'created_at' => '2024-02-01T12:00:00.000000',
            'currency' => 'EGP',
            'error_occured' => true,  // boolean instead of string
            'has_parent_transaction' => false,  // boolean instead of string
            'id' => '789',
            'integration_id' => '12345',
            'is_3d_secure' => false,
            'is_auth' => false,
            'is_capture' => false,
            'is_refunded' => false,
            'is_standalone_payment' => true,
            'is_voided' => false,
            'order' => ['id' => '555'],
            'owner' => '2002',
            'pending' => false,
            'source_data' => [
                'pan' => '1234',
                'sub_type' => 'Visa',
                'type' => 'card',
            ],
            'success' => true,
        ];

        $concatenated = '5000'
            . '2024-02-01T12:00:00.000000'
            . 'EGP'
            . 'true'   // error_occured (bool true)
            . 'false'  // has_parent_transaction (bool false)
            . '789'
            . '12345'
            . 'false'  // is_3d_secure
            . 'false'  // is_auth
            . 'false'  // is_capture
            . 'false'  // is_refunded
            . 'true'   // is_standalone_payment
            . 'false'  // is_voided
            . '555'    // order.id
            . '2002'   // owner
            . 'false'  // pending
            . '1234'   // source_data.pan
            . 'Visa'   // source_data.sub_type
            . 'card'   // source_data.type
            . 'true';  // success

        $validSignature = hash_hmac('sha512', $concatenated, 'test_hmac_secret');

        $this->assertTrue($this->service->validateHmac($payload, $validSignature));
    }
}

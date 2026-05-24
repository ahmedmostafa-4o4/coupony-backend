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
            'subscription.paymob.api_key' => 'test_api_key_123',
            'subscription.paymob.integration_id' => '12345',
            'subscription.paymob.iframe_id' => '67890',
            'subscription.paymob.hmac_secret' => 'test_hmac_secret',
            'subscription.paymob.base_url' => 'https://accept.paymob.com/api',
        ]);

        $this->service = new PaymobService();
    }

    public function test_authenticate_returns_token_on_success(): void
    {
        Http::fake([
            'accept.paymob.com/api/auth/tokens' => Http::response([
                'token' => 'auth_token_abc123',
            ], 200),
        ]);

        $token = $this->service->authenticate();

        $this->assertEquals('auth_token_abc123', $token);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://accept.paymob.com/api/auth/tokens'
                && $request['api_key'] === 'test_api_key_123';
        });
    }

    public function test_authenticate_throws_exception_on_failure(): void
    {
        Http::fake([
            'accept.paymob.com/api/auth/tokens' => Http::response(['detail' => 'Unauthorized'], 401),
        ]);

        $this->expectException(PaymobApiException::class);

        $this->service->authenticate();
    }

    public function test_authenticate_throws_exception_when_no_token_in_response(): void
    {
        Http::fake([
            'accept.paymob.com/api/auth/tokens' => Http::response(['message' => 'ok'], 200),
        ]);

        $this->expectException(PaymobApiException::class);
        $this->expectExceptionMessage('Paymob authentication failed: No token in response');

        $this->service->authenticate();
    }

    public function test_create_order_returns_order_data(): void
    {
        Http::fake([
            'accept.paymob.com/api/auth/tokens' => Http::response([
                'token' => 'auth_token_abc123',
            ], 200),
            'accept.paymob.com/api/ecommerce/orders' => Http::response([
                'id' => 99001,
                'created_at' => '2024-01-15T10:00:00Z',
            ], 200),
        ]);

        $result = $this->service->createOrder(10000, 'EGP', 'order-uuid-123');

        $this->assertEquals(99001, $result['id']);

        Http::assertSent(function ($request) {
            if ($request->url() === 'https://accept.paymob.com/api/ecommerce/orders') {
                return $request['amount_cents'] === 10000
                    && $request['currency'] === 'EGP'
                    && $request['merchant_order_id'] === 'order-uuid-123'
                    && $request['delivery_needed'] === false;
            }

            return true;
        });
    }

    public function test_create_order_throws_exception_when_no_order_id(): void
    {
        Http::fake([
            'accept.paymob.com/api/auth/tokens' => Http::response([
                'token' => 'auth_token_abc123',
            ], 200),
            'accept.paymob.com/api/ecommerce/orders' => Http::response([
                'message' => 'created',
            ], 200),
        ]);

        $this->expectException(PaymobApiException::class);
        $this->expectExceptionMessage('Paymob order creation failed: No order ID in response');

        $this->service->createOrder(10000, 'EGP', 'order-uuid-123');
    }

    public function test_generate_payment_key_returns_key(): void
    {
        Http::fake([
            'accept.paymob.com/api/acceptance/payment_keys' => Http::response([
                'token' => 'payment_key_xyz789',
            ], 200),
        ]);

        $billingData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone_number' => '+201234567890',
            'apartment' => 'NA',
            'floor' => 'NA',
            'street' => 'NA',
            'building' => 'NA',
            'shipping_method' => 'NA',
            'postal_code' => 'NA',
            'city' => 'Cairo',
            'country' => 'EG',
            'state' => 'NA',
        ];

        $key = $this->service->generatePaymentKey(
            'auth_token_abc123',
            99001,
            10000,
            'EGP',
            $billingData
        );

        $this->assertEquals('payment_key_xyz789', $key);

        Http::assertSent(function ($request) {
            if ($request->url() === 'https://accept.paymob.com/api/acceptance/payment_keys') {
                return $request['auth_token'] === 'auth_token_abc123'
                    && $request['order_id'] === 99001
                    && $request['amount_cents'] === 10000
                    && $request['currency'] === 'EGP'
                    && $request['integration_id'] === 12345
                    && $request['expiration'] === 3600;
            }

            return true;
        });
    }

    public function test_generate_payment_key_throws_exception_on_failure(): void
    {
        Http::fake([
            'accept.paymob.com/api/acceptance/payment_keys' => Http::response(
                ['detail' => 'Invalid token'],
                401
            ),
        ]);

        $this->expectException(PaymobApiException::class);

        $this->service->generatePaymentKey('invalid_token', 99001, 10000, 'EGP', []);
    }

    public function test_get_payment_url_constructs_correct_url(): void
    {
        $url = $this->service->getPaymentUrl('payment_key_xyz789');

        $this->assertEquals(
            'https://accept.paymob.com/acceptance/iframes/67890?payment_token=payment_key_xyz789',
            $url
        );
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

    public function test_network_error_throws_paymob_api_exception(): void
    {
        Http::fake([
            'accept.paymob.com/api/auth/tokens' => Http::response(null, 500),
        ]);

        $this->expectException(PaymobApiException::class);

        $this->service->authenticate();
    }
}

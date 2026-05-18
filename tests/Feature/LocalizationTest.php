<?php

namespace Tests\Feature;

use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'customer', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'seller', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'seller_pending', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
    }

    public function test_locales_endpoint_returns_supported_languages(): void
    {
        $response = $this->withHeader('Accept-Language', 'en')
            ->getJson('/api/v1/locales');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['code' => 'en', 'name' => 'English', 'native_name' => 'English'])
            ->assertJsonFragment(['code' => 'ar', 'name' => 'Arabic', 'native_name' => 'العربية']);
    }

    public function test_authenticated_user_can_update_language(): void
    {
        $user = User::factory()->create(['language' => 'en']);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/auth/language', [
                'language' => 'ar',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.language', 'ar')
            ->assertJsonPath('message', 'تم تحديث اللغة بنجاح.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'language' => 'ar',
        ]);
    }

    public function test_saved_user_language_is_used_when_header_is_missing(): void
    {
        $user = User::factory()->create(['language' => 'ar']);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept-Language' => '',
        ])
            ->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertHeader('Content-Language', 'ar')
            ->assertJsonPath('message', 'تم تسجيل الخروج بنجاح.');
    }

    public function test_accept_language_header_overrides_saved_language_for_the_current_request(): void
    {
        $user = User::factory()->create(['language' => 'ar']);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept-Language' => 'en',
        ])->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertHeader('Content-Language', 'en')
            ->assertJsonPath('message', 'Logged out successfully.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'language' => 'ar',
        ]);
    }

    public function test_unsupported_header_locale_falls_back_to_user_language(): void
    {
        $user = User::factory()->create(['language' => 'ar']);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept-Language' => 'fr',
        ])->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertHeader('Content-Language', 'ar')
            ->assertJsonPath('message', 'تم تسجيل الخروج بنجاح.');
    }

    public function test_registration_validation_errors_are_translated_in_english_and_arabic(): void
    {
        $englishResponse = $this->withHeader('Accept-Language', 'en')
            ->postJson('/api/v1/auth/register', [
                'last_name' => 'Doe',
                'email' => 'john@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'customer',
            ]);

        $englishResponse->assertStatus(422)
            ->assertHeader('Content-Language', 'en')
            ->assertJsonPath('errors.first_name.0', 'The first name field is required.');

        $arabicResponse = $this->withHeader('Accept-Language', 'ar')
            ->postJson('/api/v1/auth/register', [
                'last_name' => 'Doe',
                'email' => 'john@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'customer',
            ]);

        $arabicResponse->assertStatus(422)
            ->assertHeader('Content-Language', 'ar')
            ->assertJsonPath('errors.first_name.0', 'حقل الاسم الأول مطلوب.');
    }

    public function test_otp_resend_error_message_is_localized(): void
    {
        $user = User::factory()->create([
            'email' => 'otp@example.com',
            'language' => 'ar',
        ]);

        $this->withHeader('Accept-Language', 'ar')
            ->postJson('/api/v1/auth/otp/send', [
                'email' => $user->email,
                'purpose' => 'verify_email',
            ])
            ->assertOk();

        $response = $this->withHeader('Accept-Language', 'ar')
            ->postJson('/api/v1/auth/otp/resend', [
                'email' => $user->email,
                'purpose' => 'verify_email',
            ]);

        $response->assertStatus(429)
            ->assertHeader('Content-Language', 'ar')
            ->assertJsonPath('error_code', 'RATE_LIMIT');

        $this->assertStringContainsString('يرجى الانتظار', $response->json('message'));
    }

    public function test_password_reset_message_is_localized(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'language' => 'ar',
        ]);

        $response = $this->withHeader('Accept-Language', 'ar')
            ->postJson('/api/v1/auth/password/forgot', [
                'email' => $user->email,
            ]);

        $response->assertOk()
            ->assertHeader('Content-Language', 'ar')
            ->assertJsonPath('message', 'تم إرسال رمز إعادة تعيين كلمة المرور إلى بريدك الإلكتروني.');
    }

    public function test_admin_store_management_response_is_localized(): void
    {
        $admin = User::factory()->create(['language' => 'ar']);
        $admin->assignRole('admin');
        $store = Store::factory()->create(['status' => StoreStatus::PENDING]);
        $token = $admin->createToken('admin-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept-Language' => '',
        ])
            ->getJson("/api/v1/admin/stores/{$store->id}");

        $response->assertOk()
            ->assertHeader('Content-Language', 'ar')
            ->assertJsonPath('message', 'تم جلب تفاصيل المتجر بنجاح.');
    }
}

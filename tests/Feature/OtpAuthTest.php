<?php

namespace Tests\Feature;

use App\Models\OtpCode;
use App\Models\Role;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OtpAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders(): void
    {
        $this->withoutVite();

        $this->get('/login')->assertOk()->assertSee('ورود');
    }

    public function test_can_request_otp_for_valid_phone(): void
    {
        $response = $this->postJson('/auth/otp/request', ['phone' => '09121234567']);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('otp_codes', ['phone' => '09121234567']);
    }

    public function test_invalid_phone_format_is_rejected(): void
    {
        $response = $this->postJson('/auth/otp/request', ['phone' => '12345']);

        $response->assertStatus(422);
    }

    public function test_verifying_correct_code_creates_new_user_and_logs_in(): void
    {
        Role::create(['name' => 'Customer', 'slug' => 'customer']);

        $otpService = app(OtpService::class);
        $otpService->requestCode('09121234567');
        $code = OtpCode::where('phone', '09121234567')->latest()->first();

        // چون کد Hash شده ذخیره می‌شود، برای تست باید یک کد شناخته‌شده بسازیم
        $rawCode = '54321';
        $code->update(['code' => Hash::make($rawCode)]);

        $response = $this->postJson('/auth/otp/verify', [
            'phone' => '09121234567',
            'code' => $rawCode,
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('users', ['phone' => '09121234567']);
        $user = User::where('phone', '09121234567')->first();
        $this->assertNotNull($user->phone_verified_at);
        $this->assertTrue($user->roles->contains('slug', 'customer'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_verifying_wrong_code_fails_and_increments_attempts(): void
    {
        $otpService = app(OtpService::class);
        $otpService->requestCode('09121234567');
        $otp = OtpCode::where('phone', '09121234567')->latest()->first();
        $otp->update(['code' => Hash::make('11111')]);

        $response = $this->postJson('/auth/otp/verify', [
            'phone' => '09121234567',
            'code' => '99999',
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
        $this->assertEquals(1, $otp->fresh()->attempts);
        $this->assertGuest();
    }

    public function test_expired_code_is_rejected(): void
    {
        $otpService = app(OtpService::class);
        $otpService->requestCode('09121234567');
        $otp = OtpCode::where('phone', '09121234567')->latest()->first();
        $otp->update(['code' => Hash::make('54321'), 'expires_at' => now()->subMinute()]);

        $response = $this->postJson('/auth/otp/verify', [
            'phone' => '09121234567',
            'code' => '54321',
        ]);

        $response->assertStatus(422);
    }

    public function test_existing_user_logs_in_without_creating_duplicate(): void
    {
        $existingUser = User::factory()->create(['phone' => '09121234567']);

        $otpService = app(OtpService::class);
        $otpService->requestCode('09121234567');
        $otp = OtpCode::where('phone', '09121234567')->latest()->first();
        $otp->update(['code' => Hash::make('54321')]);

        $this->postJson('/auth/otp/verify', [
            'phone' => '09121234567',
            'code' => '54321',
        ])->assertOk();

        $this->assertDatabaseCount('users', 1);
        $this->assertAuthenticatedAs($existingUser);
    }

    public function test_code_locks_after_max_attempts(): void
    {
        $otpService = app(OtpService::class);
        $otpService->requestCode('09121234567');
        $otp = OtpCode::where('phone', '09121234567')->latest()->first();
        $otp->update(['code' => Hash::make('54321'), 'attempts' => 5]);

        $response = $this->postJson('/auth/otp/verify', [
            'phone' => '09121234567',
            'code' => '54321',
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'تعداد تلاش‌های مجاز تمام شده. کد جدید درخواست دهید.']);
    }
}

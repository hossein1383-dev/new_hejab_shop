<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WalletTopup;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletTopupTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_initiate_topup(): void
    {
        $this->postJson('/wallet/topup', ['amount' => 50000])->assertStatus(401);
    }

    public function test_rejects_amount_below_minimum(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/wallet/topup', ['amount' => 5000]);

        $response->assertStatus(422);
    }

    public function test_authenticated_user_can_initiate_topup(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/wallet/topup', ['amount' => 50000]);

        $response->assertStatus(201)->assertJson(['success' => true]);
        $this->assertDatabaseHas('wallet_topups', ['user_id' => $user->id, 'amount' => 50000, 'status' => 'pending']);
    }

    public function test_successful_callback_credits_wallet(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/wallet/topup', ['amount' => 50000]);
        $topup = WalletTopup::where('user_id', $user->id)->firstOrFail();

        $this->post('/wallet/topup/callback', [
            'reference' => $topup->reference,
            'amount' => 50000,
            'outcome' => 'success',
        ])->assertRedirect(route('wallet.show'));

        $this->assertEquals('paid', $topup->fresh()->status);
        $wallet = app(WalletService::class)->getOrCreate($user);
        $this->assertEquals(50000, $wallet->balance);
    }

    public function test_duplicate_callback_does_not_credit_twice(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/wallet/topup', ['amount' => 50000]);
        $topup = WalletTopup::where('user_id', $user->id)->firstOrFail();

        $callbackData = ['reference' => $topup->reference, 'amount' => 50000, 'outcome' => 'success'];
        $this->post('/wallet/topup/callback', $callbackData);
        $this->post('/wallet/topup/callback', $callbackData);

        $wallet = app(WalletService::class)->getOrCreate($user);
        $this->assertEquals(50000, $wallet->balance); // نه ۱۰۰,۰۰۰
    }

    public function test_failed_callback_does_not_credit_wallet(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/wallet/topup', ['amount' => 50000]);
        $topup = WalletTopup::where('user_id', $user->id)->firstOrFail();

        $this->post('/wallet/topup/callback', [
            'reference' => $topup->reference,
            'amount' => 50000,
            'outcome' => 'failed',
        ]);

        $this->assertEquals('failed', $topup->fresh()->status);
        $wallet = app(WalletService::class)->getOrCreate($user);
        $this->assertEquals(0, $wallet->balance);
    }
}

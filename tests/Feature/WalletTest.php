<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_debit_exact_balance_succeeds_and_leaves_zero(): void
    {
        $user = User::factory()->create();
        $walletService = app(WalletService::class);
        $walletService->credit($user, 50000);

        $wallet = $walletService->debit($user, 50000);

        $this->assertEquals(0, $wallet->balance);
    }

    public function test_credit_increases_balance_and_records_transaction(): void
    {
        $user = User::factory()->create();
        $walletService = app(WalletService::class);

        $wallet = $walletService->credit($user, 50000, 'ORD-TEST', 'بازگشت وجه');

        $this->assertEquals(50000, $wallet->balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'credit',
            'amount' => 50000,
        ]);
    }

    public function test_debit_decreases_balance_when_sufficient(): void
    {
        $user = User::factory()->create();
        $walletService = app(WalletService::class);

        $walletService->credit($user, 100000);
        $wallet = $walletService->debit($user, 30000, 'ORD-TEST-2');

        $this->assertEquals(70000, $wallet->balance);
    }

    public function test_debit_fails_when_insufficient_balance(): void
    {
        $user = User::factory()->create();
        $walletService = app(WalletService::class);
        $walletService->credit($user, 10000);

        $this->expectException(\DomainException::class);
        $walletService->debit($user, 20000);
    }

    public function test_authenticated_user_can_view_wallet_balance(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        app(WalletService::class)->credit($user, 25000);

        $response = $this->actingAs($user)->get('/wallet');

        $response->assertOk()->assertSee('25,000');
    }

    public function test_guest_cannot_view_wallet(): void
    {
        $this->get('/wallet')->assertRedirect('/login');
    }
}

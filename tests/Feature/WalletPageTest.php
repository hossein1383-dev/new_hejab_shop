<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_wallet_page(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $walletService = app(WalletService::class);
        $walletService->credit($user, 50000, null, 'شارژ تست');

        $this->actingAs($user)->get('/wallet')
            ->assertOk()
            ->assertSee('50,000')
            ->assertSee('شارژ تست');
    }

    public function test_wallet_page_shows_empty_state_with_no_transactions(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();

        $this->actingAs($user)->get('/wallet')
            ->assertOk()->assertSee('هنوز تراکنشی ثبت نشده است');
    }

    public function test_guest_is_redirected_to_login_from_wallet_page(): void
    {
        $this->get('/wallet')->assertRedirect('/login');
    }

    public function test_wallet_page_shows_new_card_design_with_hidden_topup_form(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/wallet');

        $response->assertOk()
            ->assertSee('data-toggle-topup-form', false)
            ->assertSee('wallet-balance-card__icon', false);
    }
}

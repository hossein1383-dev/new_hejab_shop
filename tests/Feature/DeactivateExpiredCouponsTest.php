<?php

namespace Tests\Feature;

use App\Models\Coupon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeactivateExpiredCouponsTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_coupons_are_deactivated(): void
    {
        $expired = Coupon::factory()->create(['status' => 'active', 'ends_at' => now()->subDay()]);
        $stillValid = Coupon::factory()->create(['status' => 'active', 'ends_at' => now()->addDay()]);
        $noExpiry = Coupon::factory()->create(['status' => 'active', 'ends_at' => null]);

        $this->artisan('coupons:deactivate-expired')->assertSuccessful();

        $this->assertEquals('inactive', $expired->fresh()->status);
        $this->assertEquals('active', $stillValid->fresh()->status);
        $this->assertEquals('active', $noExpiry->fresh()->status);
    }
}

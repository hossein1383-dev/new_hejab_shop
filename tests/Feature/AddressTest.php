<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_address_and_it_becomes_default_automatically(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/addresses', [
            'receiver_name' => 'علی رضایی',
            'phone' => '09120000000',
            'province' => 'تهران',
            'city' => 'تهران',
            'address_line' => 'خیابان آزادی',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('addresses', ['user_id' => $user->id, 'is_default' => true]);
    }

    public function test_setting_new_default_address_unsets_previous_default(): void
    {
        $user = User::factory()->create();
        $old = Address::factory()->create(['user_id' => $user->id, 'is_default' => true]);

        $this->actingAs($user)->postJson('/addresses', [
            'receiver_name' => 'کاربر دوم',
            'phone' => '09120000001',
            'province' => 'تهران',
            'city' => 'تهران',
            'address_line' => 'خیابان دوم',
            'is_default' => true,
        ])->assertStatus(201);

        $this->assertDatabaseHas('addresses', ['id' => $old->id, 'is_default' => false]);
    }

    public function test_user_cannot_delete_another_users_address(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($intruder)->deleteJson("/addresses/{$address->id}")->assertStatus(403);
    }

    public function test_guest_cannot_manage_addresses(): void
    {
        $this->postJson('/addresses', ['receiver_name' => 'x'])->assertStatus(401);
    }
}

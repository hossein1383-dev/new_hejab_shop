<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'receiver_name' => fake()->name(),
            'phone' => '09' . fake()->numerify('#########'),
            'province' => 'تهران',
            'city' => 'تهران',
            'address_line' => fake()->address(),
            'is_default' => true,
        ];
    }
}

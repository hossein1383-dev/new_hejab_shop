<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '09' . fake()->unique()->numerify('#########'),
            'phone_verified_at' => now(),
            'status' => 'active',
            'remember_token' => Str::random(10),
        ];
    }
}

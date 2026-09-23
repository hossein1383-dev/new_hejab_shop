<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name) . '-' . fake()->unique()->numberBetween(1, 100000),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####??')),
            'category_id' => Category::factory(),
            'price' => fake()->numberBetween(100000, 5000000),
            'status' => 'active',
        ];
    }
}

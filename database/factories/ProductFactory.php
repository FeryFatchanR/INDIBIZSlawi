<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Produk '.fake()->unique()->words(2, true);

        return [
            'name' => $name, 'slug' => Str::slug($name), 'short_description' => fake()->sentence(),
            'description' => '<p>'.fake()->paragraph().'</p>', 'status' => ProductStatus::Published, 'benefits' => ['Stabil'],
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => ProductStatus::Draft]);
    }
}

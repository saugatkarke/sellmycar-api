<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'title' => fake()->sentence(3),
            'slug' => fake()->slug(),
            'description' => fake()->paragraph(),
            'stock' => 10,
            'price' => fake()->numberBetween(2000, 100000),
            'year' => fake()->numberBetween(2002, 2026),

            'make' => fake()->randomElement([
                'Toyota',
                'Honda',
                'BYD',
                'Ford',
                'Kia',
                'Mazda'
            ]),

            'model' => fake()->word(),
            'mileage' => fake()->numberBetween(1000, 200000),
            'condition' => 'used',
            'transmission' => 'automatic',
            'fuel_type' => 'petrol',
            'color' => fake()->colorName(),
            'image' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn() => [
            'is_active' => false,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn() => [
            'stock' => 0
        ]);
    }
}

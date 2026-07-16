<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_title' => fake()->senetence(3),

            'product_make' => fake()->word(),

            'product_model' => fake()->word(),

            'product_year' => fake()->numberBetween(2010, 2025),

            'quantity' => 1,

            'price' => 0,

            'subtotal' => 0,
        ];
    }
}

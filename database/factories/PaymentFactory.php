<?php

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => fake()->numberBetween(2010, 2025),
            'provider_payment_id' => fake()->randomDigit(),
            'status' => fake()->word(),
            'total_amount' => fake()->numberBetween(100, 3000),
        ];
    }
}

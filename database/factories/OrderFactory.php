<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'order_number' => '#' . fake()->unique()->bothify('#####'),
            'status' => 'pending',
            'total_amount' => 0,
            'payment_status' => 'unpaid',
            'payment_method' => 'null',
            'shipping_address' => 'null',
            'notes' => 'null',
        ];
    }
}

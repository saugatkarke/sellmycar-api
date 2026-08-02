<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use Laravel\Sanctum\Sanctum;

use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;
    public function test_authenticated_user_can_create_payment(): void
    {
        // Arrange
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total_amount' => 3000,
        ]);

        // Act
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/orders/{$order->id}/payments");

        $response->assertCreated();

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'pending'
        ]);
    }
}

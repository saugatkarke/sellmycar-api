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
    public function test_guest_cannot_create_payment(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total_amount' => 3000,
        ]);

        $response = $this->postJson("/api/orders/{$order->id}/payments");

        $response->assertUnauthorized();
    }

    public function test_user_cannot_create_payment_for_another_user_order(): void
    {
        $auth_user = User::factory()->create();
        $non_auth_user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $non_auth_user->id,
            'status' => 'pending'
        ]);

        Sanctum::actingAs($auth_user);

        $response = $this->postJson("/api/orders/{$order->id}/payments");

        $response->assertNotFound();
    }
    public function test_it_returns_existing_pending_payment(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total_amount' => 3000,
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/orders/{$order->id}/payments");
        $this->postJson("/api/orders/{$order->id}/payments");

        $this->assertDatabaseCount('payments', 1);
    }
}

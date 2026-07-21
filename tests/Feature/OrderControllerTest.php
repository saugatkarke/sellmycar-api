<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_autheticated_user_can_only_see_their_own_order(): void
    {
        $userOne = User::factory()->create();
        $userSecond = User::factory()->create();

        $userOneOrder = Order::factory()->create([
            'user_id' => $userOne->id,
        ]);
        $userSecondOrder = Order::factory()->create([
            'user_id' => $userSecond->id,
        ]);

        Sanctum::actingAs($userOne);

        $response = $this->getJson('/api/orders/');
        $response->assertStatus(200);

        $response->assertJsonFragment([
            'order_id' => $userOneOrder->id,
        ]);
        $response->assertJsonMissing([
            'order_id' => $userSecondOrder->id,
        ]);
        $response->assertJsonCount(1, 'data');
    }

    public function test_user_can_view_order_with_order_items(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock' => 10,
            'price' => 20000
        ]);
        $anotherProduct = Product::factory()->create([
            'stock' => 20,
            'price' => 30000
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_title' => $product->title,
            'price' => $product
                ->price,
            'product_make' => $product->make,
            'product_model' => $product->model,
            'product_year' => $product->year,
            'subtotal' => $product->price * 1,
            'quantity' => 1,
        ]);

        $order->items()->create([
            'product_id' => $anotherProduct->id,
            'product_title' => $anotherProduct->title,
            'price' => $anotherProduct
                ->price,
            'product_make' => $anotherProduct->make,
            'product_model' => $anotherProduct->model,
            'product_year' => $anotherProduct->year,
            'subtotal' => $anotherProduct->price * 1,
            'quantity' => 1,
        ]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/orders/' . $order->id);

        $response->assertStatus(200);

        $response->assertJsonCount(2, 'data.order_items');
        $response->assertJsonPath(
            'data.order_items.0.quantity',
            1
        );
    }

    public function test_order_items_contains_product_snapshot(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock' => 10,
            'price' => 20000
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
        ]);

        $order_items = $order->items()->create([
            'product_id' => $product->id,
            'product_title' => $product->title,
            'price' => $product
                ->price,
            'product_make' => $product->make,
            'product_model' => $product->model,
            'product_year' => $product->year,
            'subtotal' => $product->price * 1,
            'quantity' => 1,
        ]);

        $product->update([
            'title' => 'Changed Product Name',
            'price' => 99999,
        ]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/orders/' . $order->id);

        $response->assertStatus(200);

        $response->assertJsonPath(
            'data.order_items.0.product_title',
            $order_items->product_title
        );

        $response->assertJsonPath(
            'data.order_items.0.price',
            $order_items->price
        );
    }

    public function test_user_cannot_access_another_users_order(): void
    {
        $userOne = User::factory()->create();
        $userTwo = User::factory()->create();

        $orderUserTwo = Order::factory()->create([
            'user_id' => $userTwo->id,
        ]);

        Sanctum::actingAs($userOne);

        $response = $this->getJson('/api/orders/' . $orderUserTwo->id);
        $response->assertStatus(404);
    }

    public function test_guest_user_cannot_access_order(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->getJson('/api/orders/' . $order->id);
        $response->assertStatus(401);
    }
}

<?php

namespace Tests\Feature\Services;

use App\Models\User;
use App\Models\Product;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Event;

class CheckoutServiceTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_it_creates_an_order_successfully(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock' => 10,
            'price' => 20000
        ]);

        $cart = $user->cart()->create();

        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => $product->price,
        ]);

        $order = app(CheckoutService::class)->checkout($user);

        $this->assertInstanceOf(Order::class, $order);

        $this->assertEquals($user->id, $order->user_id);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $user->id
        ]);
    }

    public function test_it_creates_order_item_and_price_snapshot_successfully(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock' => 10,
            'price' => 20000
        ]);

        $cart = $user->cart()->create();

        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => $product->price,
        ]);

        $order = app(CheckoutService::class)->checkout($user);

        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_title' => $product->title,
            'price' => $product->price,
            'quantity' => 2,
            'subtotal' => $product->price * 2,
        ]);
    }

    public function test_it_reduces_product_stock_successfully(): void
    {
        Event::fake();
        $user = User::factory()->create();

        $cart = $user->cart()->create();

        $product = Product::factory()->create([
            'stock' => 10,
            'price' => 10000,
        ]);

        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => $product->price,
        ]);

        app(CheckoutService::class)->checkout($user);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 8,
        ]);
    }

    public function test_it_clears_cart_items_successfully(): void
    {
        Event::fake();
        $user = User::factory()->create();

        $cart = $user->cart()->create();

        $product = Product::factory()->create([
            'stock' => 10,
            'price' => 10000,
        ]);

        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => $product->price,
        ]);

        app(CheckoutService::class)->checkout($user);

        $this->assertDatabaseMissing('cart_items', [
            'cart_id' => $cart->id,
        ]);
    }
}

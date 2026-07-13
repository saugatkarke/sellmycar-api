<?php

use App\Events\OrderPlaced;
use App\Exceptions\ProductUnavailableException;
use App\Exceptions\ProductNotFoundException;
use App\Exceptions\CartEmptyException;
use App\Exceptions\OutOfStockException;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use App\Models\Product;
use App\Models\User;
use App\Models\Order;
use App\Models\CartItem;
use App\Models\OrderItem;

class CheckoutService
{
    /**
     * @throws CartEmptyException
     * @throws OutOfStockException
     * @throws ProductUnavailableException
     */
    public function checkout(User $user): Order
    {
        $cartItems = $this->getCartItems($user);
        $this->validateCheckoutEligibility($cartItems);

        $order = DB::transaction(function () use ($user, $cartItems) {

            $lockedProducts = $this->lockProducts($cartItems);

            $this->ensureStockAvailable($cartItems, $lockedProducts);

            $order = $this->createOrder($user, $cartItems);

            $this->createOrderItems($order, $cartItems, $lockedProducts);

            $this->reduceStock($cartItems, $lockedProducts);

            // 5. clear cart
            $this->clearCart($user);

            return $order;
        });

        OrderPlaced::dispatch($order);

        return $order;
    }

    private function clearCart(User $user): void
    {
        $user->cart()
            ->first()
            ->cartItems()
            ->delete();
    }

    private function createOrderItems(Order $order, EloquentCollection $cartItems, EloquentCollection $lockProducts): void
    {
        foreach ($cartItems as $item) {
            $product = $lockProducts->get($item->product_id);
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_title' => $product->title,
                'product_make' => $product->make,
                'product_model' => $product->model,
                'product_year' => $product->year,
                'quantity' => $item->quantity,
                'price' => $product->price,
                'subtotal' => $product->price * $item->quantity,
            ]);
        }
    }

    private function reduceStock(EloquentCollection $cartItems, EloquentCollection $products): void
    {
        foreach ($cartItems as $item) {
            $product = $products->get($item->product_id);
            $product->decrement('stock', $item->quantity);
        }
    }
    private function lockProducts(EloquentCollection $cartItems): EloquentCollection
    {
        $productIds = $cartItems
            ->pluck('product_id')
            ->unique()
            ->values();

        return Product::query()
            ->whereIn('id', $productIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    private function createOrder(User $user, EloquentCollection $cartItems): Order
    {
        $total = $cartItems->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });
        // 2. create order
        return Order::create([
            'user_id' => $user->id,
            'total_amount' => $total,
            'status' => 'pending',
        ]);
    }

    private function getCartItems(User $user): EloquentCollection
    {
        $cartItems = $user->cartItems()->with('product')->get();
        if ($cartItems->isEmpty()) {
            throw new CartEmptyException();
        }
        return $cartItems;
    }

    private function validateCheckoutEligibility(EloquentCollection $cartItem): void
    {
        foreach ($cartItem as $item) {
            $this->ensureProductExists($item);
            $this->ensureProductIsActive($item);
            $this->ensureValidQuantity($item);
        }
    }
    private function ensureProductExists(CartItem $item): void
    {
        if (!$item->product) {
            throw new  ProductNotFoundException();
        }
    }
    private function ensureProductIsActive(CartItem $item): void
    {
        if (!$item->product->is_active) {
            throw new ProductUnavailableException();
        }
    }
    private function ensureValidQuantity(CartItem $item): void
    {
        if ($item->quantity <= 0) {
            throw new \Exception("Invalid quantity");
        }
    }
    private function ensureStockAvailable(EloquentCollection $cartItems, EloquentCollection $products): void
    {
        foreach ($cartItems as $item) {

            $product = $products->get($item->product_id);

            if ($item->quantity > $product->stock) {
                throw new OutOfStockException();
            }
        }
    }
}

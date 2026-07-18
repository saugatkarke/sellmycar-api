<?php

namespace App\Services;

use App\Exceptions\OutOfStockException;
use App\Exceptions\ProductUnavailableException;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\CartResource;
use App\Models\Product;
use App\Models\Cart;
use App\Helpers\ApiResponse;
use Exception;

class CartService
{

    public function addToCart(int $userId, int $productId, int $quantity = 1)
    {
        return DB::transaction(function () use ($userId, $productId, $quantity) {

            // 1. Get product (source of truth)
            $product = Product::findOrFail($productId);

            if (!$product->is_active) {
                throw new ProductUnavailableException();
            }

            if ($product->stock < $quantity) {
                throw new OutOfStockException();
            }

            // 2. Find or create cart
            $cart = Cart::firstOrCreate([
                'user_id' => $userId
            ]);

            // 3. Check if product already exists in cart
            $cartItem = $cart->items()
                ->where('product_id', $productId)
                ->first();

            if ($cartItem) {

                // update quantity
                $newQuantity = $cartItem->quantity + $quantity;

                if ($product->stock < $newQuantity) {
                    throw new Exception('Not enough stock for updated quantity.');
                }

                $cartItem->update([
                    'quantity' => $newQuantity,
                ]);
            } else {

                // 4. Create new cart item (snapshot price)
                $cart->items()->create([
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'price' => $product->price,
                ]);
            }

            return $cart->load('items.product');
        });
    }

    public function removeItem(int $userId, int $itemId)
    {
        return DB::transaction(function () use ($userId, $itemId) {

            $cart = Cart::where('user_id', $userId)->firstOrFail();

            $cart->items()->where('id', $itemId)->delete();

            return $cart->fresh('items.product');
        });
    }

    public function clearItem(int $userId)
    {
        return DB::transaction(function () use ($userId) {
            $cart = Cart::where('user_id', $userId)->firstOrFail();

            $cart->items()->delete();

            return $cart->fresh('items.product');
        });
    }

    public function index()
    {
        $cart = Cart::with('items.product')
            ->where('user_id', auth()->id())
            ->first();

        return ApiResponse::success(
            new CartResource($cart),
            'Cart fetched successfully'
        );
    }
}

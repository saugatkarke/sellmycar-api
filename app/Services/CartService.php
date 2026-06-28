<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Cart;
use Exception;

class CartService
{

    public function addToCart(int $userId, int $productId, int $quantity = 1)
    {
        return DB::transaction(function () use ($userId, $productId, $quantity) {

            // 1. Get product (source of truth)
            $product = Product::findOrFail($productId);

            if (!$product->is_active) {
                throw new Exception('Product is not available.');
            }

            if ($product->stock < $quantity) {
                throw new Exception('Not enough stock available.');
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
                    'unit_price' => $product->price,
                ]);
            }

            return $cart->load('items.product');
        });
    }
}

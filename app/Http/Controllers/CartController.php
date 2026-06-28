<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\AddToCartRequest;
use App\Services\CartService;

class CartController extends Controller
{
    public function store(AddToCartRequest $addToCartRequest, CartService $cartService)
    {
        $cart = $cartService->addToCart(
            auth()->id(),
            $addToCartRequest->product_id,
            $addToCartRequest->quantity,
        );

        return ApiResponse::success($cart, 'Item added to cart!');
    }
}

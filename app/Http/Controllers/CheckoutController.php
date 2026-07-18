<?php

namespace App\Http\Controllers;

use App\Services\CheckoutService;

class CheckoutController extends Controller
{
    public function store(CheckoutService $checkoutService)
    {
        $user = auth()->user();
        $order = $checkoutService->checkout($user);
        return response()->json(['message' => 'Checkout successful', 'order' => $order]);
    }
}

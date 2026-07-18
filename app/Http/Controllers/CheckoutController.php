<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Resources\OrderResource;
use App\Services\CheckoutService;

class CheckoutController extends Controller
{
    public function store(CheckoutService $checkoutService)
    {
        $user = auth()->user();
        $order = $checkoutService->checkout($user);
        return ApiResponse::success(new OrderResource($order), 'Order placed succesfully!', 201);
    }
}

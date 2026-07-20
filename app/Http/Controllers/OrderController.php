<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Resources\OrderResource;

class OrderController extends Controller
{
    public function index()
    {
        $orders = auth()->user()->orders()->with('items')->get();
        return ApiResponse::success(OrderResource::collection($orders), 'Orders fetched successfully', 200);
    }

    public function show(int $id)
    {
        $order = auth()->user()->orders()->with('items')->findOrFail($id);
        return ApiResponse::success((new OrderResource($order)), 'Order Items fetched successfully', 200);
    }
}

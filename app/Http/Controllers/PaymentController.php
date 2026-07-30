<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Order;
use App\Services\PaymentService;
use App\Http\Resources\PaymentResource;

class PaymentController extends Controller
{
    public function store(Order $order, PaymentService $paymentService)
    {
        $payment = $paymentService->createPayment(auth()->user(), $order);
        return ApiResponse::success(
            new PaymentResource($payment),
            'Payment created successfully.',
            201
        );
    }
}

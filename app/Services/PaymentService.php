<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Order;
use Exception;

class PaymentService
{
    public function createPayment(Order $order): Payment
    {
        if ($order->status !== 'pending') {
            throw new Exception('Order cannot be paid!');
        }

        $existingPayment = $order->payments()->where('status', 'pending')->first();

        if ($existingPayment) {
            return $existingPayment;
        }
        return $order->payments()->create([
            'provider' => 'stripe',
            'status' => 'pending',
            'currency' => 'AUD',
            'total_amount' => $order->total_amount,
        ]);
    }
}

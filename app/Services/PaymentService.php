<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Order;
use App\Models\User;
use Exception;

class PaymentService
{
    public function createPayment(User $user, Order $order): Payment
    {
        if ($order->user_id !== $user->id) {
            throw new Exception('You are not authorised to pay for this order.');
        }
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

<?php

namespace App\Services;

use App\Exceptions\OrderNotPayableException;
use App\Exceptions\UnauthorizedOrderPaymentException;
use App\Models\Payment;
use App\Models\Order;
use App\Models\User;
use Stripe\StripeClient;
use Exception;

class PaymentService
{
    public function createPayment(User $user, Order $order): Payment
    {
        if ($order->user_id !== $user->id) {
            throw new UnauthorizedOrderPaymentException();
        }
        if ($order->status !== 'pending') {
            throw new OrderNotPayableException();
        }

        $existingPayment = $order->payments()->where('status', 'pending')->first();

        if ($existingPayment) {
            return $existingPayment;
        }
        $payment = $order->payments()->create([
            'provider' => 'stripe',
            'status' => 'pending',
            'currency' => 'AUD',
            'total_amount' => $order->total_amount,
        ]);

        $stripe = new StripeClient(config('services.stripe.secret'));

        $intent = $stripe->paymentIntents->create([
            'amount' => (int) round($payment->total_amount * 100),
            'currency' => strtolower($payment->currency),
            'automatic_payment_methods' => [
                'enabled' => true,
            ],

            'metadata' => [
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'user_id' => $user->id,
            ],
        ]);

        $payment->update([
            'payment_provider_id' => $intent->id,
            'status' => $intent->status,
        ]);

        $payment->setAttribute('client_secret', $intent->client_secret);

        return $payment;
    }
}

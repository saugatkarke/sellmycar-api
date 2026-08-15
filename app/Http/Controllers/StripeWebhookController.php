<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                $secret
            );
        } catch (SignatureVerificationException $e) {
            return response()->json([
                'message' => 'invalid webhook signature'
            ], 403);
        }
        $PaymentIntentId = $event->data->object->id;
        $payment = Payment::where('provider_payment_id', $PaymentIntentId)->first();

        if ($event->type == 'payment_intent.succeeded') {
            if ($payment !== null && $payment->status === 'pending') {
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
                $order = $payment->order;

                $order->update([
                    'payment_status' => 'paid'
                ]);
            }
        }

        if ($event->type === 'payment_intent.payment_failed') {
            if ($payment !== null && $payment->status === 'pending') {
                $payment->update(['status' => 'failed']);
            }
        }

        return response()->json([
            'event_type' => $event->type,
            'payment_intent_id' => $event->data->object->id,
            'payment_found' => $payment !== null,
        ]);
    }
}

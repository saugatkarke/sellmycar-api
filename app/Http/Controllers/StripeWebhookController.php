<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');
        $event = Webhook::constructEvent($payload, $signature, $secret);
        $PaymentIntentId = $event->data->object->id;
        $payment = Payment::where('provider_payment_id', $PaymentIntentId)->first();

        if ($event->type == 'payment_intent.succeeded') {
            if ($payment !== null) {
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

        return response()->json([
            'event_type' => $event->type,
            'payment_intent_id' => $event->data->object->id,
            'payment_found' => $payment !== null,
        ]);
    }
}

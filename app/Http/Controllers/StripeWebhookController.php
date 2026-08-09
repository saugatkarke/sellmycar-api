<?php

namespace App\Http\Controllers;

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

        return response()->json([
            'event_type' => $event->type,
            'payment_intent_id' => $event->data->object->id,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\StripeWebhookService;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request, StripeWebhookService $stripeWebhookService)
    {
        try {
            $result = $stripeWebhookService->handleStripeWebhookEvent($request);
        } catch (SignatureVerificationException $e) {
            return response()->json([
                'message' => 'invalid webhook signature'
            ], 403);
        }

        $event = $result['event'];
        $payment = $result['payment'];

        if ($result['duplicate']) {
            return response()->json([
                'message' => 'webhook event already processed',
            ], 200);
        }

        return response()->json([
            'event_type' => $event->type,
            'payment_intent_id' => $event->data->object->id,
            'payment_found' => $payment !== null,
        ]);
    }
}

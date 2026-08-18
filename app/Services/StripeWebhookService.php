<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Payment;
use App\Models\StripeWebhookEvent;
use Illuminate\Http\Request;
use Stripe\Event;
use Stripe\Webhook;

class StripeWebhookService
{
    public function handleStripeWebhookEvent(Request $request)
    {
        $event = $this->verifyStripeWebhookSignature($request);
        if ($this->checkStripeWebhookEventExists($event)) {
            return [
                'event' => $event,
                'payment' => null,
                'duplicate' => true,
            ];
        };

        $payment = $this->getPayment($event);
        $webhookEvent = $this->createStripeWebhookEvent($event, $payment);
        $this->processSuccessfulStripeWebhookEvent($event, $payment);
        $this->processFailedStripeWebhookEvent($event, $payment);
        $webhookEvent->update(['processing_status' => 'processed']);
        return [
            'event' => $event,
            'payment' => $payment
        ];
    }

    private function verifyStripeWebhookSignature(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        return Webhook::constructEvent(
            $payload,
            $signature,
            $secret
        );
    }

    private function checkStripeWebhookEventExists(Event $event): bool
    {
        return StripeWebhookEvent::where('event_id', $event->id)->exists();
    }

    private function getPayment(Event $event): ?Payment
    {
        $PaymentIntentId = $event->data->object->id;
        $payment = Payment::where('provider_payment_id', $PaymentIntentId)->first();
        return $payment;
    }
    private function createStripeWebhookEvent(Event $event, ?Payment $payment): StripeWebhookEvent
    {
        $webhookEvent =  StripeWebhookEvent::create([
            'event_id' => $event->id,
            'event_type' => $event->type,
            'payment_id' => $payment?->id,
            'received_at' => now(),
        ]);
        return $webhookEvent;
    }

    private function processSuccessfulStripeWebhookEvent(Event $event, ?Payment $payment): void
    {
        if ($event->type == 'payment_intent.succeeded') {
            if ($payment !== null && $payment->status === 'pending') {
                DB::transaction(function () use ($payment) {

                    $payment->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);
                    $order = $payment->order;

                    $order->update([
                        'payment_status' => 'paid'
                    ]);
                });
            }
        }
    }
    private function processFailedStripeWebhookEvent(Event $event, ?Payment $payment): void
    {
        if ($event->type === 'payment_intent.payment_failed') {
            if ($payment !== null && $payment->status === 'pending') {
                $payment->update(['status' => 'failed']);
            }
        }
    }
}

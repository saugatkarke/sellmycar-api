<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeWebhookControllerTest extends TestCase
{
    use RefreshDatabase;
    private function generateStripeSignature(string $payload, string $secret): string
    {
        $timestamp = time();

        $signedPayload = $timestamp . '.' . $payload;

        $signature = hash_hmac(
            'sha256',
            $signedPayload,
            $secret
        );

        return "t={$timestamp},v1={$signature}";
    }

    public function test_it_accepts_a_valid_stripe_webhook(): void
    {
        $payload = json_encode([
            'id' => 'ent_test',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                ],
            ],
        ]);

        $signature = $this->generateStripeSignature($payload, config('services.stripe.webhook_secret'));
        $response = $this
            ->call(
                'POST',
                '/api/webhooks/stripe',
                [],
                [],
                [],
                [
                    'HTTP_STRIPE_SIGNATURE' => $signature,
                    'CONTENT_TYPE' => 'application/json',
                ],
                $payload
            );

        // Assert
        $response->assertStatus(200);

        $response->assertJson([
            'event_type' => 'payment_intent.succeeded',
            'payment_intent_id' => 'pi_test_123',
            'payment_found' => false,
        ]);
    }
    public function test_it_updates_payment_as_paid_by_stripe_webhook(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total_amount' => 3000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_test_123',
            'status' => 'pending'
        ]);

        $payload = json_encode([
            'id' => 'ent_test',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                ],
            ],
        ]);

        $signature = $this->generateStripeSignature($payload, config('services.stripe.webhook_secret'));
        $response = $this
            ->call(
                'POST',
                '/api/webhooks/stripe',
                [],
                [],
                [],
                [
                    'HTTP_STRIPE_SIGNATURE' => $signature,
                    'CONTENT_TYPE' => 'application/json',
                ],
                $payload
            );

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'paid',
        ]);

        $this->assertNotNull($payment->fresh()->paid_at);
    }
}

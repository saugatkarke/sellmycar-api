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
    public function test_it_updates_payment_and_order_as_paid_by_stripe_webhook(): void
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
        $this->assertDatabaseHas('orders', [
            'id' => $payment->order_id,
            'payment_status' => 'paid',
        ]);
    }
    public function test_it_handles_failed_payment_by_stripe_webhook(): void
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
            'type' => 'payment_intent.payment_failed',
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
            'status' => 'failed',
        ]);

        $this->assertNull($payment->fresh()->paid_at);
    }

    public function  test_it_does_not_process_duplicate_stripe_webhook_event(): void
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
            'status' => 'pending',
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
        $responseOne = $this
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

        $paidAt = $payment->fresh()->paid_at;
        $responseSecond = $this
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
        $responseOne->assertStatus(200);
        $responseSecond->assertJsonFragment(['message' => 'webhook event already processed']);
        $this->assertEquals(
            $paidAt,
            $payment->fresh()->paid_at
        );

        $this->assertDatabaseCount('stripe_webhook_events', 1);
    }

    public function test_it_doesnot_change_data_for_unknown_stripe_paymentintent_id(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(
            [
                'user_id' => $user->id,
                'status' => 'pending',
                'total_amount' => 3000,
            ]
        );

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_test_123',
            'status' => 'pending',
        ]);

        $payload = json_encode([
            'id' => 'ent_test',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'unknown_test_123',
                ],
            ],
        ]);

        $signature = $this->generateStripeSignature($payload, config('services.stripe.webhook_secret'));

        $response = $this->call(
            'POST',
            'api/webhooks/stripe',
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $payload
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas(
            'payments',
            [
                'status' => 'pending'
            ]
        );
        $this->assertNull($payment->paid_at);
        $this->assertDatabaseCount(
            'payments',
            1
        );
    }
    public function test_it_doesnot_change_data_for_invalid_stripe_signature(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(
            [
                'user_id' => $user->id,
                'status' => 'pending',
                'total_amount' => 3000,
            ]
        );

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_test_123',
            'status' => 'pending',
        ]);

        $payload = json_encode([
            'id' => 'ent_test',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'unknown_test_123',
                ],
            ],
        ]);

        $signature = 'invalid_test_123';

        $response = $this->call(
            'POST',
            'api/webhooks/stripe',
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $payload
        );

        $response->assertStatus(403);

        $this->assertDatabaseHas(
            'payments',
            [
                'status' => 'pending'
            ]
        );
        $this->assertNull($payment->paid_at);
        $this->assertDatabaseCount(
            'payments',
            1
        );
    }
    public function test_it_does_not_change_data_for_unknown_stripe_event_type(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(
            [
                'user_id' => $user->id,
                'status' => 'pending',
                'total_amount' => 3000,
            ]
        );

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_test_123',
            'status' => 'pending',
        ]);

        $payload = json_encode([
            'id' => 'ent_test',
            'type' => 'payment_intent.updated',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                ],
            ],
        ]);

        $signature = $this->generateStripeSignature(
            $payload,
            config('services.stripe.webhook_secret')
        );

        $response = $this->call(
            'POST',
            'api/webhooks/stripe',
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $payload
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas(
            'payments',
            [
                'status' => 'pending'
            ]
        );
        $this->assertNull($payment->paid_at);
        $this->assertDatabaseCount(
            'payments',
            1
        );
    }
    public function test_it_does_not_change_failed_payment_by_successful_stripe_webhook(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(
            [
                'user_id' => $user->id,
                'status' => 'pending',
                'total_amount' => 3000,
            ]
        );

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_test_123',
            'status' => 'failed',
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

        $signature = $this->generateStripeSignature(
            $payload,
            config('services.stripe.webhook_secret')
        );

        $response = $this->call(
            'POST',
            'api/webhooks/stripe',
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $payload
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas(
            'payments',
            [
                'status' => 'failed'
            ]
        );
        $this->assertDatabaseHas('orders', [
            'payment_status' => 'unpaid',
        ]);
        $this->assertNull($payment->paid_at);
        $this->assertDatabaseCount(
            'payments',
            1
        );
    }
    public function test_it_does_not_change_paid_payment_by_failed_stripe_webhook(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(
            [
                'user_id' => $user->id,
                'status' => 'pending',
                'total_amount' => 3000,
                'payment_status' => 'paid',
            ]
        );

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_test_123',
            'status' => 'paid',
            'paid_at' => now()
        ]);

        $payload = json_encode([
            'id' => 'ent_test',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                ],
            ],
        ]);

        $signature = $this->generateStripeSignature(
            $payload,
            config('services.stripe.webhook_secret')
        );

        $response = $this->call(
            'POST',
            'api/webhooks/stripe',
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $payload
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas(
            'payments',
            [
                'status' => 'paid'
            ]
        );
        $this->assertDatabaseHas('orders', [
            'payment_status' => 'paid',
        ]);
        $this->assertNotNull($payment->paid_at);
        $this->assertDatabaseCount(
            'payments',
            1
        );
    }
    public function test_it_does_not_change_unpaid_order_by_failed_stripe_webhook(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(
            [
                'user_id' => $user->id,
                'status' => 'pending',
                'total_amount' => 3000,
            ]
        );

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_test_123',
            'status' => 'pending',
        ]);

        $payload = json_encode([
            'id' => 'ent_test',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                ],
            ],
        ]);

        $signature = $this->generateStripeSignature(
            $payload,
            config('services.stripe.webhook_secret')
        );

        $response = $this->call(
            'POST',
            'api/webhooks/stripe',
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $payload
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas(
            'payments',
            [
                'status' => 'failed'
            ]
        );
        $this->assertDatabaseHas('orders', [
            'payment_status' => 'unpaid',
        ]);
        $this->assertNull($payment->paid_at);
        $this->assertDatabaseCount(
            'payments',
            1
        );
    }
    public function test_it_records_webhook_as_received_before_processing(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(
            [
                'user_id' => $user->id,
                'status' => 'pending',
                'total_amount' => 3000,
            ]
        );

        Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_test_123',
            'status' => 'pending',
        ]);
        $payload = json_encode([
            'id' => 'ent_test',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                ],
            ],
        ]);

        $signature = $this->generateStripeSignature(
            $payload,
            config('services.stripe.webhook_secret')
        );

        $response = $this->call(
            'POST',
            'api/webhooks/stripe',
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $payload
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas(
            'stripe_webhook_events',
            [
                'processing_status' => 'processed'
            ]
        );
    }
}

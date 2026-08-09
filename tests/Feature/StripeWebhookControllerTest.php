<?php

namespace Tests\Feature;

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
        ]);
    }
    // public function test_it_receives_valid_event_type(): void
    // {
    //     $payload = json_encode([
    //         'id' => 'ent_test',
    //         'type' => 'payment_intent.succeeded',
    //         'data' => [
    //             'object' => [
    //                 'id' => 'pi_test_123',
    //             ],
    //         ],
    //     ]);

    //     $signature = $this->generateStripeSignature($payload, config('services.stripe.webhook_secret'));
    //     $response = $this
    //         ->call(
    //             'POST',
    //             '/api/webhooks/stripe',
    //             [],
    //             [],
    //             [],
    //             [
    //                 'HTTP_STRIPE_SIGNATURE' => $signature,
    //                 'CONTENT_TYPE' => 'application/json',
    //             ],
    //             $payload
    //         );

    //     // Assert
    //     $response->assertStatus(200);


    // }
}

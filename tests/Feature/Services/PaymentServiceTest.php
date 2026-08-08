<?php

namespace Tests\Feature\Services;

use App\Exceptions\OrderNotPayableException;
use App\Exceptions\UnauthorizedOrderPaymentException;
use Stripe\StripeClient;

use App\Models\Order;
use App\Models\User;
use App\Models\Payment;
use App\Services\PaymentService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;
    private function mockStripe(int $expectedCalls = 1): StripeClient
    {
        $intent = new \stdClass();
        $intent->id = 'pi_test_123';
        $intent->client_secret = 'pi_test_123_secret';
        $intent->status = 'requires_payment_method';

        $stripe = \Mockery::mock(StripeClient::class);

        $paymentIntents = \Mockery::mock();

        $paymentIntents
            ->shouldReceive('create')
            ->once()
            ->andReturn($intent);

        $stripe->paymentIntents = $paymentIntents;

        $this->app->instance(StripeClient::class, $stripe);
        return $stripe;
    }
    public function test_it_creates_payment_successfully(): void
    {
        //arrange
        $order = Order::factory()->create([
            'status' => 'pending',
            'payment_status' => 'unpaid'
        ]);

        $stripe = $this->mockStripe(1);
        //act
        $payment = app(PaymentService::class)->createPayment($order->user, $order, $stripe);

        //assert
        $this->assertEquals(
            $order->id,
            $payment->order_id
        );
        $this->assertInstanceOf(Payment::class, $payment);
    }

    public function test_it_returns_existing_payment_successfully(): void
    {
        //arrange
        $order = Order::factory()->create([
            'status' => 'pending',
            'payment_status' => 'unpaid'
        ]);

        $stripe = $this->mockStripe(1);

        //act
        app(PaymentService::class)->createPayment($order->user, $order, $stripe);
        app(PaymentService::class)->createPayment($order->user, $order, $stripe);

        //assert
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_it_throws_exception_when_order_is_not_pending(): void
    {
        $order = Order::factory()->create([
            'status' => 'processing',
        ]);

        $stripe = \Mockery::mock(StripeClient::class);
        // don't set ->once() on paymentIntents->create
        $this->expectException(OrderNotPayableException::class);
        app(PaymentService::class)->createPayment($order->user, $order, $stripe);
    }

    public function test_it_cannot_create_payment_for_another_users_order(): void
    {
        $userOne = User::factory()->create();
        $userTwo = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $userTwo->id,
        ]);
        $stripe = \Mockery::mock(StripeClient::class);
        // don't set ->once() on paymentIntents->create
        $this->expectException(UnauthorizedOrderPaymentException::class);

        app(PaymentService::class)
            ->createPayment($userOne, $order, $stripe);
    }
}

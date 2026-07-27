<?php

namespace Tests\Feature\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;
    public function test_it_creates_payment_successfully(): void
    {
        //arrange
        $order = Order::factory()->create([
            'status' => 'pending',
            'payment_status' => 'unpaid'
        ]);

        //act
        $payment = app(PaymentService::class)->createPayment($order);

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

        //act
        app(PaymentService::class)->createPayment($order);

        app(PaymentService::class)->createPayment($order);


        //assert
        $this->assertDatabaseCount('payments', 1);
    }
    public function test_it_throws_exception_when_order_is_not_pending(): void
    {
        $order = Order::factory()->create([
            'status' => 'processing',
        ]);
        $this->expectException(Exception::class);
        app(PaymentService::class)->createPayment($order);
    }
}

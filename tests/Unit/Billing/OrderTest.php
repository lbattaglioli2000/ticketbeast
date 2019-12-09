<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\Concert;
use App\Billing\FakePaymentGateway;
use App\Billing\PaymentFailedException;

use Carbon\Carbon;

class OrderTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function tickets_are_released_when_an_order_is_canceled()
    {
        $concert = factory(Concert::class)->create();
        $concert->addTickets(10);
        $order = $concert->orderTickets('jane@example.com', 5);
        $this->assertEquals(5, $concert->ticketsRemaining());

        $order->cancel();

        $this->assertEquals(10, $concert->ticketsRemaining());
        $this->assertNull($order->fresh());
    }
}
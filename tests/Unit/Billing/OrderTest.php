<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\Concert;
use App\Order;
use App\Billing\FakePaymentGateway;
use App\Billing\PaymentFailedException;

use Carbon\Carbon;

class OrderTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    function converting_to_an_array(){
        // Arrange
        $concert = factory(Concert::class)->create([
            'ticket_price' => 1200
        ])->addTickets(5);

        $order = $concert->orderTickets('jane@example.com', 5);

        // Act
        $result = $order->toArray();

        // Assert
        $this->assertEquals([
            'email' => 'jane@example.com',
            'ticket_quantity' => 5,
            'amount' => 6000
        ], $result);
    }

    /** @test */
    public function tickets_are_released_when_an_order_is_canceled()
    {
        $concert = factory(Concert::class)->create()->addTickets(10);

        $order = $concert->orderTickets('jane@example.com', 5);
        $this->assertEquals(5, $concert->ticketsRemaining());

        $order->cancel();

        $this->assertEquals(10, $concert->ticketsRemaining());
        $this->assertNull($order->fresh());
    }

    /** @test */
    function an_order_can_be_create_from_tickets_email_and_amount(){
        // Arrange
        $concert = factory(Concert::class)->create()->addTickets(5);
        $this->assertEquals(5, $concert->ticketsRemaining());

        // Act
        $order = Order::forTickets($concert->findTickets(3), 'john@example.com', 3600);

        // Assert
        $this->assertEquals('john@example.com', $order->email);
        $this->assertEquals(3, $order->ticketQuantity());
        $this->assertEquals(3600, $order->amount);
        $this->assertEquals(2, $concert->ticketsRemaining());
    }
}
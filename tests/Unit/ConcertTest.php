<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\Concert;
use Carbon\Carbon;

use App\Billing\NotEnoughTicketsException;

class ConcertTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function can_get_formatted_date()
    {
        $concert = factory(Concert::class)->make([
            'date' => Carbon::parse('December 8, 2019')
        ]);

        $this->assertEquals('December 8, 2019', $concert->formatted_date);
    }

    /** @test */
    public function can_get_formatted_start_time()
    {
        $concert = factory(Concert::class)->make([
            'date' => Carbon::parse('December 8, 2019 8:35pm')
        ]);

        $this->assertEquals('8:35pm', $concert->start_time);
    }

    /** @test */
    public function can_get_ticket_price_in_dollars()
    {
        $concert = factory(Concert::class)->make([
            'ticket_price' => 6750
        ]);

        $this->assertEquals('67.50', $concert->formatted_ticket_price);
    }

    /** @test */
    public function concerts_with_a_published_at_date_are_published()
    {
        $publishedConcertA = factory(Concert::class)->create([
            'published_at' => Carbon::parse("-1 week")
        ]);
        $publishedConcertB = factory(Concert::class)->create([
            'published_at' => Carbon::parse("-1 week")
        ]);
        $unpublishedConcert = factory(Concert::class)->create([
            'published_at' => null
        ]);

        $publishedConcerts = Concert::published()->get();

        $this->assertTrue($publishedConcerts->contains($publishedConcertA));
        $this->assertTrue($publishedConcerts->contains($publishedConcertB));
        $this->assertFalse($publishedConcerts->contains($unpublishedConcert));
    }

    /** @test */
    public function can_order_concert_tickets()
    {
        $concert = factory(Concert::class)->create()->addTickets(5);

        $order = $concert->orderTickets('jane@example.com', 3);

        $this->assertEquals('jane@example.com', $order->email);
        $this->assertEquals(3, $order->ticketQuantity());
        $this->assertEquals(2, $concert->ticketsRemaining());
    }

    /** @test */
    public function can_add_tickets()
    {
        $concert = factory(Concert::class)->create();

        $concert->addTickets(50);

        $this->assertEquals(50, $concert->ticketsRemaining());
    }

    /** @test */
    public function tickets_remaining_does_not_include_tickets_associated_with_an_order()
    {
        $concert = factory(Concert::class)->create()->addTickets(50);

        $concert->orderTickets('jane@example.com', 30);

        $this->assertEquals(20, $concert->ticketsRemaining());
    }

    /** @test */
    public function trying_to_purchase_more_tickets_than_remains_throws_an_exception(){

        $concert = factory(Concert::class)->create()->addTickets(10);

        try
        {
            $concert->orderTickets('jane@example.com', 11);
        }
        catch (NotEnoughTicketsException $exception)
        {
            $this->assertFalse($concert->hasOrderFor('jane@example.com'));
            $this->assertEquals(10, $concert->ticketsRemaining());
            return;
        }

        $this->fail();
    }
}

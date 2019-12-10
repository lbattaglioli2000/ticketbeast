<?php
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\Concert;

use App\Billing\PaymentGateway;
use App\Billing\FakePaymentGateway;
use App\Billing\NotEnoughTicketsException;

use Carbon\Carbon;

class PurchaseTicketsTest extends TestCase
{
    use DatabaseMigrations;

    private $paymentGateway;
    protected function setUp()
    {
        parent::setUp();

        $this->paymentGateway = new FakePaymentGateway();
        $this->app->instance(PaymentGateway::class, $this->paymentGateway);
    }

    /*
     * -----------------------
     * HELPER METHODS
     * -----------------------
     */
    private function orderTickets(Concert $concert, $params){
        return $this->json(
            'POST',
            '/concerts/' . $concert->id . '/orders',
            $params);
    }

    /*
     * -----------------------
     * CUSTOM ASSERTIONS
     * -----------------------
     */

    private function assertValidationError($field){
        $this->assertResponseStatus(422);
        $this->assertArrayHasKey($field, $this->decodeResponseJson());
    }

    /*
     * -----------------------
     * THE TEST CHAMBERRRR
     * -----------------------
     */

    /** @test */
    public function a_customer_can_purchase_concert_tickets_for_a_published_concert()
    {
        // Arrange
        $concert = factory(Concert::class)
            ->states('published')
            ->create([
                'ticket_price' => 3250
            ])->addTickets(5);


        // Act
        $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);

        // Assert
        $this->assertResponseStatus(201);

        $this->seeJsonSubset([
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'amount' => 3 * $concert->ticket_price
        ]);

        $this->assertEquals(9750, $this->paymentGateway->totalCharges());
        $this->assertTrue($concert->hasOrderFor('john@example.com'));
        $this->assertEquals(3, $concert->ordersFor('john@example.com')->first()->ticketQuantity());
    }

    /** @test */
    public function cannot_purchase_tickets_to_an_unpublished_concert()
    {
        $concert = factory(Concert::class)->states('unpublished')->create();
        $concert->addTickets(5);

        $this->orderTickets($concert, [
            'email' => 'jane@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);

        $this->assertResponseStatus(404);
        $this->assertFalse($concert->hasOrderFor('jane@example.com'));
        $this->assertEquals(0, $this->paymentGateway->totalCharges());
    }

    /** @test */
    public function an_order_is_not_created_if_payment_fails()
    {
        $concert = factory(Concert::class)->states('published')->create()->addTickets(5);

        $this->orderTickets($concert, [
            'email' => 'jane@example.com',
            'ticket_quantity' => 3,
            'payment_token' => 'invalid-token'
        ]);

        $this->assertResponseStatus(422);
        $this->assertFalse($concert->hasOrderFor('jane@example.com'));
    }

    /** @test */
    public function a_customer_cannot_purchase_more_tickets_than_remain()
    {
        $concert = factory(Concert::class)->states('published')->create();

        $concert->addTickets(50);

        $this->orderTickets($concert, [
            'email' => 'jane@example.com',
            'ticket_quantity' => 51,
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);

        $this->assertResponseStatus(422);

        $this->assertFalse($concert->hasOrderFor('jane@example.com'));
        $this->assertEquals(0, $this->paymentGateway->totalCharges());
        $this->assertEquals(50, $concert->ticketsRemaining());
    }

    /** @test */
    public function cannot_purchase_tickets_that_another_customer_is_trying_to_purchase(){
        // Arrange

        $concert = factory(Concert::class)->create([
            'ticket_price' => 1200
        ])->addTickets(3);

        // Act

        $this->paymentGateway->beforeFirstCharge(function ($paymentGateway) use ($concert){

            $concert->orderTickets($concert, [
                'email' => 'personA@ayyyy.com',
                'ticket_quantity' => 1,
                'payment_token' => $this->paymentGateway->getValidTestToken()
            ]);

            $this->assertResponseStatus(422);
            $this->assertFalse($concert->hasOrderFor('personA@ayyyy.com'));
            $this->assertEquals(0, $this->paymentGateway->totalCharges());

        });

        $concert->orderTickets($concert, [
            'email' => 'personB@beeee.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);


        // Assert

        $this->assertEquals(9750, $this->paymentGateway->totalCharges());
        $this->assertTrue($concert->hasOrderFor('personB@beeee.com'));
        $this->assertEquals(3, $concert->ordersFor('personB@beeee.com')->first()->ticketQuantity());
    }

    /** @test */
    public function email_is_required_to_purchase_tickets()
    {
        $concert = factory(Concert::class)->states('published')->create();

        $this->orderTickets($concert, [
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);

        // 422: Validation Error!
        $this->assertValidationError('email');
    }

    /** @test */
    public function cannot_purchase_tickets_that_have_already_been_purchased()
    {
        $concert = factory(Concert::class)->states('published')->create()->addTickets(10);

        $concert->orderTickets('jane@example.com', 8);

        try
        {
            $concert->orderTickets('john@example.com', 3);

        }
        catch (NotEnoughTicketsException $exception)
        {
            $this->assertFalse($concert->hasOrderFor('john@example.com'));
            $this->assertEquals(2, $concert->ticketsRemaining());
            return;
        }

        $this->fail("You sold tickets that weren't yours to sell in the first place.");
    }
}

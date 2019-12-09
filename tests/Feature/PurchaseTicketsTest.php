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
        $this->disableExceptionHandling();

        // Arrange
        $concert = factory(Concert::class)
            ->states('published')
            ->create([
                'ticket_price' => 3250
            ]);

        $concert->addTickets(5);

        // Act

        $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);

        // Assert

        $this->assertResponseStatus(201);

        $this->assertEquals(9750, $this->paymentGateway->totalCharges());

        $order = $concert->orders()->where('email', 'john@example.com')->first();

        $this->assertNotNull($order);
        $this->assertEquals(3, $order->tickets->count());
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
        $this->assertEquals(0, $concert->orders()->count());
        $this->assertEquals(0, $this->paymentGateway->totalCharges());
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
    public function an_order_is_not_created_if_payment_fails()
    {
        $concert = factory(Concert::class)->states('published')->create();
        $concert->addTickets(5);

        $this->orderTickets($concert, [
            'email' => 'jane@example.com',
            'ticket_quantity' => 3,
            'payment_token' => 'invalid-token'
        ]);

        $this->assertResponseStatus(422);
        $this->assertNull($concert->orders()->where('email', 'jane@example.com')->first());
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

        $order = $concert->orders()->where('email', 'jane@example.com')->first();

        $this->assertNull($order);
        $this->assertEquals(0, $this->paymentGateway->totalCharges());
        $this->assertEquals(50, $concert->ticketsRemaining());
    }

    /** @test */
    public function cannot_purchase_tickets_that_have_already_been_purchased()
    {
        $concert = factory(Concert::class)->states('published')->create();
        $concert->addTickets(10);
        $concert->orderTickets('jane@example.com', 8);

        try
        {
            $concert->orderTickets('john@example.com', 3);

        }
        catch (NotEnoughTicketsException $exception)
        {
            $order = $concert->orders()->where('email', 'john@example.com')->first();
            $this->assertNull($order);
            $this->assertEquals(2, $concert->ticketsRemaining());
            return;
        }

        $this->fail("You sold tickets that weren't yours to sell in the first place.");
    }
}

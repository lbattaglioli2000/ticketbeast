<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\Concert;
use App\Billing\FakePaymentGateway;
use App\Billing\PaymentFailedException;

use Carbon\Carbon;

class FakePaymentGatewayTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function charges_with_a_valid_token_are_successful()
    {
        $paymentGateway = new FakePaymentGateway();

        $paymentGateway->charge(2500, $paymentGateway->getValidTestToken());

        $this->assertEquals(2500, $paymentGateway->totalCharges());
    }

    /** @test */
    public function charges_with_an_invalid_payment_token_fail()
    {
        $paymentGateway = new FakePaymentGateway();

        try
        {
            $paymentGateway->charge(2500, 'not-valid');
        }
        catch (PaymentFailedException $exception)
        {
            return;
        }

        $this->fail();
    }

    /** @test */
    function running_a_hook_before_the_first_charge(){
        // Arrange
        $paymentGateway = new FakePaymentGateway();
        $callbackRan = false;

        // Act
        $paymentGateway->beforeFirstCharge(function($paymentGateway) use (&$callbackRan){
            $callbackRan = true;
            $this->assertEquals(0, $paymentGateway->totalCharges());
        });

        $paymentGateway->charge(2500, $paymentGateway->getValidTestToken());

        // Assert
        $this->assertEquals(2500, $paymentGateway->totalCharges());
        $this->assertTrue($callbackRan);
    }
}
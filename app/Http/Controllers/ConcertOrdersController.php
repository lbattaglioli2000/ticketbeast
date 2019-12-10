<?php

namespace App\Http\Controllers;

use App\Billing\NotEnoughTicketsException;
use App\Billing\PaymentFailedException;
use App\Concert;
use App\Order;
use App\Reservation;
use Illuminate\Http\Request;

use App\Billing\PaymentGateway;

class ConcertOrdersController extends Controller
{
    private $paymentGateway;

    public function __construct(PaymentGateway $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
    }

    public function store($concertId)
    {
        $concert = Concert::published()->findOrFail($concertId);

        $this->validate(request(), [
            'email' => ['required', 'email'],
            'ticket_quantity' => ['required', 'integer', 'min:1'],
            'payment_token' => ['required', 'string']
        ]);

        try {

            // 1. Find some tickets
            $tickets = $concert->findTickets(request('ticket_quantity'));
            $reservation = new Reservation($tickets);

            // 2. Charge the customer
            $this->paymentGateway->charge($reservation->totalCost(), request('payment_token'));

            // 3. Create an order
            $order = Order::forTickets($tickets, request('email'), $reservation->totalCost());

            return response()->json($order->toArray(), 201);
        }
        catch (PaymentFailedException $exception)
        {
            return response()->json([], 422);
        }
        catch (NotEnoughTicketsException $exception)
        {
            return response()->json([], 422);
        }
    }
}

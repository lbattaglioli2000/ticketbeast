<?php

namespace App;

use App\Billing\NotEnoughTicketsException;
use Illuminate\Database\Eloquent\Model;
use App\Order;

class Concert extends Model
{
    /*
     * ----------------------
     * PROTECTED PROPERTIES
     * ----------------------
     */
    protected $guarded = [
        //
    ];
    protected $dates = [
        'date', 'published_at'
    ];

    /*
     * ----------------------
     * QUERY SCOPES
     * ----------------------
     */
    public function scopePublished($query){
        return $query->whereNotNull('published_at');
    }

    /*
     * ----------------------
     * COMPUTED ATTRIBUTES
     * ----------------------
     */
    public function getFormattedDateAttribute(){
        return $this->date->format('F j, Y');
    }
    public function getStartTimeAttribute(){
        return $this->date->format('g:ia');
    }
    public function getFormattedTicketPriceAttribute(){
        return number_format($this->ticket_price / 100, 2);
    }

    /*
     * ----------------------
     * ELOQUENT RELATIONSHIPS
     * ----------------------
     */
    public function orders(){
        return $this->hasMany(Order::class);
    }

    public function tickets(){
        return $this->hasMany(Ticket::class);
    }

    /*
     * ----------------------
     * BUSINESS LOGIC
     * ----------------------
     */

    /**
     * @param $email
     * @param $quantity
     * @return \App\Order
     */
    public function orderTickets($email, $quantity){
        $tickets = $this->tickets()->unsold()->take($quantity)->get();

        if($tickets->count() < $quantity)
            throw new NotEnoughTicketsException();

        $order = $this->orders()->create([
            'email' => $email
        ]);

        foreach ($tickets as $ticket){
            $order->tickets()->save($ticket);
        }

        return $order;
    }


    public function addTickets($quantity){
        foreach(range(1, $quantity) as $i){
            $this->tickets()->create([]);
        }
    }

    public function ticketsRemaining(){
        return $this->tickets()->whereNull('order_id')->count();
    }
}

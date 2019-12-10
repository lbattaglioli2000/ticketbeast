<?php

namespace App;

use App\Billing\NotEnoughTicketsException;
use Illuminate\Database\Eloquent\Model;
use App\Order;
use Illuminate\Support\Collection;

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
        return $this->belongsToMany(Order::class, 'tickets');
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

        $tickets = $this->findTickets($quantity);
        return $this->createOrder($email, $tickets);
    }

    /**
     * @param $quantity
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findTickets($quantity){
        $tickets = $this->tickets()->unsold()->take($quantity)->get();

        if($tickets->count() < $quantity)
            throw new NotEnoughTicketsException();

        return $tickets;
    }

    /**
     * @param $email
     * @param $tickets
     * @return \App\Order
     */
    public function createOrder($email, \Illuminate\Database\Eloquent\Collection $tickets){
        return Order::forTickets($tickets, $email, $tickets->sum());
    }

    /**
     * @param $quantity
     * @return $this
     */
    public function addTickets($quantity){
        foreach(range(1, $quantity) as $i){
            $this->tickets()->create([]);
        }

        return $this;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function ticketsRemaining(){
        return $this->tickets()->whereNull('order_id')->count();
    }

    /**
     * @param $email
     * @return bool
     */
    public function hasOrderFor($email) {
        return $this->orders()->where('email', $email)->count() > 0;
    }

    /**
     * @param $email
     * @return mixed
     */
    public function ordersFor($email) {
        return $this->orders()->where('email', $email)->get();
    }
}

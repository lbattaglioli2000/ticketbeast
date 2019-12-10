<?php

namespace App;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Ticket;

class Order extends Model
{
    /*
     * ----------------------
     * PROTECTED PROPERTIES
     * ----------------------
     */
    protected $fillable = [
        'email', 'concert_id', 'amount'
    ];

    public static function forTickets(Collection $tickets, $email, $amount = null){
        $order = self::create([
            'email' => $email,
            'amount' => $amount === null ? $tickets->sum('price') : $amount,
        ]);

        foreach ($tickets as $ticket){
            $order->tickets()->save($ticket);
        }

        return $order;
    }

    public function tickets() {
        return $this->hasMany(Ticket::class);
    }

    public function concert(){
        return $this->belongsTo(Concert::class);
    }

    public function cancel() {

        foreach ($this->tickets as $ticket){
            $ticket->release();
        }

        $this->delete();
    }

    public function ticketQuantity(){
        return $this->tickets()->count();
    }

    public function toArray()
    {
        return [
            'email' => $this->email,
            'ticket_quantity' => $this->ticketQuantity(),
            'amount' => $this->amount
        ];
    }
}

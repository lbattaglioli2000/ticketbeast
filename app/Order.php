<?php

namespace App;

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
        'email', 'concert_id'
    ];

    public function tickets() {
        return $this->hasMany(Ticket::class);
    }

    public function cancel() {

        foreach ($this->tickets as $ticket){
            $ticket->release();
        }

        $this->delete();
    }
}

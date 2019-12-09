<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Order;

class Ticket extends Model
{
    protected $fillable = [
        'order_id '
    ];

    public function order() {
        return $this->belongsTo(Order::class);
    }

    public function scopeUnsold($query){
        return $query->whereNull('order_id');
    }

    public function release(){
        $this->update(['order_id' => null]);
    }
}

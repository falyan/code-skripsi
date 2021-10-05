<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderProgress extends Model
{
    protected $table = 'order_progress';

    protected $guarded = ['id'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

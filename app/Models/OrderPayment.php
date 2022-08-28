<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderPayment extends Model
{
    protected $table = 'payment';

    protected $guarded = ['id'];

    protected function serializeDate($date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function order()
    {
        return $this->hasOne(Order::class, 'payment_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

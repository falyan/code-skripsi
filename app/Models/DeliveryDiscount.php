<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryDiscount extends Model
{
    protected $table = 'delivery_discount';

    protected $guarded = ['id'];

    protected function serializeDate($date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}

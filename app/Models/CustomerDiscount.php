<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerDiscount extends Model
{
    protected $table = 'customer_discount';

    protected $guarded = ['id'];

    protected function serializeDate($date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}

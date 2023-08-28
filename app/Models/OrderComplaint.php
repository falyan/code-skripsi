<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderComplaint extends Model
{
    protected $table = 'order_complaints';

    protected $guarded = ['id'];

    protected function serializeDate($date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDetailLog extends Model
{
    protected $table = 'order_detail_logs';

    protected $guarded = ['id'];

    protected function serializeDate($date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'order';

    protected $guarded = ['id'];

    protected function serializeDate($date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function progress()
    {
        return $this->hasOne(OrderProgress::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
}

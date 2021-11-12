<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDelivery extends Model
{
    protected $table = 'order_delivery';

    protected $guarded = ['id'];
    protected $appends = ['courier'];

    protected function serializeDate($date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function getCourierAttribute()
    {
        $courier = MasterData::where([
            ['type', 'rajaongkir_courier'],
            ['reference_third_party_id', $this->delivery_method]
        ])->first()->value ?? '';

        return $courier;
    }
}

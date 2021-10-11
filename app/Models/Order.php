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

    public function detail()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function progress()
    {
        return $this->hasMany(OrderProgress::class);
    }

    public function progress_active()
    {
        return $this->hasOne(OrderProgress::class)->where('status',1);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function delivery()
    {
        return $this->hasOne(OrderDelivery::class);
    }

    public function buyer()
    {
        return $this->belongsTo(Customer::class, 'buyer_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

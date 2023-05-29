<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerTiket extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'customer_tiket';
    protected $guarded = ['id'];

    public function master_tiket()
    {
        return $this->belongsTo(MasterTiket::class, 'master_tiket_id', 'id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}

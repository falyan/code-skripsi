<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromoLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'promo_log';

    protected $guarded = ['id'];

    protected $fillable = [
        'order_id',
        'promo_master_id',
        'promo_merchant_id',
        'type',
        'value',
        'created_by',
        'updated_by',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function promo_master()
    {
        return $this->belongsTo(PromoMaster::class, 'promo_master_id');
    }

    public function promo_merchant()
    {
        return $this->belongsTo(PromoMerchant::class, 'promo_merchant_id');
    }
}

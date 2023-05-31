<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromoMerchant extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'promo_merchant';

    protected $guarded = ['id'];

    protected $fillable = [
        'promo_master_id',
        'merchant_id',
        'start_date',
        'end_date',
        'usage_value',
        'max_value',
        'status',
        'created_by',
        'updated_by',
    ];

    public function promo_master()
    {
        return $this->belongsTo(PromoMaster::class, 'promo_master_id');
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }
}

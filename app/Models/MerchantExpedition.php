<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantExpedition extends Model
{
    protected $table = 'merchant_expedition';

    protected $guarded = ['id'];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected function serializeDate($date){
        return $date->format('Y-m-d H:i:s');
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
}

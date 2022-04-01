<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MdrMerchant extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mdr_merchant';
    protected $guarded = ['id'];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];
    protected function serializeDate($date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function category()
    {
        return $this->belongsTo(MasterData::class);
    }

    public function merchant(){
        return $this->belongsTo(Merchant::class);
    }
}

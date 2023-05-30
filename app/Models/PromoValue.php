<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromoValue extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'promo_value';

    protected $guarded = ['id'];

    protected $fillable = [
        'promo_master_id',
        'price_1',
        'price_2',
        'operator',
        'value_1',
        'value_2',
        'status',
        'created_by',
        'updated_by',
    ];

    public function promo_master()
    {
        return $this->belongsToMany(PromoMaster::class, 'promo_master_id');
    }
}

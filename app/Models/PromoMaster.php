<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromoMaster extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'promo_master';

    protected $guarded = ['id'];

    protected $fillable = [
        'name',
        'description',
        'promo_value_type',
        'start_date',
        'end_date',
        'event_type',
        'value_1',
        'value_2',
        'usage_value',
        'min_order_value',
        'max_value',
        'status',
        'created_by',
        'updated_by',
    ];

    public function promo_regions()
    {
        return $this->hasMany(PromoRegion::class);
    }

    public function promo_values()
    {
        return $this->hasMany(PromoValue::class);
    }
}

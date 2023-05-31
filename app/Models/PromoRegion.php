<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromoRegion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'promo_region';

    protected $guarded = ['id'];

    protected $fillable = [
        'promo_master_id',
        'value_type',
        'province_ids',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'province_ids' => 'array',
    ];

    public function promo_master()
    {
        return $this->belongsTo(PromoMaster::class, 'promo_master_id');
    }
}

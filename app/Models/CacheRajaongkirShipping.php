<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CacheRajaongkirShipping extends Model
{
    use HasFactory;

    protected $table = 'cache_rajaongkir_shipping';
    protected $primaryKey = 'key';

    protected $fillable = [
        'key',
        'value',
        'expired_at',
    ];

    protected function serializeDate($date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}

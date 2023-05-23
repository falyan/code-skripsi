<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterTiket extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'master_tiket';
    protected $guarded = ['id'];

    protected $casts = [
        'tnc' => 'array',
    ];

    public function user_tiket()
    {
        return $this->hasMany(CustomerTiket::class, 'master_tiket_id', 'id');
    }

    public function master_data()
    {
        return $this->belongsTo(MasterData::class, 'master_data_key', 'key');
    }
}

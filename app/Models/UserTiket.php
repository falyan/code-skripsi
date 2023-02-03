<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserTiket extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'user_tiket';
    protected $guarded = ['id'];

    public function master_tiket()
    {
        return $this->belongsTo(MasterTiket::class, 'master_tiket_id', 'id');
    }
}

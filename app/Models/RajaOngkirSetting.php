<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RajaOngkirSetting extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'raja_ongkir_setting';

    protected $guarded = ['id'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subdistrict extends Model
{
    use HasFactory;

    protected $table = 'subdistrict';

    protected $guarded = ['id'];

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }
}

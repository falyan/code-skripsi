<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OptionVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'option_variant';
    protected $guarded = [];

    public function master_variant()
    {
        return $this->belongsTo(MasterVariant::class, 'master_variant_id');
    }
}

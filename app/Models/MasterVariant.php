<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'master_variant';
    protected $guarded = [];

    public function option_variants()
    {
        return $this->hasMany(OptionVariant::class, 'master_variant_id');
    }

    public function variants()
    {
        return $this->hasMany(Variant::class, 'master_variant_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VariantStock extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'variant_stock';
    protected $guarded = [];

    public function variant_value_product()
    {
        return $this->belongsTo(VariantValueProduct::class, 'variant_value_product_id');
    }
}

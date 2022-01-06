<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariantStock extends Model
{
    protected $table = 'variant_stock';
    protected $guarded = [];

    public function variant_value_product()
    {
        return $this->belongsTo(VariantValueProduct::class, 'variant_value_product_id');
    }
}

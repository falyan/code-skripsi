<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariantValueProduct extends Model
{
    protected $table = 'variant_value_product';
    protected $guarded = [];

    public function variant_value()
    {
        return $this->belongsTo(VariantValue::class, 'variant_value_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}

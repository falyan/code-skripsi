<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariantValue extends Model
{
    protected $table = 'variant_value';
    protected $guarded = [];

    public function variant()
    {
        return $this->belongsTo(Variant::class, 'variant_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}

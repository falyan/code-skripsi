<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariantSuggestion extends Model
{
    protected $table = 'variant_suggestion';
    protected $guarded = [];

    public function variant()
    {
        return $this->belongsTo(Variant::class, 'variant_id');
    }
}

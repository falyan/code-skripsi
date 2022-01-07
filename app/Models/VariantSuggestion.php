<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VariantSuggestion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'variant_suggestion';
    protected $guarded = [];

    public function variant()
    {
        return $this->belongsTo(Variant::class, 'variant_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Variant extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'variant';
    protected $guarded = [];

    public function category()
    {
        return $this->belongsTo(MasterData::class);
    }

    public function variant_suggestions()
    {
        return $this->hasMany(VariantSuggestion::class, 'variant_id');
    }

    public function variant_values()
    {
        return $this->hasMany(VariantValue::class, 'variant_id');
    }
}

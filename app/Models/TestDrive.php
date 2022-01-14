<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TestDrive extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'test_drive';
    protected $guarded = [];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];
    protected function serializeDate($date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function product()
    {
        return $this->belongsToMany(Product::class, 'test_drive_product', 'test_drive_id', 'product_id');
    }

    public function booking()
    {
        return $this->hasMany(TestDriveBooking::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}

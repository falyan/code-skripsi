<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestDriveProduct extends Model
{
    use HasFactory;

    protected $table = 'test_drive_product';
    protected $guarded = [];

    protected $dates = [
        'created_at',
        'updated_at'
    ];
    protected function serializeDate($date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}

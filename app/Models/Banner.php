<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Banner extends Model
{
    use HasFactory, SoftDeletes;

    public $table = 'banner';

    protected $guarded = ['id'];

    protected $fillable = [
        'url',
        'is_video',
        'type',
        'status',
        'created_by',
        'updated_by',
    ];
}

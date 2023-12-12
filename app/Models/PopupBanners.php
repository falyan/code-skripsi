<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PopupBanners extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'popup_banners';

    protected $guarded = ['id'];

    protected $fillable = [
        'title',
        'image_url',
        'content_url',
        'analytics_tracker',
        'created_by',
        'updated_by',
        'is_active',
        'deleted_by',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IconcashCredential extends Model
{
    use SoftDeletes;

    protected $table = 'iconcash_credentials';

    protected $guarded = ['id'];

    public $rules = [];

    protected $jsonable = ['value'];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function inquiry()
    {
        return $this->hasMany(IconcashInquiry::class);
    }
}

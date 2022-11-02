<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscussionMaster extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'product_discussion_master';
    protected $guarded = ['id'];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    protected function serializeDate($date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function discussion_response()
    {
        return $this->hasMany(DiscussionResponse::class, 'master_discussion_id');
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'discussion_master_id');
    }
}

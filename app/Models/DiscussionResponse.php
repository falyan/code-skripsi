<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscussionResponse extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'product_discussion_response';
    protected $guarded = ['id'];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];
    protected function serializeDate($date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function discussion_master(){
        return $this->belongsTo(DiscussionMaster::class);
    }

    public function customer(){
        return $this->belongsTo(Customer::class);
    }

    public function merchant(){
        return $this->belongsTo(Merchant::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $table = 'report';

    protected $fillable = [
        'product_id',
        'review_id',
        'product_discussion_master_id',
        'product_discussion_response_id',
        'reported_by',
        'reported_user_id',
        'reason',
        'description',
        'report_type',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function review()
    {
        return $this->belongsTo(Review::class);
    }

    public function productDiscussionMaster()
    {
        return $this->belongsTo(DiscussionMaster::class);
    }

    public function productDiscussionResponse()
    {
        return $this->belongsTo(DiscussionResponse::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgentOrderProgres extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'agent_order_progress';
    protected $guarded = ['id'];

    protected function serializeDate($date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function order()
    {
        return $this->belongsTo(AgentOrder::class, 'agent_order_id');
    }
}

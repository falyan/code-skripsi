<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgentOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'agent_orders';
    protected $guarded = ['id'];

    protected function serializeDate($date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }

    public function progress()
    {
        return $this->hasMany(AgentOrderProgres::class, 'agent_order_id');
    }

    public function progress_active()
    {
        return $this->hasOne(AgentOrderProgres::class, 'agent_order_id')->where('status', 1);
    }

    public function payment()
    {
        return $this->hasOne(AgentPayment::class, 'agent_order_id');
    }

    public function payments()
    {
        return $this->hasMany(AgentPayment::class, 'agent_order_id');
    }
}

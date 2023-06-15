<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentMasterData extends Model
{
    protected $table = 'agent_master_data';
    protected $guarded = ['id'];
    protected $casts = [
        'value' => 'integer',
        'fee' => 'integer',
        'status' => 'integer',
    ];

    protected function serializeDate($date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgentMenu extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'agent_menu';
    protected $guarded = ['id'];

    protected function serializeDate($date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function margins()
    {
        return $this->hasMany(AgentMargin::class, 'agent_menu_id');
    }

    public function margin()
    {
        return $this->hasOne(AgentMargin::class, 'agent_menu_id');
    }
}

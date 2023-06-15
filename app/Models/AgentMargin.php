<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgentMargin extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'agent_margin';
    protected $guarded = ['id'];

    protected function serializeDate($date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }

    public function menu()
    {
        return $this->belongsTo(AgentMenu::class, 'agent_menu_id');
    }
}

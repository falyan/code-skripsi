<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentMasterSbu extends Model
{
    use HasFactory;

    protected $table = 'mst_sbu_icon';
    protected $guarded = ['id'];

    public function merchants()
    {
        return $this->hasMany(Merchant::class, 'sbu_id', 'id');
    }
}

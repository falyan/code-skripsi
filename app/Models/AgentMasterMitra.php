<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentMasterMitra extends Model
{
    use HasFactory;

    protected $table = 'mst_mitra';
    protected $guarded = ['id'];

    public function merchants()
    {
        return $this->hasMany(Merchant::class, 'mitra_id', 'id');
    }
}

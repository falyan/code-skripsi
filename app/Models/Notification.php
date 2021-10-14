<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notification';

    protected $guarded = ['id'];
    
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function user_bot()
    {
        return $this->belongsTo(UserBot::class);
    }

    protected function serializeDate($date){
        return $date->format('Y-m-d H:i:s');
    }
}

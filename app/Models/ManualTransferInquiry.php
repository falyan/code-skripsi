<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManualTransferInquiry extends Model
{
    use HasFactory;

    protected $table = 'manual_transfer_inquiry';
    protected $guarded = ['id'];
    protected $dates = [
        'date_expired',
    ];

    protected function serializeDate($date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function payment()
    {
        return $this->belongsTo(OrderPayment::class, 'idpel', 'no_reference');
    }
}

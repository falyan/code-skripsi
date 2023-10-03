<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstallmentOrder extends Model
{
    protected $table = 'pi_order';

    protected $fillable = [
        'customer_id',
        'pi_provider_id',
        'order_id',
        'month_tenor',
        'fee_tenor',
        'installment_tenor',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function provider()
    {
        return $this->belongsTo(InstallmentProvider::class, 'pi_provider_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}

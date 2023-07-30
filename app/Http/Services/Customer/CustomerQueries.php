<?php

namespace App\Http\Services\Customer;

use App\Http\Services\Service;
use App\Models\CustomerAddress;

class CustomerQueries extends Service
{
    public function getListCustomerAddress($customer_id)
    {
        $customer_address = CustomerAddress::with([
            'customer',
            'province',
            'city',
            'district',
            'subdistrict',
        ])
            ->where('customer_id', $customer_id)
            ->orderBy('is_default', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->get();

        return $customer_address;
    }

    public function getDefaultCustomerAddress($customer_id)
    {
        $customer_address = CustomerAddress::with([
            'customer',
            'province',
            'city',
            'district',
            'subdistrict',
        ])
            ->where('customer_id', $customer_id)
            ->where('is_default', true)
            ->first();

        return $customer_address;
    }
}

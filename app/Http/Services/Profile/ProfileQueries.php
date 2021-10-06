<?php

namespace App\Http\Services\Profile;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;

class ProfileQueries
{
    public function getUser()
    {
        // if (request('related_pln_mobile_customer_id')) {
        //     $data = Customer::where('related_pln_mobile_customer_id', request('related_pln_mobile_customer_id'))->first();
        // }
        if (Auth::check()) {
            $data = Customer::where('related_pln_mobile_customer_id', (auth()->user()->related_pln_mobile_customer_id))->first();
        } else {
            $data = null;
        }
        return $data;
    }

    public function getMerchant()
    {
        // if (request('related_pln_mobile_customer_id')) {
        //     $data = $this->getUser()->merchant ? $this->getUser()->merchant : null;
        // }

        if (Auth::check()) {
            $data = $this->getUser()->merchant ? $this->getUser()->merchant : null;
        } else {
            $data = null;
        }
        return $data;
    }
}

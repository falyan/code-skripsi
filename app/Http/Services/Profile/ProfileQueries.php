<?php

namespace App\Http\Services\Profile;

use App\Models\Customer;
use App\Models\Merchant;
use Illuminate\Support\Facades\Auth;

class ProfileQueries
{
    public function getUser()
    {
        if (Auth::check()) {
            $data = Customer::find(Auth::id());
        } else {
            $data = null;
        }
        return $data;
    }

    public function getMerchant()
    {
        if (Auth::check()) {
            if (empty(Auth::user()->merchant_id)) {
                return null;
            }
            $data = Merchant::with(['operationals'])->find(Auth::user()->merchant_id);
            $haveSetupMerchant = count($data->operationals) > 0 ? true : false;
            
            $data = $data->makeHidden('operationals')->toArray();
            $data['haveSetupMerchant'] = $haveSetupMerchant;
        } else {
            $data = null;
        }
        return $data;
    }
}

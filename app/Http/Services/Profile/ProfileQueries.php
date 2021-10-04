<?php

namespace App\Http\Services\Profile;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;

class ProfileQueries
{
    public function getUser()
    {
        return Customer::find(Auth::user()->id);
    }

    public function getMerchant()
    {
        return $this->getUser()->merchant ? $this->getUser()->merchant : null;
    }
}

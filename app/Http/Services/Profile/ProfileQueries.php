<?php

namespace App\Http\Services\Profile;

use App\Http\Services\Service;
use App\Models\Customer;
use App\Models\Merchant;
use Illuminate\Support\Facades\Auth;

class ProfileQueries extends Service
{
    public function getUser()
    {
        if (Auth::check()) {
            $data = Customer::find(Auth::user());
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

    public function validatePassword($password)
    {
        $messages = collect();

        $uppercase = preg_match('@[A-Z]@', $password);
        $lowercase = preg_match('@[a-z]@', $password);
        $number    = preg_match('@[0-9]@', $password);

        if (!$uppercase || !$lowercase || !$number || strlen($password) < 8) {
            $messages->push("kombinasi password harus berisi minimal 8 karakter yang mengandung huruf besar, huruf kecil, dan angka.");
        }

        return $messages;
    }
}

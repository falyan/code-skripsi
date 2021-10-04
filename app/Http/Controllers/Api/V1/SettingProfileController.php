<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;

class SettingProfileController extends Controller
{
    public function __construct()
    {
        $this->getUser = Customer::find(Auth::user()->id);
    }

    public function myProfile()
    {
        try {
            return $this->respondWithData([
                'profile' => $this->getUser,
                'merchant_link' => "http://103.94.6.62/api/pln-marketplace-saruman-development/v1/buyer/query/setting/merchant"
            ], 'Success get your profile information', 200);
        } catch (Exception $e) {
            return $this->respondWithResult(false, $e->getMessage(), 500);
        }
    }

    public function myMerchant()
    {
        try {
            return $this->respondWithData($this->getUser->merchant, 'Success get your merchant information', 200);
        } catch (Exception $e) {
            return $this->respondWithResult(false, $e->getMessage(), 500);
        }
    }
}

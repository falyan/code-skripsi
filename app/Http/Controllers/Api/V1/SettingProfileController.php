<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Profile\ProfileCommands;
use App\Http\Services\Profile\ProfileQueries;
use Illuminate\Support\Facades\Validator;
use Exception;

class SettingProfileController extends Controller
{
    public function __construct()
    {
        $this->profileCommands = new ProfileCommands();
        $this->profileQueries = new ProfileQueries();
    }

    public function index()
    {
        // $validator = Validator::make(request()->all(), [
        //     'related_pln_mobile_customer_id' => 'nullable|exists:cart,related_pln_mobile_customer_id'
        // ]);

        try {
            // if ($validator->fails()) {
            //     throw new Exception($validator->errors(), 400);
            // }

            return $this->respondWithData([
                'user' => $this->profileQueries->getUser(),
                'toko' => $this->profileQueries->getMerchant()
            ], 'Success get your profile information', 200);
        } catch (Exception $e) {
            return $this->respondWithResult(false, $e->getMessage(), 500);
        }
    }
}

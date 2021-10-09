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
        try {
            return $this->respondWithData([
                'user' => $this->profileQueries->getUser(),
                'toko' => $this->profileQueries->getMerchant()
            ], 'Success get your profile information', 200);
        } catch (Exception $e) {
            return $this->respondWithResult(false, $e->getMessage(), 500);
        }
    }
}

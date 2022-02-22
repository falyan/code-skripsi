<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Mdr\MdrQueries;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MdrController extends Controller
{
    public function __construct()
    {
        $this->mdrQuery = new MdrQueries();
    }

    public function getMdrValue($category_id){
        try {
            return $this->mdrQuery->getMdrValue(Auth::user()->merchant_id, $category_id);
        }catch (Exception $e){
            return $this->respondErrorException($e, request());
        }

    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Merchant\MerchantCommands;
use App\Http\Services\Merchant\MerchantQueries;
use App\Models\Customer;
// use App\Models\Customer;
use App\Models\Merchant;
use App\Models\User;
use Exception, Input;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MerchantController extends Controller
{
    public function aturToko()
    {
        $validator = Validator::make(request()->all(), [
            'slogan' => 'required',
            'description' => 'required',
            'operational' => 'required'
        ]);

        try {
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validasi gagal', 'data' => $validator->errors()], 400);
            };
            
            return MerchantCommands::aturToko(request()->all(), Auth::user()->merchant_id);
        } catch (\Throwable $th) {
            if (in_array($th->getCode(), $this->error_codes)) {
                return response()->json(['error' => ['code' => 'ERROR', 'http_code' => $th->getCode(), 'message' => $th->getMessage()]], $th->getCode());
            }
            return response()->json(['error' => ['code' => 'ERROR', 'http_code' => $th->getCode(), 'message' => $th->getMessage()]], 404);
        }
    }

    public function homepageProfile()
    {
        try {
            return MerchantQueries::homepageProfile(Auth::user()->merchant_id);
        } catch (Exception $th) {
            if (in_array($th->getCode(), $this->error_codes)) {
                return $this->respondWithResult(false, $th->getMessage(), $th->getCode());
            }
            return $this->respondWithResult(false, $th->getMessage(), 500);
        }
    }

    public function publicProfile($merchant_id)
    {
        try {
            return $this->respondWithData(MerchantQueries::publicProfile($merchant_id), 'Berhasil mendapatkan data toko');
        } catch (Exception $th) {
            if (in_array($th->getCode(), $this->error_codes)) {
                return $this->respondWithResult(false, $th->getMessage(), $th->getCode());
            }
            return $this->respondWithResult(false, $th->getMessage(), 500);
        }
    }

    public function setExpedition()
    {
        $validator = Validator::make(request()->all(), [
            'list_expeditions' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondValidationError($validator->errors(), 'Validation Error!');
        }

        try {
            MerchantCommands::createOrUpdateExpedition(request()->get('list_expeditions'));
            return $this->respondWithData(Merchant::with('expedition')->where('id', Auth::user()->merchant->id)->firstOrFail(), 'Layanan ekspedisi berhasil disimpan');
        } catch (Exception $th) {
            if (in_array($th->getCode(), $this->error_codes)) {
                return $this->respondWithResult(false, $th->getMessage(), $th->getCode());
            }
            return $this->respondWithResult(false, $th->getMessage(), 500);
        }
    }
}
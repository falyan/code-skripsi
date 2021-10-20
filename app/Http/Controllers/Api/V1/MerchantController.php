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
use Illuminate\Http\Request;
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
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function homepageProfile()
    {
        try {
            return MerchantQueries::homepageProfile(Auth::user()->merchant_id);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function publicProfile($merchant_id)
    {
        try {
            return $this->respondWithData(MerchantQueries::publicProfile($merchant_id), 'Berhasil mendapatkan data toko');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
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
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function aturLokasi(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'address' => 'required|min:3',
            'province_id' => 'required|exists:province,id',
            'city_id' => 'required|exists:city,id',
            'district_id' => 'required|exists:district,id',
            'postal_code' => 'required|max:5',
            'longitude' => 'nullable',
            'latitude' => 'nullable',
        ]);

        if ($validator->fails()) {
            return $this->respondValidationError($validator->errors(), 'Validation Error!');
        }

        try {
            request()->request->add([
                'full_name' => Auth::user()->full_name
            ]);
            $data = MerchantCommands::updateLokasi($request, Auth::user()->merchant_id);
            return $this->respondWithData($data, 'Layanan ekspedisi berhasil disimpan');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function requestMerchantList(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'key' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondValidationError($validator->errors(), 'Validation Error!');
        }

        try {
            // return \Illuminate\Support\Str::random(32);
            $key = "mbfxuavEyTjtfOGNR2bwrVlkgRnBsqUO";

            if ($request->key != $key) {
                return $this->respondValidationError(['key' => 'Your key is invalid'], 'Validation Error!');
            }

            $data = MerchantQueries::getListMerchant($request);
            return $this->respondWithData($data, 'Berhasi mendapatkan data ist merchant');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }
}

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
        ], [
            'required' => ':attribute diperlukan.'
        ]);

        try {
            if ($validator->fails()) {
                $errors = collect();
                foreach ($validator->errors()->getMessages() as $key => $value) {
                    foreach ($value as $error) {
                        $errors->push($error);
                    }
                }
                return $this->respondValidationError($errors, 'Validation Error!');
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
        ], [
            'required' => 'Minimal harus pilih 1 expedisi.'
        ]);

        if ($validator->fails()) {
            $errors = collect();
            foreach ($validator->errors()->getMessages() as $key => $value) {
                foreach ($value as $error) {
                    $errors->push($error);
                }
            }
            return $this->respondValidationError($errors, 'Validation Error!');
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
        $validator = Validator::make(
            request()->all(),
            [
                'address' => 'required|min:3',
                'province_id' => 'required|exists:province,id',
                'city_id' => 'required|exists:city,id',
                'district_id' => 'required|exists:district,id',
                'postal_code' => 'required|max:5',
                'longitude' => 'nullable',
                'latitude' => 'nullable',
            ],
            [
                'exists' => 'ID :attribute tidak ditemukan.',
                'required' => ':attribute diperlukan.',
                'max' => 'panjang :attribute maksimum :max karakter.',
                'min' => 'panjang :attribute minimum :min karakter.',
            ]
        );

        if ($validator->fails()) {
            $errors = collect();
            foreach ($validator->errors()->getMessages() as $key => $value) {
                foreach ($value as $error) {
                    $errors->push($error);
                }
            }
            return $this->respondValidationError($errors, 'Validation Error!');
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
        ], [
            'required' => ':attribute diperlukan.'
        ]);

        if ($validator->fails()) {
            $errors = collect();
            foreach ($validator->errors()->getMessages() as $key => $value) {
                foreach ($value as $error) {
                    $errors->push($error);
                }
            }
            return $this->respondValidationError($errors, 'Validation Error!');
        }

        try {
            // return \Illuminate\Support\Str::random(32);
            $key = "mbfxuavEyTjtfOGNR2bwrVlkgRnBsqUO";

            if ($request->key != $key) {
                return $this->respondValidationError(['key' => 'Your key is invalid'], 'Validation Error!');
            }

            $limit = $request->limit ?? 10;
            $page = $request->page ?? 1;

            $data = MerchantQueries::getListMerchant($limit, $page);
            return $this->respondWithData($data, 'Berhasi mendapatkan data ist merchant');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function updateMerchantProfile(Request $request){
        try {
            if (Auth::check()){
                $merchantCommand = new MerchantCommands();
                return $merchantCommand->updateMerchantProfile(Auth::user()->merchant_id, $request);
            }
        }catch (Exception $e){
            return $this->respondErrorException($e, request());
        }
    }
}

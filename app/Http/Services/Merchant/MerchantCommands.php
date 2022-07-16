<?php

namespace App\Http\Services\Merchant;

use App\Http\Services\Service;
use App\Models\Etalase;
use App\Models\Merchant;
use App\Models\MerchantBanner;
use App\Models\MerchantExpedition;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MerchantCommands extends Service
{
    public static function aturToko($request, $merchant_id){
        try {
            DB::beginTransaction();
            $merchant = Merchant::find($merchant_id);
            $merchant->update([
                'slogan' => data_get($request, 'slogan'),
                'description' => data_get($request, 'description')
            ]);

            $merchant->operationals()->delete();

            foreach (data_get($request, 'operational') as $key) {
                if (array_key_exists('day_id', $key)) {
                    $key['master_data_id'] = $key['day_id'];
                    unset($key['day_id']);
                }
                $key['open_time'] = data_get($request, 'open_time');
                $key['closed_time'] = data_get($request, 'closed_time');
                $merchant->operationals()->create($key);
            }
            DB::commit();

            return [
                'merchant' => $merchant,
                'operationals' => $merchant->operationals()->get()
            ];
        } catch (\Throwable $th) {
            DB::rollBack();
            if (in_array($th->getCode(), self::$error_codes)) {
                throw new Exception($th->getMessage(), $th->getCode());
            }
            throw new Exception($th->getMessage(), 500);
        }
    }

    public static function updateLokasi($request, $merchant_id)
    {
        $merchant = Merchant::findOrFail($merchant_id);
        $merchant->name = strtoupper($merchant->name);
        $merchant->phone_office = $merchant->phone_office;
        $merchant->corporate_id = $merchant->corporate_id;
        $merchant->address = $request->address == null ? $merchant->address : $request->address;
        $merchant->province_id = $request->province_id == null ? $merchant->province_id : $request->province_id;
        $merchant->city_id = $request->city_id == null ? $merchant->city_id : $request->city_id;
        $merchant->district_id = $request->district_id == null ? $merchant->district_id : $request->district_id;
        $merchant->postal_code = $request->postal_code == null ? $merchant->postal_code : $request->postal_code;
        $merchant->longitude = $request->longitude == null ? $merchant->longitude : $request->longitude;
        $merchant->latitude = $request->latitude == null ? $merchant->latitude : $request->latitude;
        $merchant->email = strtolower($merchant->email);
        $merchant->photo_url = $merchant->photo_url;
        $merchant->pic_id = $merchant->pic_id;
        $merchant->nib_url = $request->nib_url;
        $merchant->registration_form_url = $merchant->registration_form_url;
        $merchant->status = $merchant->status;
        $merchant->is_open = $merchant->is_open;
        $merchant->created_by = $merchant->created_by;
        $merchant->updated_by = $request->full_name;
        $merchant->reference_customer_id = $merchant->reference_customer_id;

        if ($merchant->save()) {
            return $merchant;
        } else {
            return false;
        }
    }

    public static function createOrUpdateExpedition($list_expeditions)
    {
        try {
            DB::beginTransaction();

            if (!$merchant = Auth::user()->merchant) {
                throw new Exception('User tidak memiliki merchant.');
            }

            MerchantExpedition::updateOrCreate(
                ['merchant_id' => $merchant->id],
                ['merchant_id' => $merchant->id, 'list_expeditions' => $list_expeditions]
            );
            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            if (in_array($th->getCode(), self::$error_codes)) {
                throw new Exception($th->getMessage(), $th->getCode());
            }
            throw new Exception($th->getMessage(), 500);
        }
    }

    public function updateMerchantProfile($merchant_id, $data){
        $merchant = Merchant::findOrFail($merchant_id);
        $merchant->photo_url = ($data->photo_url == null) ? ($merchant->photo_url) : ($data->photo_url);
        $merchant->name = ($data->name == null) ? ($merchant->name) : ($data->name);

        if (!$merchant->save()){
            $response['success'] = false;
            $response['message'] = 'Gagal mengubah profil merchant';
            $response['data'] = $merchant;

            return $response;
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil mengubah profil merchant';
        $response['data'] = $merchant;
        return $response;
    }

    public function setCustomLogistic($merchant_id){
        $merchant = Merchant::find($merchant_id);
        if ($merchant == null){
            $response['success'] = false;
            $response['message'] = 'Gagal mendapatkan data merchant';
            $response['data'] = $merchant;
            return $response;
        }

        if ($merchant->has_custom_logistic == false || $merchant->has_custom_logistic == null){
            $merchant->has_custom_logistic = true;
            if ($merchant->save()){
                $response['success'] = true;
                $response['message'] = 'Berhasil mengaktifkan custom logistic';
                $response['data'] = $merchant;
                return $response;
            }
        }

        if ($merchant->has_custom_logistic == true){
            $merchant->has_custom_logistic = false;
            if ($merchant->save()){
                $response['success'] = true;
                $response['message'] = 'Berhasil menonaktifkan custom logistic';
                $response['data'] = $merchant;
                return $response;
            }
        }
    }

    public function createBanner($request, $merchant_id)
    {
        try {
            $banner =  MerchantBanner::create([
                'merchant_id' => $merchant_id,
                'url' => $request->url,
                'created_by' => $request->full_name,
                'updated_by' => $request->full_name
            ]);
            DB::commit();

            return [
                'success' => true,
                'message' => 'Berhasil menambah banner',
                'data' => $banner
            ];
        } catch (\Exception $th) {
            DB::rollBack();
            if (in_array($th->getCode(), self::$error_codes)) {
                throw new Exception($th->getMessage(), $th->getCode());
            }
            throw new Exception($th->getMessage(), 500);
        }
    }
    
    public function deleteBanner($banner_id, $merchant_id)
    {
        try {
            $item = MerchantBanner::where([
                'id' => $banner_id,
                'merchant_id' => $merchant_id
            ])->first();
            if ($item == null) {
                throw new Exception('Banner tidak ditemukan', 400);
            }
            DB::beginTransaction();
            $item->delete();
            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            throw new Exception($th->getMessage(), $th->getCode());
        }
    }
}

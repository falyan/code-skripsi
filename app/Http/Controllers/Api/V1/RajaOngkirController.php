<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Rajaongkir\RajaongkirResources;
use App\Http\Services\Manager\RajaOngkirManager;
use App\Models\City;
use App\Models\District;
use App\Models\MasterData;
use App\Models\Province;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RajaOngkirController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->rajaongkirManager = new RajaOngkirManager();
    }

    public function getProvince($province_id = null)
    {
        try {
            return RajaOngkirManager::getProvinces($province_id);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getDistrict($district = null, $city_id = null)
    {
        try {
            return RajaOngkirManager::getSubdistrict($district, $city_id);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function injectProvince()
    {
        try {
            DB::beginTransaction();

            array_map(function ($item) {
                Province::firstOrCreate([
                    'name' => data_get($item, 'province'),
                    'rajaongkir_province_id' => data_get($item, 'province_id')
                ]);
            }, RajaOngkirManager::getProvinces());

            DB::commit();
            return $this->respondWithResult(true, 'Success inject data');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respondErrorException($e, request());
        }
    }
    public function injectCity()
    {
        try {
            set_time_limit(0);
            DB::beginTransaction();

            array_map(function ($item) {
                City::firstOrCreate([
                    'name' => data_get($item, 'city_name'),
                    'province_id' => (int) data_get($item, 'province_id'),
                    'rajaongkir_city_id' => data_get($item, 'city_id')
                ]);
            }, RajaOngkirManager::getCities());

            DB::commit();
            return $this->respondWithResult(true, 'Success inject data');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respondErrorException($e, request());
        }
    }

    public function injectDistrict()
    {
        try {
            set_time_limit(0);
            DB::beginTransaction();
            $cities = City::all()->count();
            for ($i = 1; $i <= $cities; $i++) {
                array_map(function ($item) {
                    District::firstOrCreate([
                        'name' => data_get($item, 'subdistrict_name'),
                        'city_id' => (int) data_get($item, 'city_id'),
                        'rajaongkir_district_id' => data_get($item, 'subdistrict_id')
                    ]);
                }, RajaOngkirManager::getSubdistrict(null, $i));
            }
            DB::commit();
            return $this->respondWithResult(true, 'Success inject data');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respondErrorException($e, request());
        }
    }

    public function updateCity()
    {
        try {
            set_time_limit(0);

            DB::beginTransaction();
            array_map(function ($item) {
                City::where('name', data_get($item, 'city_name'))->update([
                    'rajaongkir_city_id' => data_get($item, 'city_id')
                ]);
            }, RajaOngkirManager::getCities());
            DB::commit();

            return $this->respondWithResult(true, 'Success update data');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respondErrorException($e, request());
        }
    }

    public function ongkir()
    {
        $validator = Validator::make(request()->all(), [
            'origin_district_id' => 'required',
            'destination_district_id' => 'required',
            'weight' => 'required',
            'courier' => 'required',
            'length' => 'sometimes',
            'width' => 'sometimes',
            'height' => 'sometimes',
            'diameter' => 'sometimes',
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
            return RajaOngkirManager::getOngkir(request()->only(['origin_district_id', 'destination_district_id', 'weight', 'courier']));
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function couriers()
    {
        try {
            return [
                'data' => array_map(function ($item) {
                    return [
                        'name' => data_get($item, 'value'),
                        'value' => data_get($item, 'reference_third_party_id'),
                        'logo' => data_get($item, 'photo_url')
                    ];
                }, MasterData::where('type', 'rajaongkir_courier')->orderBy('value', 'ASC')->get()->toArray())
            ];
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function trackOrder()
    {
        try {
            $trx_no = request()->input('trx_no');
            return response()->json(['data' => $this->rajaongkirManager->trackOrder($trx_no)]);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }
}

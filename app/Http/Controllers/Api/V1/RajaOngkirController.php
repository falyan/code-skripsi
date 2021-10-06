<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Manager\RajaOngkirManager;
use App\Models\City;
use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RajaOngkirController extends Controller
{
    public function getProvince($province_id = null)
    {
        try {
            return RajaOngkirManager::getProvinces($province_id);
        } catch (Exception $th) {
            if (in_array($th->getCode(), $this->error_codes)) {
                return $this->respondWithResult(false, $th->getMessage(), $th->getCode());
            }
            return $this->respondWithResult(false, $th->getMessage(), 500);
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
        } catch (Exception $th) {
            DB::rollBack();
            if (in_array($th->getCode(), $this->error_codes)) {
                return $this->respondWithResult(false, $th->getMessage(), $th->getCode());
            }
            return $this->respondWithResult(false, $th->getMessage(), 500);
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
                    'province_id' => (int) data_get($item, 'province_id')
                ]);
            }, RajaOngkirManager::getCities());

            DB::commit();
            return $this->respondWithResult(true, 'Success inject data');
        } catch (Exception $th) {
            DB::rollBack();
            if (in_array($th->getCode(), $this->error_codes)) {
                return $this->respondWithResult(false, $th->getMessage(), $th->getCode());
            }
            return $this->respondWithResult(false, $th->getMessage(), 500);
        }
    }
}

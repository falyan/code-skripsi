<?php

namespace App\Http\Services\Region;

use App\Http\Services\Service;
use App\Models\City;
use App\Models\District;
use App\Models\Province;

class RegionQueries extends Service
{
    public function searchDistrict($keyword, $limit = 10){
        if (strlen($keyword) < 3){
            return false;
        }

        $district = District::with(['city' => function($city)
        {$city->with(['province']);}])->where('name', 'ILIKE', '%'.$keyword.'%')->paginate($limit);

        if ($district->isEmpty()){
            $response['success'] = false;
            $response['message'] = 'Gagal mendapatkan data region!';
            return $response;
        }
        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data region!';
        $response['data'] = $district;
        return $response;
    }


    public function searchProvince($keyword = null, $limit = 10){
        if ($keyword == null){
            $provinces = Province::paginate($limit);
        } else {
            $provinces = Province::where('name', 'ILIKE', '%'. $keyword .'%')->paginate($limit);
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data provinsi';
        $response['data'] = $provinces;
        return $response;
    }

    public function searchCity($province_id, $keyword = null, $limit = 10){
        if ($keyword == null){
            $cities = City::where('province_id', $province_id)->paginate($limit);
        } else {
            $cities = City::where('province_id', $province_id)->where('name', 'ILIKE', '%'. $keyword .'%')->paginate($limit);
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data kota';
        $response['data'] = $cities;
        return $response;
    }
}

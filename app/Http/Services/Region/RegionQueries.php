<?php

namespace App\Http\Services\Region;

use App\Http\Services\Service;
use App\Models\District;

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
}

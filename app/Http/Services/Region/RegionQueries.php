<?php

namespace App\Http\Services\Region;

use App\Models\District;

class RegionQueries{
    public function searchDistrict($keyword){
        $district = District::with(['city' => function($city)
        {$city->with(['province']);}])->where('name', 'ILIKE', '%'.$keyword.'%')->get();

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

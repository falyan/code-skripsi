<?php

namespace App\Http\Services\Mdr;

use App\Models\MasterData;
use App\Models\MdrCategory;
use App\Models\MdrMerchant;

class MdrQueries{
    public function getMdrValue($merchant_id, $category_id){
        $basic_category = MasterData::whereHas('child', function ($parent) use ($category_id){
            $parent->whereHas('child', function ($child) use ($category_id){
                $child->where('id', $category_id);
            });
        })->first();

        if (!$basic_category || $basic_category = null){
            $response['success'] = false;
            $response['message'] = 'Data category gagal didapatkan';
            $response['data'] = $basic_category;

            return $response;
        }

        $mdr_value = MdrMerchant::where('merchant_id', $merchant_id)->where('category_id', $basic_category->id)->where('status', 1)->first();
        if ($mdr_value == null){
            $mdr_value = MdrCategory::where('category_id', $basic_category->id)->where('status', 1)->first();
            if ($mdr_value == null){
                $response['success'] = false;
                $response['message'] = 'Data mdr gagal didapatkan';
                $response['data'] = $mdr_value;

                return $response;
            }
        }

        $response['success'] = true;
        $response['message'] = 'Data mdr berhasil didapatkan';
        $response['data'] = $mdr_value;

        return $response;
    }
}

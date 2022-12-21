<?php

namespace App\Http\Services\Mdr;

use App\Models\MasterData;
use App\Models\MdrCategory;
use App\Models\MdrMerchant;

class MdrQueries
{
    public function getMdrValue($merchant_id, $category_id)
    {
        // $basic_category = MasterData::whereHas('child', function ($parent) use ($category_id) {
        //     $parent->whereHas('child', function ($child) use ($category_id) {
        //         $child->where('id', $category_id);
        //     });
        // })->first();

        $basic_category = MasterData::find($category_id);
        $is_parent = $basic_category->parent_id == null;
        while ($is_parent === false) {
            $basic_category = MasterData::where('id', $basic_category->parent_id)->first();
            if ($basic_category->parent_id == null) {
                $is_parent = true;
                break;
            }
        }

        if (!$basic_category || empty($basic_category)) {
            $response['success'] = false;
            $response['message'] = 'Data category gagal didapatkan';
            $response['data'] = $basic_category;

            return $response;
        }

        $mdr_value = MdrMerchant::where('merchant_id', $merchant_id)->where('category_id', $basic_category->id)->where('status', 1)->first();
        if ($mdr_value == null) {
            $mdr_value = MdrCategory::where('category_id', $basic_category->id)->where('status', 1)->first();
            if ($mdr_value == null) {
                $response['success'] = false;
                $response['message'] = 'Data mdr gagal didapatkan';
                $response['data'] = $mdr_value;

                return $response;
            } else {
                $mdr_value->value = $mdr_value->value == null ? 0 : (float)$mdr_value->value;
            }
        } else {
            $mdr_value->value = $mdr_value->value == null ? 0 : (float)$mdr_value->value;
        }

        $response['success'] = true;
        $response['message'] = 'Data mdr berhasil didapatkan';
        $response['data'] = $mdr_value;

        return $response;
    }
}

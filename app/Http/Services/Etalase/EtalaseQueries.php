<?php

namespace App\Http\Services\Etalase;

use App\Http\Resources\Etalase\EtalaseCollection;
use App\Http\Resources\Etalase\EtalaseResource;
use App\Http\Services\Service;
use App\Models\Etalase;
use App\Models\Product;

class EtalaseQueries extends Service
{
    public static function getAll($id){
        $paginate = Etalase::where('merchant_id', $id)->paginate(10);
        return new EtalaseCollection($paginate);
    }

    public static function getproductMerchantEtalaseId($merchant_id, $etalase_id){
        $paginate = Product::where([
            'etalase_id' => $etalase_id,
            'merchant_id' => $merchant_id
        ])->paginate(10);
        return new EtalaseCollection($paginate);
    }

    public static function getById($id)
    {
        $row = Etalase::where('id', $id)->first();
        return new EtalaseResource($row);
    }
}

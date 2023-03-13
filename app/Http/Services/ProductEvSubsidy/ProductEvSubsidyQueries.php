<?php

namespace App\Http\Services\ProductEvSubsidy;

use App\Http\Services\Service;
use App\Models\ProductEvSubsidy;

class ProductEvSubsidyQueries extends Service
{
    public function getData()
    {
        $ev_products = ProductEvSubsidy::where([
            'merchant_id' => auth()->user()->merchant_id,
        ])->with('product')->get();

        return $ev_products;
    }
}

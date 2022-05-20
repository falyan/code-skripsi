<?php

namespace App\Http\Services\ProductCategoryApproval;

use App\Models\MasterData;
use App\Models\ProductCategoryApproval;
use App\Models\Variant;
use App\Models\VariantValueProduct;

class ProductCategoryApprovalQueries
{
    public function checkCategoryKey($category_key)
    {
        $productCategoryApproval = ProductCategoryApproval::where('category_key', $category_key)->first();
        $response = [
            'success' => true,
            'message' => 'Successfully checking category approval!',
            'need_approval' => !empty($productCategoryApproval),
        ];
        return $response;
    }
}

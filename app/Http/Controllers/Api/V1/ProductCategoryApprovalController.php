<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Http\Controllers\Controller;
use App\Http\Services\ProductCategoryApproval\ProductCategoryApprovalQueries;

class ProductCategoryApprovalController extends Controller
{
    protected $productCategoryApprovalQueries;

    public function __construct()
    {
        $this->productCategoryApprovalQueries = new ProductCategoryApprovalQueries();
    }

    public function checkCategory($category_key)
    {
        try {
            return $this->productCategoryApprovalQueries->checkCategoryKey($category_key);
        } catch (Exception   $e) {
            return $this->respondErrorException($e, request());
        }
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Variant\VariantQueries;
use Exception;

class VariantController extends Controller
{
    protected $variantCommands, $variantQueries;

    public function __construct()
    {
        $this->variantQueries = new VariantQueries();
    }

    public function getVariantById($id)
    {
        try {
            return $this->variantQueries->find($id);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getVariantByCategory($category_id)
    {
        try {
            return $this->variantQueries->getByCategory($category_id);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getVariantByProduct(string $variant_value_id)
    {
        try {
            if (!$variant_value_id) {
                return [
                    'success' => false,
                    'message' => 'Variant value tidak boleh kosong!'
                ];
            }

            return VariantQueries::detailVariantByProduct($variant_value_id);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }
}

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
        $this->variant_value_id = request('variant_value_id');
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

    public function getVariantByProduct()
    {
        try {
            $variantValueID = $this->variant_value_id;
            if (!$variantValueID) {
                return [
                    'success' => false,
                    'message' => 'Variant value tidak boleh kosong!'
                ];
            }

            return VariantQueries::detailVariantByProduct($variantValueID);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }
}

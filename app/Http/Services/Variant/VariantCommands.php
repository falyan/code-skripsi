<?php

namespace App\Http\Services\Variant;

use App\Models\Product;
use App\Models\Variant;
use App\Models\VariantValue;
use Exception;
use Illuminate\Support\Facades\DB;

class VariantCommands
{
    public function createVariantValue($product_id, $data)
    {
        try {
            if (!isset($data['variant_id']) || !isset($data['name'])) {
                return [];
            }

            DB::beginTransaction();

            $variant_value = VariantValue::create([
                'variant_id' => $data['variant_id'],
                'product_id' => $product_id,
                'name' => $data['name'],
            ]);

            DB::commit();

            return $variant_value;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }
}

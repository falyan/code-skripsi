<?php

namespace App\Http\Services\Variant;

use App\Models\Product;
use App\Models\Variant;
use App\Models\VariantStock;
use App\Models\VariantValue;
use App\Models\VariantValueProduct;
use Exception;
use Illuminate\Support\Facades\DB;

class VariantCommands
{
    public function createVariantValue($product_id, $data)
    {
        try {
            DB::beginTransaction();

            $variant_values = collect();
            foreach ($data['variant'] as $d) {
                foreach ($d['variant_value'] as $vv) {
                    $variant_value = VariantValue::create([
                        'variant_id' => $vv['variant_id'],
                        'product_id' => $product_id,
                        'option_name' => $vv['option_name']
                    ]);
                    $variant_values->push($variant_value);
                }
            }

            $iddd = [];
            foreach ($data['variant_value_product'] as $vvp) {
                $options = array_filter(explode(';', trim($vvp['desc'])));
                $id_value = [];
                foreach ($options as $option) {
                    $variant_value = VariantValue::where('product_id', $product_id)->where('option_name', $option)->first();
                    $id_value[] = $variant_value->id;
                }
                $id_value = trim(implode(',', $id_value));
                $iddd[] = $id_value;

                $variant_value_product = VariantValueProduct::create([
                    'variant_value_id' => $id_value,
                    'product_id' => $product_id,
                    'description' => $vvp['desc'],
                    'price' => $vvp['price'],
                ]);

                VariantStock::create([
                    'variant_value_product' => $variant_value_product->id,
                    'amount' => $vvp['amount'],
                    'description' => '{"from": "Product", "type": "adding", "title": "Tambah stok produk baru", "amount": "' . $vvp['amount'] . '"}',
                    'status' => 1,
                ]);
            }

            DB::commit();

            return [
                "variant_value" => $variant_values,
                'variant_value_id' => $iddd,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }
}

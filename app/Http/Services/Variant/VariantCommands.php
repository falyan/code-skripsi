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

            $variant_amount_total = array_sum(array_column($data['variant_value_product'], 'amount'));

            if ($variant_amount_total != $data->amount) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Total amount varian tidak boleh kurang dari atau lebih dari produk!',
                ];
            }

            $variant_values = collect();
            foreach ($data['variant'] as $d) {
                foreach ($d['variant_value'] as $vv) {
                    $variant_value = VariantValue::create([
                        'variant_id' => $vv['variant_id'],
                        'product_id' => $product_id,
                        'option_name' => $vv['option_name'],
                        'created_by' => $data['full_name'],
                    ]);
                    $variant_values->push($variant_value);
                }
            }

            $variant_value_products = collect();
            $variant_stocks = collect();

            foreach ($data['variant_value_product'] as $vvp) {
                $options = array_filter(explode(';', trim($vvp['desc'])));
                $value_id = [];
                foreach ($options as $option) {
                    $variant_value = VariantValue::where('product_id', $product_id)->where('option_name', $option)->first();
                    $value_id[] = $variant_value->id;
                }
                $value_id = trim(implode(',', $value_id));

                $variant_value_product = VariantValueProduct::create([
                    'variant_value_id' => $value_id,
                    'product_id' => $product_id,
                    'description' => $vvp['desc'],
                    'price' => $vvp['price'],
                    'created_by' => $data['full_name'],
                ]);

                $variant_stock = VariantStock::create([
                    'variant_value_product_id' => $variant_value_product->id,
                    'amount' => $vvp['amount'],
                    'description' => '{"from": "Product", "type": "adding", "title": "Tambah stok produk baru", "amount": "' . $vvp['amount'] . '"}',
                    'status' => 1,
                    'created_by' => $data['full_name'],
                ]);

                $variant_value_products = $variant_value_products->push($variant_value_product);
                $variant_stocks = $variant_stocks->push($variant_stock);
            }

            DB::commit();

            $variant_data = [
                'variant_values' => $variant_values,
                'variant_value_products' => $variant_value_products,
                'variant_stocks' => $variant_stocks,
            ];
            return [
                'success' => true,
                'message' => 'Berhasil membuat varian produk!',
                'data' => $variant_data
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }
}

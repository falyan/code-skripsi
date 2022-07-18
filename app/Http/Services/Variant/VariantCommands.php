<?php

namespace App\Http\Services\Variant;

use App\Http\Services\Product\ProductCommands;
use App\Models\ProductStock;
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
                    if (empty($variant_value)) {
                        DB::rollBack();
                        return [
                            'success' => false,
                            'message' => 'Nama varian tidak sesuai dengan yg diisi!',
                        ];
                    }
                    $value_id[] = $variant_value->id;
                }
                $value_id = trim(implode(',', $value_id));

                $variant_value_product = VariantValueProduct::create([
                    'variant_value_id' => $value_id,
                    'product_id' => $product_id,
                    'description' => $vvp['desc'],
                    'price' => $vvp['price'],
                    'strike_price' => $vvp['strike_price'] ?? null,
                    'created_by' => $data['full_name'],
                    'main_variant' => $vvp['main_variant'] ?? false,
                    'status' => $vvp['status'] ?? 0,
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
                'data' => $variant_data,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public function updateVariantValue($product_id, $data)
    {
        try {
            DB::beginTransaction();
            $data['product_id'] = $product_id;

            $variant_amount_total = array_sum(array_column($data['variant_value_product'], 'amount'));

            if ($variant_amount_total != $data->amount) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Total amount varian tidak boleh kurang dari atau lebih dari produk!',
                ];
            }

            $vv_olds = VariantValue::where('product_id', $product_id)->get();
            $vvp_olds = VariantValueProduct::where('product_id', $product_id)->get();

            array_map(function ($variant_value) use ($data, $vv_olds) {
                array_map(function ($vv) use ($data, $vv_olds) {
                    if (isset($vv['id'])) {
                        $value = VariantValue::find(data_get($vv, 'id'));
                        $variant_value_product = VariantValueProduct::where('product_id', $data['product_id'])
                            ->where('variant_value_id', 'ilike', "%{$value->id}%")
                            ->where('description', 'ilike', "%{$value->option_name}%")
                            ->get();

                        foreach ($variant_value_product as $value_product) {
                            $new_desc = str_replace($value->option_name, data_get($vv, 'option_name'), $value_product->description);
                            $value_product->update([
                                'description' => $new_desc,
                            ]);
                        }

                        $value->update([
                            'variant_id' => data_get($vv, 'variant_id'),
                            'option_name' => data_get($vv, 'option_name'),
                            'updated_by' => $data['full_name'],
                        ]);
                    } else {
                        foreach ($vv_olds as $vv_old) {
                            VariantValue::destroy($vv_old['id']);
                        }

                        VariantValue::create([
                            'variant_id' => $vv['variant_id'],
                            'product_id' => $data['product_id'],
                            'option_name' => $vv['option_name'],
                            'created_by' => $data['full_name'],
                        ]);
                    }
                }, $variant_value['variant_value']);
            }, $data['variant']);

            array_map(function ($vvp) use ($data, $vvp_olds) {
                if (isset($vvp['id'])) {
                    $variant_value_product = VariantValueProduct::find($vvp['id']);
                    $variant_value_product->update([
                        'price' => $vvp['price'],
                        'strike_price' => $vvp['strike_price'] ?? null,
                        'updated_by' => $data['full_name'],
                        'main_variant' => $vvp['main_variant'] ?? $variant_value_product->main_variant,
                        'status' => $vvp['status'] ?? $variant_value_product->status,
                    ]);
                    if (isset($vvp['amount'])) {
                        $product_comamnd = new ProductCommands();
                        $product_comamnd->updateStockVariantProduct($variant_value_product->id, [
                            'amount' => $vvp['amount'],
                            'full_name' => $data['full_name'],
                        ]);
                    }
                } else {
                    $options = array_filter(explode(';', trim($vvp['desc'])));
                    $value_id = [];
                    foreach ($options as $option) {
                        $find_variant_value = VariantValue::where('product_id', $data['product_id'])->where('option_name', $option)->first();
                        if (empty($find_variant_value)) {
                            DB::rollBack();
                            return [
                                'success' => false,
                                'message' => 'Nama varian tidak sesuai dengan yg diisi!',
                            ];
                        }
                        $value_id[] = $find_variant_value->id;
                    }
                    $value_id = trim(implode(',', $value_id));

                    foreach ($vvp_olds as $vvp_old) {
                        VariantValueProduct::destroy($vvp_old['id']);
                    }

                    $variant_value_product = VariantValueProduct::create([
                        'variant_value_id' => $value_id,
                        'product_id' => $data['product_id'],
                        'description' => $vvp['desc'],
                        'price' => $vvp['price'],
                        'strike_price' => $vvp['strike_price'] ?? null,
                        'created_by' => $data['full_name'],
                        'main_variant' => $vvp['main_variant'] ?? false,
                        'status' => $vvp['status'] ?? 0,
                    ]);

                    $variant_stock = VariantStock::create([
                        'variant_value_product_id' => $variant_value_product->id,
                        'amount' => $vvp['amount'],
                        'description' => '{"from": "Product", "type": "adding", "title": "Tambah stok produk baru", "amount": "' . $vvp['amount'] . '"}',
                        'status' => 1,
                        'created_by' => $data['full_name'],
                    ]);
                }
            }, $data['variant_value_product']);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Berhasil update varian!',
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public function updateVariantPrice($product_id, $data)
    {
        try {
            DB::beginTransaction();

            foreach ($data as $value) {
                $vvp_old = VariantValueProduct::where(['product_id' => $product_id, 'id' => $value['id']])->first();

                if (!$vvp_old) {
                    DB::rollBack();
                    return [
                        'success' => false,
                        'message' => 'Varian Produk tidak ditemukan!',
                    ];
                }

                $vvp_old->update([
                    'price' => $value['price'],
                    'strike_price' => empty($value['strike_price']) || ($value['strike_price'] == null || $value['strike_price'] == 0) ? null : $value['strike_price'],
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Berhasil update price varian!',
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public function updateVariantStock($product_id, $data)
    {
        try {
            DB::beginTransaction();

            $vvp_status = $this->updateVariantStatusCode($product_id, $data);
            $this->updateVariantStockCode($product_id, $data);

            DB::commit();

            if ($vvp_status != null) {
                return [
                    'success' => true,
                    'message' => 'Berhasil update stock dan status varian berhasil!',
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Berhasil update stock varian berhasil!',
                ];
            }
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public function listVarianStock($product_id)
    {
        $vvp = VariantStock::with('variant_value_product')
            ->whereHas('variant_value_product', function ($q) use ($product_id) {
                $q->where('product_id', $product_id);
            })->get();

        $data = [];
        foreach ($vvp as $variant_stock) {
            $data[] = [
                'id' => $variant_stock->id,
                'amount' => 0,
            ];
        }

        return $data;
    }

    public function updateVariantStockCode($product_id, $data)
    {
        foreach ($data as $value) {
            $vvp_old = VariantStock::with('variant_value_product')
                ->whereHas('variant_value_product', function ($q) use ($product_id) {
                    $q->where('product_id', $product_id);
                })
                ->where('id', $value['id'])
                ->first();

            if (!$vvp_old) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Varian Produk tidak ditemukan!',
                ];
            }

            $vvp_old->update(['amount' => $value['amount']]);
        }

        $vvp_amount = VariantValueProduct::with('variant_stock')
            ->where(['product_id' => $product_id, 'status' => 1])
            ->withSum('variant_stock', 'amount')
            ->get()
            ->sum('variant_stock_sum_amount');

        return ProductStock::where('product_id', $product_id)->first()
            ->update(['amount' => $vvp_amount]);
    }

    public function updateVariantStatusCode($product_id, $data)
    {
        $vvp = [];
        foreach ($data as $value) {
            $vvp_old = VariantStock::with('variant_value_product')
                ->whereHas('variant_value_product', function ($q) use ($product_id) {
                    $q->where('product_id', $product_id);
                })
                ->where('id', $value['id'])
                ->first();

            if (isset($value['status'])) {
                $vvp_status = VariantValueProduct::where('id', $vvp_old->variant_value_product_id)->first();
                $vvp_status->update(['status' => $value['status']]);

                $vvp[] = $vvp_status;
            }

            if (!$vvp_old) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Varian Produk tidak ditemukan!',
                ];
            }
        }
        return $vvp;
    }

}

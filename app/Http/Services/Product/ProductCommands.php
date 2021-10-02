<?php

namespace App\Http\Services\Product;

use App\Models\Product;
use App\Models\ProductPhoto;
use App\Models\ProductStock;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductCommands{
    public function createProduct($data){
        try {
            $rules = [
                'merchant_id' => 'required',
                'name' => 'required',
                'price' => 'required',
                'minimum_purchase' => 'required',
                'condition' => 'required',
                'weight' => 'required',
                'is_shipping_insurance' => 'required',
                'shipping_service' => 'required',
                'url.*' => 'required',
                'amount' => 'required',
                'uom' => 'required'
            ];

            $validator = Validator::make($data->all(), $rules);
            if ($validator->fails()){
                $response['success'] = false;
                $response['message'] = $validator->errors();
                $response['data'] = $data;
                return $response;
            }
            DB::beginTransaction();
            $product = Product::create([
                'merchant_id' => $data->merchant_id,
                'name' => $data->name,
                'price' => $data->price,
                'minimum_purchase' => $data->minimum_purchase,
                'category_id' => $data->category_id,
                'etalase_id' => $data->etalase_id,
                'condition' => $data->condition,
                'weight' => $data->weight,
                'description' => $data->description,
                'is_shipping_insurance' => $data->is_shipping_insurance,
                'shipping_service' => $data->shipping_service,
                'created_by' => $data->full_name,
                'updated_by' => $data->full_name
            ]);

            if (!$product){
                $response['success'] = false;
                $response['message'] = 'Gagal menambahkan produk!';
                return $response;
            }

            $product_stock = ProductStock::create([
                'merchant_id' => $data->merchant_id,
                'product_id' => $product->id,
                'amount' => $data->amount,
                'uom' => $data->uom,
                'description' => '{"type": "Create initial amount"}',
                'status' => 1,
                'created_by' => null,
                'updated_by' => null,
            ]);

            if (!$product_stock){
                $response['success'] = false;
                $response['message'] = 'Gagal menambahkan stok produk!';
                return $response;
            }

            $product_photo = '';
            foreach ($data->url as $url_photo){
                $product_photo = ProductPhoto::create([
                    'merchant_id' => $data->merchant_id,
                    'product_id' => $product->id,
                    'url' => $url_photo,
                    'created_by' => null,
                    'updated_by' => null,
                ]);
            }

            if (!$product_photo){
                $response['success'] = false;
                $response['message'] = 'Gagal menambahkan foto produk!';
                return $response;
            }

            $product_data = array_merge($product->toArray(), $product_stock->toArray(), $product_photo->toArray());
            $response['success'] = true;
            $response['message'] = 'Produk berhasil ditambahkan!';
            $response['data'] = $product_data;

            DB::commit();
            return $response;
        }catch (Exception $e){
            DB::rollBack();
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public function updateProduct($product_id, $merchant_id, $data){
        try {
            DB::beginTransaction();
            $product = Product::find($product_id);
            $product->name = ($data->name == null) ? ($product->name):($data->name);
            $product->price = ($data->price == null) ? ($product->price):($data->price);
            $product->minimum_purchase = ($data->minimum_purchase == null) ? ($product->minimum_purchase):($data->minimum_purchase) ;
            $product->category_id = ($data->category_id == null) ? ($product->category_id):($data->category_id);
            $product->etalase_id = ($data->etalase_id == null) ? ($product->etalase_id):($data->etalase_id);
            $product->condition = ($data->condition == null) ? ($product->condition):($data->condition);
            $product->weight = ($data->weight == null) ? ($product->weight):($data->weight);
            $product->description = ($data->description == null) ? ($product->description):($data->description);
            $product->is_shipping_insurance = ($data->is_shipping_insurance == null) ? ($product->is_shipping_insurance):($data->is_shipping_insurance);
            $product->shipping_service = ($data->shipping_service == null) ? ($product->shipping_service):($data->shipping_service);
            $product->updated_by = null;

            if (!$product->save()){
                $response['success'] = false;
                $response['message'] = 'Gagal mengubah produk!';
                return $response;
            }

            $product_stock_old = ProductStock::where('merchant_id', $merchant_id)
                ->where('product_id', $product_id)
                ->where('status', 1)->latest()->first();

            $product_stock_old->status = 0;
            $product_stock_old->save();

            $product_stock_new = ProductStock::create([
                'merchant_id' => $merchant_id,
                'product_id' => $product_id,
                'amount' => $data->amount,
                'uom' => $data->uom,
                'description' => '{"type": "Adjust existing amount"}',
                'status' => 1,
                'created_by' => null,
                'updated_by' => null,
            ]);

            if (!$product_stock_new->save()){
                $response['success'] = false;
                $response['message'] = 'Gagal mengubah stok produk!';
                return $response;
            }

            $product_photo = ProductPhoto::where('merchant_id', $merchant_id)
                ->where('product_id', $product_id)->get();

            foreach ($product_photo as $photo){
                $photo->delete();
            }

            foreach ($data->url as $url_photo){
                $product_photo = ProductPhoto::create([
                    'merchant_id' => $merchant_id,
                    'product_id' => $product_id,
                    'url' => $url_photo,
                    'created_by' => null,
                    'updated_by' => null,
                ]);
            }

            if (!$product_photo){
                $response['success'] = false;
                $response['message'] = 'Gagal mengubah foto produk!';
                return $response;
            }

            $product_data = array_merge($product->toArray(), $product_stock_new->toArray(), $product_photo->toArray());
            $response['success'] = true;
            $response['message'] = 'Produk berhasil diubah!';
            $response['data'] = $product_data;

            DB::commit();
            return $response;
        }catch (Exception $e){
            DB::rollBack();
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public function deleteProduct($product_id, $merchant_id){
        try {
            DB::beginTransaction();
            $product_photo = ProductPhoto::where('merchant_id', $merchant_id)
                ->where('product_id', $product_id)->get();

            foreach ($product_photo as $photo){
                $photo->delete();
            }

            $product_stock = $product_stock = ProductStock::where('merchant_id', $merchant_id)
                ->where('product_id', $product_id)->get();

            foreach ($product_stock as $stock){
                $stock->delete();
            }

            $product = Product::find($product_id);
            if ($product->delete() == 0){
                $response['success'] = false;
                $response['message'] = 'Gagal menghapus produk!';
            }

            $response['success'] = true;
            $response['message'] = 'Produk berhasil dihapus!';

            DB::commit();
            return $response;
        }catch (Exception $e){
            DB::rollBack();
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public function updateStockProduct($product_id, $merchant_id, $data){
        try {
            DB::beginTransaction();
            $stock_old = ProductStock::where('merchant_id', $merchant_id)
                ->where('product_id', $product_id)
                ->where('status', 1)->latest()->first();

            $stock_old->status = 0;
            $stock_old->save();

            $stock_new = ProductStock::create([
                'merchant_id' => $merchant_id,
                'product_id' => $product_id,
                'amount' => $data->amount,
                'uom' => $data->uom,
                'description' => '{"type": "Adjust existing amount"}',
                'status' => 1,
                'created_by' => null,
                'updated_by' => null,
            ]);

            if (!$stock_new){
                $response['success'] = false;
                $response['message'] = 'Gagal mengubah stok produk!';
                return $response;
            }
            $response['success'] = true;
            $response['message'] = 'Berhasil mengubah stok produk!';
            $response['data'] = $stock_new;

            DB::commit();
            return $response;
        }catch (Exception $e){
            DB::rollBack();
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }
}

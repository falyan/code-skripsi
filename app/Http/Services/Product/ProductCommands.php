<?php

namespace App\Http\Services\Product;

use App\Http\Services\Service;
use App\Models\Product;
use App\Models\ProductPhoto;
use App\Models\ProductStock;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductCommands extends Service
{
    public function createProduct($data){
        try {
            $rules = [
                'merchant_id' => 'required',
                'name' => 'required',
                'price' => 'required|numeric',
                'strike_price' => 'nullable|numeric|gt:price',
                'minimum_purchase' => 'required',
                'condition' => 'required',
                'weight' => 'required',
                'is_shipping_insurance' => 'required',
                'shipping_service' => 'nullable',
                'url.*' => 'required',
                'is_featured_product' => 'nullable',
                'amount' => 'required',
                'uom' => 'required'
            ];

            $validator = Validator::make($data->all(), $rules);
            if ($validator->fails()){
                $response['success'] = false;
                $response['message'] = $validator->errors();
                $response['data'] = $data->all();
                return $response;
        }
            DB::beginTransaction();
            $product = Product::create([
                'merchant_id' => $data->merchant_id,
                'name' => $data->name,
                'price' => $data->price,
                'strike_price' => $data->strike_price,
                'minimum_purchase' => $data->minimum_purchase,
                'category_id' => $data->category_id,
                'etalase_id' => $data->etalase_id,
                'condition' => $data->condition,
                'weight' => $data->weight,
                'description' => $data->description,
                'is_shipping_insurance' => $data->is_shipping_insurance,
                'shipping_service' => $data->shipping_service,
                'is_featured_product' => $data->is_featured_product,
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
                'description' => '{"from": "Product", "type": "adding", "title": "Tambah stok produk baru", "amount": "' . $data->amount. '"}',
                'status' => 1,
                'created_by' => $data->full_name,
                'updated_by' => $data->full_name
            ]);

            if (!$product_stock){
                $response['success'] = false;
                $response['message'] = 'Gagal menambahkan stok produk!';
                return $response;
            }

            $product_photo = '';
            $photo = [];
            foreach ($data->url as $url_photo){
                $product_photo = ProductPhoto::create([
                    'merchant_id' => $data->merchant_id,
                    'product_id' => $product->id,
                    'url' => $url_photo,
                    'created_by' => $data->full_name,
                    'updated_by' => $data->full_name
                ]);
                $photo[] = $url_photo;
            }
            $product_photo->url = $photo;

            if (!$product_photo){
                $response['success'] = false;
                $response['message'] = 'Gagal menambahkan foto produk!';
                return $response;
            }
            $product_data = [$product, $product_stock, $product_photo];
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

            if ($product == null){
                $response['success'] = false;
                $response['message'] = 'Produk tidak ditemukan!';
                return $response;
            }
            $product->name = ($data->name == null) ? ($product->name):($data->name);
            $product->price = ($data->price == null) ? ($product->price):($data->price);
            $product->strike_price = ($data->strike_price == null) ? ($product->strike_price):($data->strike_price);
            $product->minimum_purchase = ($data->minimum_purchase == null) ? ($product->minimum_purchase):($data->minimum_purchase) ;
            $product->category_id = ($data->category_id == null) ? ($product->category_id):($data->category_id);
            $product->etalase_id = ($data->etalase_id == null) ? ($product->etalase_id):($data->etalase_id);
            $product->condition = ($data->condition == null) ? ($product->condition):($data->condition);
            $product->weight = ($data->weight == null) ? ($product->weight):($data->weight);
            $product->description = ($data->description == null) ? ($product->description):($data->description);
            $product->is_shipping_insurance = ($data->is_shipping_insurance == null) ? ($product->is_shipping_insurance):($data->is_shipping_insurance);
            $product->is_featured_product = ($data->is_featured_product == null) ? ($product->is_featured_product):($data->is_featured_product);
            $product->shipping_service = ($data->shipping_service == null) ? ($product->shipping_service):($data->shipping_service);
            $product->updated_by = $data->full_name;

            if (!$product->save()){
                $response['success'] = false;
                $response['message'] = 'Gagal mengubah produk!';
                return $response;
            }

            $product_stock_old = ProductStock::where('merchant_id', $merchant_id)
                ->where('product_id', $product_id)
                ->where('status', 1)->latest()->first();

            if ($product_stock_old != null){
                $product_stock_old->status = 0;
                $product_stock_old->save();
            }

            $product_stock_new = ProductStock::create([
                'merchant_id' => $merchant_id,
                'product_id' => $product_id,
                'amount' => $data->amount,
                'uom' => $data->uom,
                'description' => '{"from": "Product", "type": "changing", "title": "Ubah stok produk", "amount": "' . $data->amount. '"}',
                'status' => 1,
                'created_by' => $data->full_name,
                'updated_by' => $data->full_name,
            ]);

            if (!$product_stock_new->save()){
                $response['success'] = false;
                $response['message'] = 'Gagal mengubah stok produk!';
                return $response;
            }

            $product_photo = ProductPhoto::where('merchant_id', $merchant_id)
                ->where('product_id', $product_id)->get();

            if (!empty($product_photo)){
                foreach ($product_photo as $photo){
                    $photo->delete();
                }
            }

            $photo = [];
            foreach ($data->url as $url_photo){
                $product_photo = ProductPhoto::create([
                    'merchant_id' => $merchant_id,
                    'product_id' => $product_id,
                    'url' => $url_photo,
                    'created_by' => $data->full_name,
                    'updated_by' => $data->full_name,
                ]);
                $photo[] = $url_photo;
            }
            $product_photo->url = $photo;

            if (!$product_photo){
                $response['success'] = false;
                $response['message'] = 'Gagal mengubah foto produk!';
                return $response;
            }

            $product_data = [$product, $product_stock_new, $product_photo];
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

            $product = Product::where('id', $product_id)->where('merchant_id', $merchant_id)->first();
            if ($product == null){
                $response['success'] = false;
                $response['message'] = 'Produk tidak ditemukan!';
                return $response;
            }

            if ($product->delete() == 0){
                $response['success'] = false;
                $response['message'] = 'Gagal menghapus produk!';
                return $response;
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
                'description' => '{"from": "Product", "type": "changing", "title": "Ubah stok produk", "amount": "' . $data->amount. '"}',
                'status' => 1,
                'created_by' => $data->full_name,
                'updated_by' => $data->full_name,
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

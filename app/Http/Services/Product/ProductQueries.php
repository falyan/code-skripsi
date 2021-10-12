<?php

namespace App\Http\Services\Product;

use App\Http\Services\Service;
use App\Models\Product;

class ProductQueries extends Service
{
    public function getAllProduct(){
        $data = Product::with(['product_stock', 'product_photo'])->paginate(10);

//        if ($data->isEmpty()){
//            $response['success'] = false;
//            $response['message'] = 'Gagal mendapatkan data produk!';
//            return $response;
//        }
        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function getProductByMerchantIdSeller($merchant_id){
        $data = Product::with(['product_stock', 'product_photo'])->where('merchant_id', $merchant_id)->paginate(10);

//        if ($data->isEmpty()){
//            $response['success'] = false;
//            $response['message'] = 'Gagal mendapatkan data produk!';
//            return $response;
//        }
        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function getProductByEtalaseId($etalase_id){
        $data = Product::with(['product_stock', 'product_photo'])->where('etalase_id', $etalase_id)->paginate(10);

//        if ($data->isEmpty()){
//            $response['success'] = false;
//            $response['message'] = 'Gagal mendapatkan data produk!';
//            return $response;
//        }
        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function searchProductByName($keyword, $limit = 10){
        if (strlen($keyword) < 3){
            return false;
        }

        $product = Product::with(['product_stock', 'product_photo'])->where('name', 'ILIKE', '%'.$keyword.'%')->paginate($limit);
//        if ($product->isEmpty()){
//            $response['success'] = false;
//            $response['message'] = 'Produk tidak tersedia.';
//            return $response;
//        }

        $response['success'] = true;
        $response['message'] = 'Produk berhasil didapatkan.';
        $response['data'] = $product;
        return $response;
    }
    public function getProductByMerchantIdBuyer($merchant_id, $size){
        $products = \App\Models\Product::with(['reviews', 'merchant' => function($merchant) {
            $merchant->with('city:id,name');
        },'order_details' => function ($trx) {
            $trx->whereHas('order', function ($j) {
                $j->whereHas('progress_done');
            });
        }, 'product_photo', 'product_stock'])->where('merchant_id', $merchant_id)->paginate($size);

//        if ($data->isEmpty()){
//            $response['success'] = false;
//            $response['message'] = 'Gagal mendapatkan data produk!';
//            return $response;
//        }
        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $products;
        return $response;
    }

    public function getProductByCategory($category_id){
        $data = Product::with(['product_stock', 'product_photo'])->where('category_id', $category_id)->paginate(10);

//        if ($data->isEmpty()){
//            $response['success'] = false;
//            $response['message'] = 'Gagal mendapatkan data produk!';
//            return $response;
//        }
        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function getProductById($id){
        $data = Product::withCount('reviews')->with(['reviews' => function($reviews) {
            $reviews->with('customer:id,full_name,image_url');
        }, 'product_stock', 'product_photo', 'merchant'])->where('id', $id)->first();

        if (!$data){
            $response['success'] = false;
            $response['message'] = 'Gagal mendapatkan data produk!';
            $response['data'] = $data;
            return $response;
        }
        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function getRecommendProduct(){
        $product = Product::with(['product_stock', 'product_photo'])->latest()->limit(10)->get();

//        if ($product->isEmpty()){
//            $response['success'] = false;
//            $response['message'] = 'Gagal mendapatkan data produk!';
//            return $response;
//        }
        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $product;
        return $response;
    }

    public function getSpecialProduct(){
        $product = Product::with(['product_stock', 'product_photo'])->latest()->limit(10)->get();

//        if ($product->isEmpty()){
//            $response['success'] = false;
//            $response['message'] = 'Gagal mendapatkan data produk!';
//            return $response;
//        }
        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $product;
        return $response;
    }
}

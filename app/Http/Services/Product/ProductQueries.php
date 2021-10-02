<?php

namespace App\Http\Services\Product;

use App\Models\Product;

class ProductQueries{
    public function getAllProduct(){
        $data = Product::with(['product_stock', 'product_photo'])->paginate(10);

        if (empty($data)){
            $response['success'] = false;
            $response['message'] = 'Gagal mendapatkan data produk!';
            return $response;
        }
        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function getProductByMerchantId($merchant_id){
        $data = Product::with(['product_stock', 'product_photo'])->where('merchant_id', $merchant_id)->paginate(10);

        if (empty($data)){
            $response['success'] = false;
            $response['message'] = 'Gagal mendapatkan data produk!';
            return $response;
        }
        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function getProductByEtalaseId($etalase_id){
        $data = Product::with(['product_stock', 'product_photo'])->where('etalase_id', $etalase_id)->paginate(10);

        if (empty($data)){
            $response['success'] = false;
            $response['message'] = 'Gagal mendapatkan data produk!';
            return $response;
        }
        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Services\Product\ProductCommands;
use App\Http\Services\Product\ProductQueries;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->productCommands = new ProductCommands();
        $this->productQueries = new ProductQueries();
    }

    //Create Produk
    public function createProduct(Request $request){
        return $this->productCommands->createProduct($request);
    }

    //Update Produk
    public function updateProduct($product_id, $merchant_id, Request $request){
        return $this->productCommands->updateProduct($product_id, $merchant_id, $request);
    }

    //Delete Produk
    public function deleteProduct($product_id, $merchant_id){
        return $this->productCommands->deleteProduct($product_id, $merchant_id);
    }

    //Get Semua Produk
    public function getAllProduct(){
        return $this->productQueries->getAllProduct();
    }

    //Get Produk Berdasarkan Merchant
    public function getProductByMerchant($merchant_id){
        return $this->productQueries->getProductByMerchantId($merchant_id);
    }

    //Get Produk Berdasarkan Etalase
    public function getProductByEtalase($etalase_id){
        return $this->productQueries->getProductByEtalaseId($etalase_id);
    }

    //Adjust Stok Produk
    public function updateStockProduct($product_id, $merchant_id, Request $request){
        return $this->productCommands->updateStockProduct($product_id, $merchant_id, $request);
    }
}

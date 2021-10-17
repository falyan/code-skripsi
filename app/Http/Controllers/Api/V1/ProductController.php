<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Product\ProductCommands;
use App\Http\Services\Product\ProductQueries;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        try {
            $request['full_name'] = Auth::user()->full_name;
            return $this->productCommands->createProduct($request);
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    //Update Produk
    public function updateProduct($product_id, Request $request){
        try {
            $request['full_name'] = Auth::user()->full_name;
            $merchant_id = Auth::user()->merchant_id;
            return $this->productCommands->updateProduct($product_id, $merchant_id, $request);
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    //Delete Produk
    public function deleteProduct($product_id){
        try {
            $merchant_id = Auth::user()->merchant_id;
            return $this->productCommands->deleteProduct($product_id, $merchant_id);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    //Get Semua Produk
    public function getAllProduct(){
        try {
            return $this->productQueries->getAllProduct();
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    //Get Produk Berdasarkan Merchant Seller
    public function getProductByMerchantSeller(){
        try {
            $merchant_id = Auth::user()->merchant_id;
            return $this->productQueries->getProductByMerchantIdSeller($merchant_id);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    //Get Produk Berdasarkan Etalase
    public function getProductByEtalase($etalase_id){
        try {
            return $this->productQueries->getProductByEtalaseId($etalase_id);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    //Adjust Stok Produk
    public function updateStockProduct($product_id, Request $request){
        try {
            $request['full_name'] = Auth::user()->full_name;
            $merchant_id = Auth::user()->merchant_id;
            return $this->productCommands->updateStockProduct($product_id, $merchant_id, $request);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    //Search Produk
    public function searchProductByName($keyword, $limit = 10){
        try {
            return $this->productQueries->searchProductByName($keyword, $limit);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    //Get Produk Berdasarkan Merchant Buyer
    public function getProductByMerchantBuyer($merchant_id){
        try {
            $size = request()->query('size', 10);
            return $this->productQueries->getProductByMerchantIdBuyer($merchant_id, $size);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    //Get Produk Berdasarkan Kategori
    public function getProductByCategory($category_id){
        try {
            return $this->productQueries->getProductByCategory($category_id);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    //Get Produk Berdasarkan ID
    public function getProductById($id){
        try {
            return $this->productQueries->getProductById($id);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    //Get Produk Rekomendasi
    public function getRecommendProduct(){
        try {
            return $this->productQueries->getRecommendProduct();
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getSpecialProduct(){
        try {
            return $this->productQueries->getSpecialProduct();
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getMerchantFeaturedProduct($merchant_id)
    {
        try {
            return $this->productQueries->getMerchantFeaturedProduct($merchant_id);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }
}

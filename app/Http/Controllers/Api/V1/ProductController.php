<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Product\ProductCommands;
use App\Http\Services\Product\ProductQueries;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $productCommands, $productQueries;
    public function __construct()
    {
        $this->productCommands = new ProductCommands();
        $this->productQueries = new ProductQueries();
    }

    //Create Produk
    public function createProduct(Request $request)
    {
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

            $validator = Validator::make($request->all(), $rules, [
                'exists' => 'ID :attribute tidak ditemukan.',
                'required' => ':attribute diperlukan.',
                'max' => 'panjang :attribute maksimum :max karakter.',
                'min' => 'panjang :attribute minimum :min karakter.',
                'gt' => 'nilai :attribute harus lebih besar dari :gt.',
            ]);
            if ($validator->fails()) {
                $errors = collect();
                foreach ($validator->errors()->getMessages() as $key => $value) {
                    foreach ($value as $error) {
                        $errors->push($error);
                    }
                }

                return $this->respondValidationError($errors, 'Validation Error!');
            }

            $request['full_name'] = Auth::user()->full_name;
            return $this->productCommands->createProduct($request);
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    //Update Produk
    public function updateProduct($product_id, Request $request)
    {
        try {
            $request['full_name'] = Auth::user()->full_name;
            $merchant_id = Auth::user()->merchant_id;
            return $this->productCommands->updateProduct($product_id, $merchant_id, $request);
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    //Delete Produk
    public function deleteProduct($product_id)
    {
        try {
            $merchant_id = Auth::user()->merchant_id;
            return $this->productCommands->deleteProduct($product_id, $merchant_id);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    //Get Semua Produk
    public function getAllProduct(Request $request)
    {
        try {
            $limit = $request->limit ?? 10;
            $filter = $request->filter ?? [];
            $sorting = $request->sortby ?? null;
            return $this->productQueries->getAllProduct($limit, $filter, $sorting, request()->input('page'));
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    //Get Produk Berdasarkan Merchant Seller
    public function getProductByMerchantSeller(Request $request)
    {
        try {
            $merchant_id = Auth::user()->merchant_id;
            $limit = $request->limit ?? 10;
            $filter = $request->filter ?? [];
            $sorting = $request->sortby ?? null;
            return $this->productQueries->getProductByMerchantIdSeller($merchant_id, $filter, $sorting);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    //Get Produk Berdasarkan Etalase
    public function getProductByEtalase($etalase_id, Request $request)
    {
        try {
            $limit = $request->limit ?? 10;
            $filter = $request->filter ?? [];
            $sorting = $request->sortby ?? null;
            return $this->productQueries->getProductByEtalaseId($etalase_id, $filter, $sorting);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    //Adjust Stok Produk
    public function updateStockProduct($product_id, Request $request)
    {
        try {
            $request['full_name'] = Auth::user()->full_name;
            $merchant_id = Auth::user()->merchant_id;
            return $this->productCommands->updateStockProduct($product_id, $merchant_id, $request);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    //Search Produk
    public function searchProductByName(Request $request)
    {
        try {
            $validator = Validator::make(request()->all(), [
                'keyword' => 'required|min:3',
                'limit' => 'nullable'
            ], [
                'required' => ':attribute wajib diisi.',
                'min' => 'panjang :attribute minimum :min karakter.',
            ]);

            if ($validator->fails()) {
                $errors = collect();
                foreach ($validator->errors()->getMessages() as $key => $value) {
                    foreach ($value as $error) {
                        $errors->push($error);
                    }
                }
                return $this->respondValidationError($errors, 'Validation Error!');
            }

            $limit = $request->limit ?? 10;
            $filter = $request->filter ?? [];
            $sorting = $request->sortby ?? null;

            return $this->productQueries->searchProductByName($request->keyword, $limit, $filter, $sorting);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    //Get Produk Berdasarkan Merchant Buyer
    public function getProductByMerchantBuyer($merchant_id, Request $request)
    {
        try {
            $size = request()->query('size', 10);
            $filter = $request->filter ?? [];
            $sorting = $request->sortby ?? null;
            return $this->productQueries->getProductByMerchantIdBuyer($merchant_id, $size, $filter, $sorting);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    //Get Produk Berdasarkan Kategori
    public function getProductByCategory($category_id, Request $request)
    {
        try {
            $limit = $request->limit ?? 10;
            $filter = $request->filter ?? [];
            $sorting = $request->sortby ?? null;
            return $this->productQueries->getProductByCategory($category_id, $filter, $sorting, $limit);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    //Get Produk Berdasarkan ID
    public function getProductById($id)
    {
        try {
            return $this->productQueries->getProductById($id);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    //Get Produk Rekomendasi
    public function getRecommendProduct()
    {
        try {
            return $this->productQueries->getRecommendProduct();
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getSpecialProduct()
    {
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

    public function searchProductSeller(Request $request)
    {
        try {
            $validator = Validator::make(request()->all(), [
                'keyword' => 'required|min:3',
                'limit' => 'nullable'
            ], [
                'required' => ':attribute diperlukan.',
                'min' => 'panjang :attribute minimum :min karakter.',
            ]);

            if ($validator->fails()) {
                $errors = collect();
                foreach ($validator->errors()->getMessages() as $key => $value) {
                    foreach ($value as $error) {
                        $errors->push($error);
                    }
                }
                return $this->respondValidationError($errors, 'Validation Error!');
            }

            $merchant_id = Auth::user()->merchant_id;
            $keyword = $request->keyword;
            $limit = $request->limit ?? 10;
            $filter = $request->filter ?? [];
            $sorting = $request->sortby ?? null;
            return $this->productQueries->searchProductBySeller($merchant_id, $keyword, $limit, $filter, $sorting);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }
}

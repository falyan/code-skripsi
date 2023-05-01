<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Category\CategoryQueries;
use App\Http\Services\Product\ProductCommands;
use App\Http\Services\Product\ProductQueries;
use App\Http\Services\Variant\VariantCommands;
use App\Http\Services\Variant\VariantQueries;
use App\Models\Product;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $productCommands, $productQueries;
    protected $categoryQueries, $variantCommands, $variantQueries;
    public function __construct()
    {
        $this->productCommands = new ProductCommands();
        $this->productQueries = new ProductQueries();
        $this->categoryQueries = new CategoryQueries();
        $this->variantCommands = new VariantCommands();
        $this->variantQueries = new VariantQueries();
    }

    //Create Produk
    public function createProduct(Request $request)
    {
        try {
            $rules = [
                'merchant_id' => ['required', Rule::exists('merchant', 'id')->where('deleted_at', null)],
                'name' => 'required',
                'price' => 'required|numeric',
                'strike_price' => 'nullable|numeric|gt:price',
                'minimum_purchase' => 'required',
                'category_id' => [Rule::exists('master_data', 'id')->where('type', 'product_category')->where('deleted_at', null)],
                'condition' => 'required',
                'weight' => 'required',
                'is_shipping_insurance' => 'required',
                'shipping_service' => 'nullable',
                'url.*' => 'required',
                'is_featured_product' => 'nullable',
                'amount' => 'required',
                'uom' => 'required',
                'variant' => 'array',
                'variant.*' => 'required',
                'variant.*.variant_value' => 'required|array',
                'variant.*.variant_value.*.variant_id' => 'required',
                'variant.*.variant_value.*.option_name' => 'required',
                'variant_value_product' => 'array',
                'variant_value_product.*' => 'required',
                'variant_value_product.*.desc' => 'required',
                'variant_value_product.*.price' => 'required',
                'variant_value_product.*.amount' => 'required',
            ];

            $category = $this->categoryQueries->findById($request['category_id']);
            if ($category['success']) {
                if ($category['data']->max_variant) {
                    $rules = array_merge($rules, [
                        'variant' => 'array|max:' . $category['data']->max_variant,
                    ]);
                }
            }

            if (empty($request['variant']) && empty($request['variant_value_product'])) {
                $rules['variant'] = $rules['variant'];
                $rules['variant_value_product'] = $rules['variant_value_product'];
            } else {
                $rules['variant'] = 'required|' . $rules['variant'];
                $rules['variant_value_product'] = 'required|' . $rules['variant_value_product'];
            }

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

    //Update Produk Archived
    public function updateProductArchived($product_id, Request $request)
    {
        try {
            return $this->productCommands->updateProductArchived($product_id, $request);
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    //Update Produk Featured
    public function updateProductFeatured(Request $request)
    {
        try {
            $rules = [
                'product_feature.*.id' => 'required|numeric',
                'product_feature.*.is_featured_product' => 'required|boolean',
            ];

            $validator = Validator::make($request->all(), $rules, [
                'required' => ':attribute diperlukan.',
                'numeric' => ':attribute harus berupa angka.',
                'boolean' => ':attribute harus berupa boolean.',
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
            $merchant_id = Auth::user()->merchant_id;
            return $this->productCommands->updateProductFeatured($merchant_id, $request);
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    //Delete Produk
    public function deleteProduct(Request $request, $product_id)
    {
        try {
            $merchant_id = Auth::user()->merchant_id;
            return $this->productCommands->deleteProduct($product_id, $merchant_id, $request->get('accept'));
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
            return $this->productQueries->getAllProduct($limit, $filter, $sorting, request()->input('page') ?? 1);
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
            $featured = $request->is_featured_product ?? false;
            $page = $request->page ?? 1;
            return $this->productQueries->getProductByMerchantIdSeller($merchant_id, $filter, $sorting, $page, $limit, $featured);
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
            $page = $request->page ?? 1;
            return $this->productQueries->getProductByEtalaseId($etalase_id, $filter, $sorting, $limit, $page);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    //Get Produk Unggulan Berdasarkan Merchant
    public function getProductFeatured(Request $request)
    {
        try {
            $merchant_id = Auth::user()->merchant_id;
            // $limit = request()->query('limit', 10);
            $limit = 5;
            $filter = $request->filter ?? [];
            $sorting = $request->sortby ?? null;
            $page = $request->page ?? 1;
            return $this->productQueries->getProductFeatured($merchant_id, $limit, $filter, $sorting, $page);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    //Adjust Stok Produk
    public function updateStockProduct($product_id, Request $request)
    {
        try {
            $rules = [
                'amount' => 'numeric',
                'uom' => 'string',
                'status' => 'in:1,3',
            ];

            if (isset($request['variant_value_product']) && isset($request['variant_value_product']['variant_stock'])) {
                $rules = [
                    'variant_value_product.variant_stock' => 'array',
                    'variant_value_product.variant_stock.*' => 'required',
                    'variant_value_product.variant_stock.*.id' => 'required',
                    'variant_value_product.variant_stock.*.amount' => 'required|numeric',
                    'variant_value_product.variant_stock.*.status' => 'in:1,0',
                ];
            }

            $validator = Validator::make($request->all(), $rules, [
                'required' => ':attribute diperlukan.',
                'numeric' => ':attribute harus berupa angka.',
                'string' => ':attribute harus berupa string.',
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

            $product = Product::where([
                'id' => $product_id,
                'merchant_id' => auth()->user()->merchant_id,
            ])->first();

            if (!$product) {
                return [
                    'success' => false,
                    'message' => 'Produk tidak ditemukan!',
                ];
            }

            if (isset($request['variant_value_product']) && isset($request['variant_value_product']['variant_stock'])) {
                return $this->variantCommands->updateVariantStock($product_id, $request['variant_value_product']['variant_stock']);
            } else {
                $request['full_name'] = Auth::user()->full_name;
                $merchant_id = Auth::user()->merchant_id;
                return $this->productCommands->updateStockProduct($product_id, $merchant_id, $request);
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function updatePriceProduct($product_id, Request $request)
    {
        try {
            $rules = [
                'price' => 'numeric',
                'strike_price' => 'numeric',
            ];

            if (isset($request['variant_value_product'])) {
                $rules = [
                    'variant_value_product' => 'array',
                    'variant_value_product.*' => 'required',
                    'variant_value_product.*.id' => 'required',
                    'variant_value_product.*.price' => 'required|numeric',
                    'variant_value_product.*.strike_price' => 'numeric',
                ];
            }

            $validator = Validator::make($request->all(), $rules, [
                'required' => ':attribute diperlukan.',
                'numeric' => ':attribute harus berupa angka.',
                'string' => ':attribute harus berupa string.',
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

            $product = Product::where([
                'id' => $product_id,
                'merchant_id' => auth()->user()->merchant_id,
            ])->first();

            if (!$product) {
                return [
                    'success' => false,
                    'message' => 'Produk tidak ditemukan!',
                ];
            }

            if (isset($request['variant_value_product'])) {
                return $this->variantCommands->updateVariantPrice($product_id, $request['variant_value_product']);
            } else {
                return $this->productCommands->updatePriceProduct($product_id, $request);
            }
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
                'limit' => 'nullable',
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
            $page = $request->page ?? 1;

            return $this->productQueries->searchProductByName($request->keyword, $limit, $filter, $sorting, $page);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function searchProductByNameV2(Request $request)
    {
        try {
            $validator = Validator::make(request()->all(), [
                'keyword' => 'required|min:3',
                'limit' => 'nullable',
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
            $page = $request->page ?? 1;

            return $this->productQueries->searchProductByNameV2($request->keyword, $limit, $filter, $sorting, $page);
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
            $page = $request->page ?? 1;
            return $this->productQueries->getProductByMerchantIdBuyer($merchant_id, $size, $filter, $sorting, $page);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    //Get Produk Berdasarkan Merchant Buyer dan Category
    public function getProductByMerchantIdBuyerAndSearch($merchant_id, Request $request)
    {
        try {
            $size = request()->query('size', 10);
            $filter = $request->filter ?? [];
            $sorting = $request->sortby ?? null;
            $page = $request->page ?? 1;
            return $this->productQueries->getProductByMerchantIdBuyerAndSearch($merchant_id, $size, $filter, $sorting, $page);
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
            $page = $request->page ?? 1;
            return $this->productQueries->getProductByCategory($category_id, $filter, $sorting, $limit, $page);
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

    public function getProductByIdSeller($id)
    {
        try {
            return $this->productQueries->getProductById($id, true);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    //Get Produk Rekomendasi
    public function getRecommendProduct(Request $request)
    {
        try {
            Log::info("T00001", [
                'path_url' => "start.recommend",
                'query' => [],
                'body' => Carbon::now('Asia/Jakarta'),
                'response' => 'Start',
            ]);
            $filter = $request->filter ?? [];
            $sortby = $request->sortby ?? null;
            $limit = $request->limit ?? 10;
            $page = $request->page ?? 1;

            return $this->productQueries->getRecommendProduct($filter, $sortby, $limit, $page);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getBestSellingProductByMerchant(Request $request)
    {
        try {
            $merchant_id = Auth::user()->merchant_id;
            $filter = $request->filter ?? [];
            $sortby = $request->sortby ?? null;
            $limit = $request->limit ?? 3;
            $page = $request->page ?? 1;

            return $this->productQueries->getBestSellingProductByMerchantId($merchant_id, $filter, $sortby, $limit, $page);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getProductAlmostRunningOut(Request $request)
    {
        try {
            $merchant_id = Auth::user()->merchant_id;
            $filter = $request->filter ?? [];
            $sortby = $request->sortby ?? null;
            $limit = $request->limit ?? 3;
            $page = $request->page ?? 1;

            return $this->productQueries->getProductAlmostRunningOutByMerchantId($merchant_id, $filter, $sortby, $page, $limit);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getSpecialProduct(Request $request)
    {
        try {
            $filter = $request->filter ?? [];
            $sortby = $request->sortby ?? null;
            $limit = $request->limit ?? 10;
            $page = $request->page ?? 1;

            return $this->productQueries->getSpecialProduct($filter, $sortby, $limit, $page);
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
                'limit' => 'nullable',
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
            $page = $request->page ?? 1;
            return $this->productQueries->searchProductBySeller($merchant_id, $keyword, $limit, $filter, $sorting, $page);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function searchProductSellerV2(Request $request)
    {
        try {
            $validator = Validator::make(request()->all(), [
                'keyword' => 'required|min:3',
                'limit' => 'nullable',
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
            $page = $request->page ?? 1;
            return $this->productQueries->searchProductBySellerV2($merchant_id, $keyword, $limit, $filter, $sorting, $page);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    //get product by filter (seller)
    public function getProductByFilter(Request $request)
    {
        try {
            $merchant_id = Auth::user()->merchant_id;
            $status = $request->status;
            $limit = $request->limit ?? 10;
            $filter = $request->filter ?? [];
            $sorting = $request->sortby ?? null;
            $page = $request->page ?? 1;
            return $this->productQueries->filterProductBySeller($merchant_id, $status, $limit, $filter, $sorting, $page);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getRecommendProductByCategory($category_key, Request $request)
    {
        try {
            $limit = $request->limit ?? 10;
            $filter = $request->filter ?? [];
            $sorting = $request->sortby ?? null;
            $page = $request->page ?? 1;

            return $this->productQueries->getRecommendProductByCategory($category_key, $filter, $sorting, $limit, $page);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getElectricVehicleByCategory(Request $request, $category_key, $sub_category_key)
    {
        try {
            $limit = $request->limit ?? 10;
            $filter = $request->filter ?? [];
            $sorting = $request->sortby ?? null;
            $page = $request->page ?? 1;

            return $this->productQueries->getElectricVehicleByCategory($category_key, $sub_category_key, $filter, $sorting, $limit, $page);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getElectricVehicleWithCategoryById($category_key, $sub_category_key, $id)
    {
        try {
            $data = $this->productQueries->getElectricVehicleWithCategoryById($category_key, $sub_category_key, $id);
            return $this->respondWithData($data['data'], $data['message']);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getOtherEvProductByCategory($category_id, Request $request)
    {
        try {
            $limit = $request->limit ?? 10;
            $filter = $request->filter ?? [];
            $sorting = $request->sortby ?? null;
            $page = $request->page ?? 1;

            return $this->productQueries->getOtherEvProductByCategory($category_id, $filter, $sorting, $limit, $page);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getOtherEvProduct(Request $request)
    {
        try {
            $limit = $request->limit ?? 10;
            $filter = $request->filter ?? [];
            $sorting = $request->sortby ?? null;
            $page = $request->page ?? 1;

            return $this->productQueries->getOtherEvProductByCategory(null, $filter, $sorting, $limit, $page);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getReviewByProduct($product_id, Request $request)
    {
        try {
            $limit = $request->limit ?? 10;
            return $this->productQueries->getReviewByProduct($product_id, $limit);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getProductWithFilter(Request $request)
    {
        try {
            $limit = $request->limit ?? 10;
            $filter = $request->filter ?? [];
            $sorting = $request->sortby ?? null;
            $page = $request->page ?? 1;

            return $this->productQueries->getProductWithFilter($filter, $sorting, $limit, $page);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getProductEvSubsidy(Request $request)
    {
        try {
            $limit = $request->limit ?? 10;
            $filter = $request->filter ?? [];
            $sorting = $request->sortby ?? null;
            return $this->productQueries->getProductEvSubsidy($limit, $filter, $sorting, request()->input('page') ?? 1);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function countProductWithFilter(Request $request)
    {
        try {
            $filter = $request->filter ?? [];
            $sorting = $request->sortby ?? null;

            return $this->productQueries->countProductWithFilter($filter, $sorting);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function checkProductStock(Request $request)
    {
        try {
            $rules = [
                'product_id' => 'array|required',
            ];

            $validator = Validator::make($request->all(), $rules, [
                'required' => ':attribute diperlukan.',
                'numeric' => ':attribute harus berupa angka.',
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

            return $this->productQueries->checkProductStock($request);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }
}

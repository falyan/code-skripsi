<?php

namespace App\Http\Services\Product;

use App\Http\Services\Service;
use App\Models\Merchant;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;

class ProductQueries extends Service
{
    public function getAllProduct()
    {
        $product = new Product();
        $products = $product->withCount(['reviews', 'order_details' => function($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->with(['product_stock', 'product_photo']); //todo paginate 10

        $immutable_data = $products->get()->map(function($product) {
            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 2) : null;
            return $product;
        });

        $data = static::paginate($immutable_data->toArray());

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

    public function getProductByMerchantIdSeller($merchant_id)
    {
        $product = new Product();
        $products = $product->withCount(['reviews', 'order_details' => function($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->with(['product_stock', 'product_photo'])->where('merchant_id', $merchant_id);

        $immutable_data = $products->get()->map(function($product) {
            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 2) : null;
            return $product;
        });

        $data = static::paginate($immutable_data->toArray());

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

    public function getProductByEtalaseId($etalase_id)
    {
        $product = new Product();
        $products = $product->withCount(['reviews', 'order_details' => function($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->with(['product_stock', 'product_photo'])->where('etalase_id', $etalase_id);

        $immutable_data = $products->get()->map(function($product) {
            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 2) : null;
            return $product;
        });

        $data = static::paginate($immutable_data->toArray());

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

    public function searchProductByName($keyword, $limit = 10)
    {
        if (strlen($keyword) < 3) {
            return false;
        }

        $product = new Product();
        $products = $product->withCount(['reviews', 'order_details' => function($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->with(['product_stock', 'product_photo'])->where('name', 'ILIKE', '%' . $keyword . '%');

        $immutable_data = $products->get()->map(function($product) {
            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 2) : null;
            return $product;
        });

        $data = static::paginate($immutable_data->toArray(), $limit);

        //        if ($product->isEmpty()){
        //            $response['success'] = false;
        //            $response['message'] = 'Produk tidak tersedia.';
        //            return $response;
        //        }

        $response['success'] = true;
        $response['message'] = 'Produk berhasil didapatkan.';
        $response['data'] = $data;
        return $response;
    }
    public function getProductByMerchantIdBuyer($merchant_id, $size)
    {
        $product = new Product();

        $products = $product->withCount(['reviews', 'order_details' => function($order_details) {
            $order_details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->with(['merchant' => function ($merchant) {
            $merchant->with('city:id,name');
        }, 'order_details' => function ($trx) {
            $trx->whereHas('order', function ($j) {
                $j->whereHas('progress_done');
            });
        }, 'product_photo', 'product_stock'])->where('merchant_id', $merchant_id);

        $immutable_data = $products->get()->map(function($product) {
            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 2) : null;
            return $product;
        });

        $data = static::paginate($immutable_data->toArray(), $size);

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function getProductByCategory($category_id)
    {
        $product = new Product();
        $products = $product->withCount(['reviews', 'order_details' => function($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->with(['product_stock', 'product_photo'])->where('category_id', $category_id);

        $immutable_data = $products->get()->map(function($product) {
            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 2) : null;
            return $product;
        });

        $data = static::paginate($immutable_data->toArray());

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

    public function getProductById($id)
    {
        $data = Product::withCount(['reviews', 'order_details' => function ($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->with(['reviews' => function ($reviews) {
            $reviews->with('customer:id,full_name,image_url')->paginate(10);
        }, 'product_stock', 'product_photo', 'merchant' => function ($region) {
            $region->with(['province', 'city', 'district', 'expedition']);
        }, 'etalase', 'category', 'order_details' => function($order_details) {
            $order_details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->where('id', $id)->first();

        if (!$data) {
            $response['success'] = false;
            $response['message'] = 'Gagal mendapatkan data produk!';
            $response['data'] = $data;
            return $response;
        }

        $data['avg_rating'] = round($data->reviews()->avg('rate'), 2);

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function getRecommendProduct()
    {
        $product = new Product();
        $products = $product->withCount(['reviews', 'order_details' => function($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->with(['product_stock', 'product_photo'])->orderBy('order_details_count', 'DESC')->limit(10);

        $immutable_data = $products->get()->map(function($product) {
            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 2) : null;
            return $product;
        });

        //        if ($product->isEmpty()){
        //            $response['success'] = false;
        //            $response['message'] = 'Gagal mendapatkan data produk!';
        //            return $response;
        //        }
        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $immutable_data;
        return $response;
    }

    public function getSpecialProduct()
    {
        $product = new Product();
        $products = $product->withCount(['reviews', 'order_details' => function($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->with(['product_stock', 'product_photo'])->latest()->limit(10);

        $immutable_data = $products->get()->map(function($product) {
            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 2) : null;
            return $product;
        });

        //        if ($product->isEmpty()){
        //            $response['success'] = false;
        //            $response['message'] = 'Gagal mendapatkan data produk!';
        //            return $response;
        //        }
        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $immutable_data;
        return $response;
    }

    public function getMerchantFeaturedProduct($merchant_id)
    {
        $merchant = Merchant::with('city:id,name')->findOrFail($merchant_id);
        $product = new Product();

        $products = $product->withCount(['reviews', 'order_details' => function($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->with(['reviews', 'order_details' => function ($trx) {
            $trx->whereHas('order', function ($j) {
                $j->whereHas('progress_done');
            });
        }, 'product_photo', 'product_stock'])->where([['merchant_id', $merchant_id], ['is_featured_product', true]]);

        $immutable_data = $products->get()->map(function($product) {
            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 2) : null;
            return $product;
        });

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk unggulan!';
        $response['data'] = ['nerchant' => $merchant, 'products' => $immutable_data];
        return $response;
    }

    public function searchProductBySeller($merchant_id, $keyword, $limit)
    {
        $product = new Product();
        $products = $product->withCount(['reviews', 'order_details' => function($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->with(['product_stock', 'product_photo'])->where([['merchant_id', $merchant_id], ['name', 'ILIKE', '%' . $keyword . '%']]);

        $immutable_data = $products->get()->map(function($product) {
            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 2) : null;
            return $product;
        });

        $data = static::paginate($immutable_data->toArray(), $limit);

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }
}

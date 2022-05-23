<?php

namespace App\Http\Services\Product;

use App\Http\Services\Service;
use App\Models\MasterData;
use App\Models\MasterVariant;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\VariantValueProduct;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProductQueries extends Service
{
    public function getAllProduct($limit, $filter = [], $sortby = null, $current_page = 1)
    {
        $product = new Product();
        $products = $product
            ->withCount(['order_details' => function ($details) {
                $details->whereHas('order', function ($order) {
                    $order->whereHas('progress_done');
                });
            }])
            ->where('status', 1)->with(['product_stock', 'product_photo', 'merchant.city', 'is_wishlist'])->whereHas('merchant', function ($merchant) {
            $merchant->where('status', 1);
        }); //todo paginate 10

        $data = $this->productPaginate($products, $limit);

        // if ($data->isEmpty()) {
        //     $response['success'] = false;
        //     $response['message'] = 'Gagal mendapatkan data produk!';
        //     return $response;
        // }
        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function getProductByMerchantIdSeller($merchant_id, $filter = [], $sortby = null, $current_page = 1, $limit)
    {
        $product = new Product();
        $products = $product->withCount(['order_details' => function ($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->with(['product_stock', 'product_photo', 'is_wishlist'])->where('merchant_id', $merchant_id);

        $immutable_data = $products->get()->map(function ($product) {
            $product->reviews = null;
            //            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 1) : 0.0;
            $product->avg_rating = 0.0;
            return $product;
        });

        $data = static::paginate($immutable_data->toArray(), $limit, $current_page);

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

    public function getProductAlmostRunningOutByMerchantId($merchant_id, $filter = [], $sortby = null, $current_page = 1, $limit = 10)
    {
        // seller
        Log::info("T00001", [
            'path_url' => "merchant.running-out",
            'query' => [],
            'body' => Carbon::now('Asia/Jakarta'),
            'response' => '',
        ]);
        $product = new Product();
        $products = $product->with(['product_stock', 'product_photo', 'is_wishlist'])->where('merchant_id', $merchant_id);

        $immutable_data = $products->get()->map(function ($product) {
            $id = $product->id;
            $product->reviews = null;
            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 1) : 0.0;
            // $product->avg_rating =  null;
            $product->stock = $product->product_stock->first()->amount;
            $product->variants = MasterVariant::whereHas('variants', function ($v) use ($id) {
                $v->whereHas('variant_values', function ($vv) use ($id) {
                    $vv->where('product_id', $id);
                })->with(['variant_values' => function ($vv) use ($id) {
                    $vv->where('product_id', $id);
                }]);
            })->with(['variants' => function ($v) use ($id) {
                $v->whereHas('variant_values', function ($vv) use ($id) {
                    $vv->where('product_id', $id);
                })->with(['variant_values' => function ($vv) use ($id) {
                    $vv->where('product_id', $id);
                }]);
            }])->get();

            return $product;
        });
        $immutable_data = collect($immutable_data)->sortBy('stock');

        $data = static::paginate($immutable_data->toArray(), $limit, $current_page);

        // if ($data->isEmpty()){
        //     $response['success'] = false;
        //     $response['message'] = 'Gagal mendapatkan data produk!';
        //     return $response;
        // }
        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function getProductByEtalaseId($etalase_id, $filter = [], $sortby = null, $limit = 10, $current_page = 1)
    {
        // seller
        $product = new Product();
        $products = $product->withCount(['order_details' => function ($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->with(['product_stock', 'product_photo', 'is_wishlist'])->where('etalase_id', $etalase_id);

        $immutable_data = $products->get()->map(function ($product) {
            $product->reviews = null;
            //            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 1) : 0.0;
            $product->avg_rating = 0.0;
            return $product;
        });

        $data = static::paginate($immutable_data->toArray(), $limit, $current_page);

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

    public function searchProductByName($keyword, $limit, $filter = [], $sortby = null, $current_page = 1)
    {
        $product = new Product();
        $products = $product->withCount(['order_details' => function ($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->where('status', 1)->with(['merchant' => function ($merchant) {
            $merchant->with(['city:id,name'])->select('id', 'name', 'address', 'postal_code', 'city_id', 'photo_url');
        }, 'product_stock:id,product_id,amount,uom', 'product_photo:id,product_id,url', 'is_wishlist'])
            ->whereHas('merchant', function ($merchant) use ($filter) {
                $merchant->where('status', 1);
                $location = $filter['location'] ?? null;
                if (!empty($location)) {
                    if (strpos($location, ',')) {
                        $merchant->whereIn('city_id', explode(',', $location));
                    } else {
                        $merchant->where('city_id', 'LIKE', $location);
                    }
                }
            })->where('product.name', 'ILIKE', '%' . $keyword . '%')
            ->orWhereHas('merchant', function ($query) use ($keyword) {
                $query->where('name', 'ILIKE', '%' . $keyword . '%')->where('status', 1);
            });

        $filtered_data = $this->filter($products, $filter);
        $sorted_data = $this->sorting($filtered_data, $sortby);

        $data = $this->productPaginate($sorted_data, $limit);

        $response['success'] = true;
        $response['message'] = 'Produk berhasil didapatkan.';
        $response['data'] = $data;
        return $response;
    }

    public function getProductByMerchantIdBuyer($merchant_id, $size, $filter = [], $sortby = null, $current_page)
    {
        $product = new Product();

        $products = $product
            ->where(['merchant_id' => $merchant_id, 'status' => 1])
            ->withCount(['order_details' => function ($order_details) {
                $order_details->whereHas('order', function ($order) {
                    $order->whereHas('progress_done');
                });
            }])
            ->with(['merchant' => function ($merchant) {
                $merchant->with('city:id,name');
            },
                'product_photo', 'product_stock']);

        $filtered_data = $this->filter($products, $filter);
        $sorted_data = $this->sorting($filtered_data, $sortby);

        $data = $this->productPaginate($sorted_data, $size);

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function getProductByCategory($category_id, $filter = [], $sortby = null, $limit = 10, $current_page = 1)
    {
        $categories = MasterData::with(['child' => function ($j) {
            $j->with('child');
        }])->where('type', 'product_category')->where('id', $category_id)->get();

        $cat_child_id = [];
        foreach ($categories as $category) {
            foreach ($category->child as $child) {
                if (!$child->child->isEmpty()) {
                    foreach ($child->child as $children) {
                        array_push($cat_child_id, $children->id);
                    }
                }
            }
        }

        $product = new Product();
        $products = $product->withCount(['order_details' => function ($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->where('status', 1)->with(['merchant' => function ($merchant) {
            $merchant->with('city:id,name');
        }, 'product_stock', 'product_photo', 'is_wishlist'])->whereHas('merchant', function ($merchant) {
            $merchant->where('status', 1);
        })->whereIn('category_id', $cat_child_id);

        $data = $this->productPaginate($products, $limit);

        // if ($data->isEmpty()) {
        //     $response['success'] = false;
        //     $response['message'] = 'Gagal mendapatkan data produk!';
        //     return $response;
        // }
        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function getProductById($id)
    {
        // seller/buyer
        // $data = Product::withCount(['reviews', 'order_details' => function ($details) {
        //     $details->whereHas('order', function ($order) {
        //         $order->whereHas('progress_done');
        //     });
        // }])->with(['reviews' => function ($reviews) {
        //     $reviews->with('customer:id,full_name,image_url')->paginate(10);
        // }, 'product_stock', 'product_photo', 'merchant' => function ($region) {
        //     $region->with(['province', 'city', 'district', 'expedition']);
        // }, 'etalase', 'category', 'order_details' => function ($order_details) {
        //     $order_details->whereHas('order', function ($order) {
        //         $order->whereHas('progress_done');
        //     });
        // }])->where('id', $id)->first();

        $data = Product::withCount(['order_details' => function ($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->with(['product_stock', 'product_photo', 'is_wishlist', 'merchant' => function ($region) {
            $region->with(['province', 'city', 'district', 'expedition']);
        }, 'etalase', 'category', 'order_details' => function ($order_details) {
            $order_details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }, 'reviews' => function ($reviews) {
            $reviews->orderBy('created_at', 'desc')->limit(3)->with(['customer', 'review_photo']);
        }])->where('id', $id)->first();

        $master_variants = MasterVariant::whereHas('variants', function ($v) use ($id) {
            $v->whereHas('variant_values', function ($vv) use ($id) {
                $vv->where('product_id', $id);
            })->with(['variant_values' => function ($vv) use ($id) {
                $vv->where('product_id', $id);
            }]);
        })->with(['variants' => function ($v) use ($id) {
            $v->whereHas('variant_values', function ($vv) use ($id) {
                $vv->where('product_id', $id);
            })->with(['variant_values' => function ($vv) use ($id) {
                $vv->where('product_id', $id);
            }]);
        }])->get();

        $variant_value_product = VariantValueProduct::with(['variant_stock'])->where('product_id', $id)->get();

        $data['variants'] = $master_variants;
        $data['variant_value_products'] = $variant_value_product;

        if (!$data) {
            $response['success'] = false;
            $response['message'] = 'Gagal mendapatkan data produk!';
            $response['data'] = $data;
            return $response;
        }

        $data['avg_rating'] = ($data->reviews()->count() > 0) ? round($data->reviews()->avg('rate'), 1) : 0.0;
        $data->reviews = null;
        //        $data->avg_rating = null;

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function getRecommendProduct($filter = [], $sortby = null, $limit = 10, $current_page = 1)
    {
        Log::info("T00001", [
            'path_url' => "product.recommend",
            'query' => [],
            'body' => Carbon::now('Asia/Jakarta'),
            'response' => '',
        ]);
        $product = new Product();
        //        $products = $product->withCount(['order_details' => function ($details) {
        //            $details->whereHas('order', function ($order) {
        //                $order->whereHas('progress_done');
        //            });
        //        }])->with(['product_stock', 'product_photo', 'is_wishlist', 'merchant.city:id,name'])->whereHas('merchant', function ($merchant){
        //            $merchant->where('status', 1);
        //        })->orderBy('order_details_count', 'DESC');
        $products = $product->where('status', 1)->with(['product_stock', 'product_photo', 'is_wishlist', 'merchant.city:id,name'])
            ->whereHas('merchant', function ($merchant) {
                $merchant->where('status', 1);
            })->inRandomOrder();
        //        $products = $product->withCount(['order_details' => function ($details) {
        //            $details->whereHas('order', function ($order) {
        //                $order->whereHas('progress_done');
        //            });
        //        }])->with(['product_stock', 'product_photo', 'is_wishlist', 'merchant.city:id,name'])
        //            ->whereHas('merchant', function ($merchant){
        //                $merchant->where('status', 1);
        //            })->inRandomOrder();

        $filtered_data = $this->filter($products, $filter);
        $sorted_data = $this->sorting($filtered_data, $sortby);

        $immutable_data = $sorted_data->get()->map(function ($product) {
            $product->reviews = null;
            $product->avg_rating = 0.0;
            $product->order_details_count = 0;
            //            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 1) : 0.0;
            return $product;
        });

        $data = static::paginate($immutable_data->toArray(), (int) $limit, $current_page);

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function getBestSellingProductByMerchantId($merchant_id, $filter = [], $sortby = null, $limit = 10, $current_page = 1)
    {
        // seller
        Log::info("T00001", [
            'path_url' => "merchant.best-selling",
            'query' => [],
            'body' => Carbon::now('Asia/Jakarta'),
            'response' => '',
        ]);
        //         $product = new Product();
        //         $merchant = Merchant::with(['city'])->find($merchant_id);
        //         $products = $product->where('merchant_id', $merchant_id)->withCount(['order_details' => function ($details) {
        //             $details->whereHas('order', function ($order) {
        //                 $order->whereHas('progress_done');
        //             });
        //         }])->with(['product_stock', 'product_photo', 'is_wishlist', 'order_details' => function ($details) {
        //             $details->whereHas('order', function ($order) {
        //                 $order->whereHas('progress_done');
        //             });
        //         }])->whereHas('merchant', function ($merchant){
        //             $merchant->where('status', 1);
        //         });

        //         $filtered_data = $this->filter($products, $filter);
        //         $sorted_data = $this->sorting($filtered_data, $sortby);

        //         $immutable_data = $sorted_data->get()->map(function ($product) {
        //             $id = $product->id;
        //             $product->reviews = null;
        // //            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 1) : 0.0;
        //             $product->avg_rating =  0.0;

        //             $product->sold = 0;
        //             foreach ($product->order_details as $order_detail) {
        //                 $product->sold += $order_detail->quantity;
        //             }

        //             $product->variants = MasterVariant::whereHas('variants', function ($v) use ($id) {
        //                 $v->whereHas('variant_values', function ($vv) use ($id) {
        //                     $vv->where('product_id', $id);
        //                 })->with(['variant_values' => function ($vv) use ($id) {
        //                     $vv->where('product_id', $id);
        //                 }]);
        //             })->with(['variants' => function ($v) use ($id) {
        //                 $v->whereHas('variant_values', function ($vv) use ($id) {
        //                     $vv->where('product_id', $id);
        //                 })->with(['variant_values' => function ($vv) use ($id) {
        //                     $vv->where('product_id', $id);
        //                 }]);
        //             }])->get();

        //             return $product;
        //         });
        //         $immutable_data = collect($immutable_data)->sortBy('sold', SORT_REGULAR, true);

        $data = static::paginate([], (int) $limit, $current_page);
        // $data = array_merge(['merchant' => $merchant], $data);
        // $data = [];

        // if ($product->isEmpty()){
        //     $response['success'] = false;
        //     $response['message'] = 'Gagal mendapatkan data produk!';
        //     return $response;
        // }
        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function getSpecialProduct($filter = [], $sortby = null, $limit = 10, $current_page = 1)
    {
        Log::info("T00001", [
            'path_url' => "product.special",
            'query' => [],
            'body' => Carbon::now('Asia/Jakarta'),
            'response' => '',
        ]);
        $product = new Product();
        //        $products = $product->withCount(['order_details' => function ($details) {
        //            $details->whereHas('order', function ($order) {
        //                $order->whereHas('progress_done');
        //            });
        //        }])->with(['product_stock', 'product_photo', 'is_wishlist', 'merchant.city:id,name'])
        //            ->whereHas('merchant', function ($merchant){
        //                $merchant->where('status', 1);
        //            })->latest();
        $products = $product->with(['product_stock', 'product_photo', 'is_wishlist', 'merchant.city:id,name'])
            ->whereHas('merchant', function ($merchant) {
                $merchant->where('status', 1);
            })->where('status', 1)->latest();

        $filtered_data = $this->filter($products, $filter);
        $sorted_data = $this->sorting($filtered_data, $sortby);

        $immutable_data = $sorted_data->limit(200)->get()->map(function ($product) {
            $product->reviews = null;
            $product->avg_rating = 0.0;
            $product->order_details_count = 0;
            //            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 1) : 0.0;
            return $product;
        });
        $data = static::paginate($immutable_data->toArray(), (int) $limit, $current_page);

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function getMerchantFeaturedProduct($merchant_id)
    {
        $merchant = Merchant::with(['province:id,name', 'city:id,name', 'district:id,name'])->where('id', $merchant_id)->first(['id', 'name', 'address', 'province_id', 'city_id', 'district_id', 'postal_code', 'photo_url']);
        $merchant['url_deeplink'] = 'https://plnmarketplace.page.link/?link=https://plnmarketplace.page.link/profile-toko-seller?id=' . $merchant_id;

        $product = new Product();

        $products = $product->with(['product_photo:id,product_id,url'])->where('status', 1)->where([['merchant_id', $merchant_id], ['is_featured_product', true]])
            ->whereHas('merchant', function ($merchant) {
                $merchant->where('status', 1);
            })->select('id', 'name', 'price');

        $immutable_data = $products->get()->map(function ($product) {
            $product->url_deeplink = 'https://plnmarketplace.page.link?link=https://plnmarketplace.page.link/detail-product?id=' . $product->id;
            return $product;
        });

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk unggulan!';
        $response['data'] = ['merchant' => $merchant, 'products' => $immutable_data];
        return $response;
    }

    public function searchProductBySeller($merchant_id, $keyword, $limit, $filter = [], $sortby = null, $page = 1)
    {
        $product = new Product();
        $products = $product->withCount(['order_details' => function ($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->with(['product_stock', 'product_photo', 'is_wishlist'])->where([['merchant_id', $merchant_id], ['name', 'ILIKE', '%' . $keyword . '%']]);

        $products = $this->filter($products, $filter);
        $products = $this->sorting($products, $sortby);

        $immutable_data = $products->get()->map(function ($product) {
            $product->reviews = null;
            //            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 1) : 0.0;
            $product->avg_rating = 0.0;
            return $product;
        });

        $data = static::paginate($immutable_data->toArray(), $limit, $page);

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function getRecommendProductByCategory($category_key, $filter = [], $sortby = null, $limit = 10, $current_page = 1)
    {
        $categories = MasterData::with(['child' => function ($j) {
            $j->with(['child']);
        }])->where('type', 'product_category')->where('key', $category_key)->get();

        $cat_child = [];
        foreach ($categories as $category) {
            foreach ($category->child as $child) {
                if (!$child->child->isEmpty()) {
                    array_push($cat_child, $child->child);
                }
            }
        }
        $collection_product = [];
        foreach ($cat_child as $cat) {
            foreach ($cat as $obj) {
                $product = new Product();
                $products = $product->withCount(['order_details' => function ($details) {
                    $details->whereHas('order', function ($order) {
                        $order->whereHas('progress_done');
                    });
                }])->with(['product_stock', 'product_photo', 'is_wishlist', 'merchant.city:id,name'])
                    ->whereHas('merchant', function ($merchant) {
                        $merchant->where('status', 1);
                    })->where('category_id', $obj->id)->where('status', 1)->orderBy('order_details_count', 'ASC')->get();

                array_push($collection_product, $products);
            }
        }
        $collection = new Collection($collection_product);

        $filtered_data = $this->filter($collection->collapse(), $filter);
        $sorted_data = $this->sorting($filtered_data, $sortby);

        $immutable_data = $sorted_data->map(function ($product) {
            $product->reviews = null;
            //            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 1) : 0.0;
            $product->avg_rating = 0.0;
            return $product;
        });

        $data = static::paginate($immutable_data->toArray(), (int) $limit, $current_page);

        //        if ($product->isEmpty()){
        //            $response['success'] = false;
        //            $response['message'] = 'Gagal mendapatkan data produk!';
        //            return $response;
        //        }
        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function getProductWithFilter($filter = [], $sortby = null, $limit = 10, $current_page = 1)
    {
        $product = new Product();
        $products = $product->withCount(['order_details' => function ($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->where('status', 1)->with(['product_stock', 'product_photo', 'is_wishlist', 'merchant.city:id,name'])
            ->whereHas('merchant', function ($merchant) {
                $merchant->where('status', 1);
            });

        $filtered_data = $this->filter($products, $filter);
        $sorted_data = $this->sorting($filtered_data, $sortby);

        $immutable_data = $sorted_data->get()->map(function ($product) {
            $product->reviews = null;
            $product->avg_rating = 0.0;
            //            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 1) : 0.0;
            return $product;
        });

        $data = static::paginate($immutable_data->toArray(), (int) $limit, $current_page);

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function countProductWithFilter($filter = [], $sortby = null)
    {
        $product = new Product();
        $products = $product->withCount(['order_details' => function ($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->where('status', 1)->with(['product_stock', 'product_photo', 'is_wishlist', 'merchant.city:id,name'])
            ->whereHas('merchant', function ($merchant) {
                $merchant->where('status', 1);
            });

        $filtered_data = $this->filter($products, $filter);
        $sorted_data = $this->sorting($filtered_data, $sortby);

        $count = $sorted_data->count();

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = [
            'count_data' => $count,
        ];
        return $response;
    }

    public function filter($model, $filter = [])
    {
        if (count($filter) > 0) {
            $keyword = $filter['keyword'] ?? null;
            $category = $filter['category'] ?? null;
            $location = $filter['location'] ?? null;
            $condition = $filter['condition'] ?? null;
            $min_price = $filter['min_price'] ?? null;
            $max_price = $filter['max_price'] ?? null;

            $data = $model->when(!empty($keyword), function ($query) use ($keyword) {
                $query->where('name', 'LIKE', "%{$keyword}%");
            })->when(!empty($category), function ($query) use ($category) {
                if (strpos($category, ',')) {
                    $query->whereIn('category_id', explode(',', $category));
                } else {
                    $query->where('category_id', $category);
                }
            })->when(!empty($location), function ($query) use ($location) {
                $query->whereHas('merchant', function ($city) use ($location) {
                    if (strpos($location, ',')) {
                        $city->whereIn('city_id', explode(',', $location));
                    } else {
                        $city->where('city_id', 'LIKE', $location);
                    }
                });
            })->when(!empty($condition), function ($query) use ($condition) {
                if (strpos($condition, ',')) {
                    $query->whereIn('condition', explode(',', strtolower($condition)));
                } else {
                    $query->where('condition', 'ILIKE', $condition);
                }
            })->when(!empty($min_price), function ($query) use ($min_price) {
                $query->where('price', '>=', $min_price);
            })->when(!empty($max_price), function ($query) use ($max_price) {
                $query->where('price', '<=', $max_price);
            });

            return $data;
        } else {
            return $model;
        }
    }

    public function sorting($model, $sortby = null)
    {
        if (!empty($sortby)) {
            $data = $model->when($sortby == 'newest', function ($query) {
                $query->orderBy('created_at', 'desc');
            })->when($sortby == 'lower_price', function ($query) {
                $query->orderBy('price', 'asc');
            })->when($sortby == 'higher_price', function ($query) {
                $query->orderBy('price', 'desc');
            });

            return $data;
        } else {
            return $model;
        }
    }

    public function productPaginate($products, $limit = 10)
    {
        $itemsPaginated = $products->paginate($limit);

        $itemsTransformed = $itemsPaginated
            ->getCollection()
            ->map(function ($item) {
                $item->avg_rating = 0.0;
                $item->reviews = null;
                return $item;
            })->toArray();

        $itemsTransformedAndPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $itemsTransformed,
            $itemsPaginated->total(),
            $itemsPaginated->perPage(),
            $itemsPaginated->currentPage(), [
                // 'path' => \Illuminate\Http\Request::url(),
                'query' => [
                    'page' => $itemsPaginated->currentPage(),
                ],
            ]
        );

        return $itemsTransformedAndPaginated;
    }
}

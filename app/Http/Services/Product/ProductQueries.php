<?php

namespace App\Http\Services\Product;

use App\Http\Services\Service;
use App\Models\MasterData;
use App\Models\MasterVariant;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\Variant;
use App\Models\VariantValue;
use App\Models\VariantValueProduct;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class ProductQueries extends Service
{
    public function getAllProduct($limit, $filter = [], $sortby = null, $current_page = 1)
    {
        $product = new Product();
        $products = $product->withCount(['order_details' => function ($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->with(['product_stock', 'product_photo', 'merchant.city', 'is_wishlist']); //todo paginate 10

        $immutable_data = $products->get()->map(function ($product) {
            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 1) : 0.0;
            //            $product->avg_rating =  null;
            $product->reviews = null;
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
            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 1) : 0.0;
            //            $product->avg_rating =  null;
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
        $product = new Product();
        $products = $product->withCount(['order_details' => function ($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->with(['product_stock', 'product_photo', 'is_wishlist'])->where('etalase_id', $etalase_id);

        $immutable_data = $products->get()->map(function ($product) {
            $product->reviews = null;
            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 1) : 0.0;
            //            $product->avg_rating =  null;
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
        }])->with(['merchant' => function ($merchant) {
            $merchant->with(['city:id,name'])->select('id', 'name', 'address', 'postal_code', 'city_id', 'photo_url');
        }, 'product_stock:id,product_id,amount,uom', 'product_photo:id,product_id,url', 'is_wishlist'])
            ->where('product.name', 'ILIKE', '%' . $keyword . '%')
            ->orWhereHas('merchant', function ($query) use ($keyword) {
                $query->where('name', 'ILIKE', '%' . $keyword . '%');
            });

        $filtered_data = $this->filter($products, $filter);
        $sorted_data = $this->sorting($filtered_data, $sortby);

        $immutable_data = $sorted_data->get()->map(function ($product) {
            $product->reviews = null;
            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 1) : 0.0;
            //            $product->avg_rating =  null;
            return $product;
        });

        $data = static::paginate($immutable_data->toArray(), $limit, $current_page);

        $response['success'] = true;
        $response['message'] = 'Produk berhasil didapatkan.';
        $response['data'] = $data;
        return $response;
    }

    public function getProductByMerchantIdBuyer($merchant_id, $size, $filter = [], $sortby = null, $current_page)
    {
        $product = new Product();

        $products = $product->withCount(['order_details' => function ($order_details) {
            $order_details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->with(['merchant' => function ($merchant) {
            $merchant->with('city:id,name');
        }, 'order_details' => function ($trx) {
            $trx->whereHas('order', function ($j) {
                $j->whereHas('progress_done');
            });
        }, 'product_photo', 'product_stock', 'is_wishlist'])->where('merchant_id', $merchant_id);

        $filtered_data = $this->filter($products, $filter);
        $sorted_data = $this->sorting($filtered_data, $sortby);

        $immutable_data = $sorted_data->get()->map(function ($product) {
            $product->reviews = null;
            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 1) : 0.0;
            //            $product->avg_rating =  null;
            return $product;
        });

        $data = static::paginate($immutable_data->toArray(), $size, $current_page);

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function getProductByCategory($category_id, $filter = [], $sortby = null, $limit = 10, $current_page = 1)
    {
        $categories = MasterData::with(['child' => function ($j) {
            $j->with(['child']);
        }])->where('type', 'product_category')->where('id', $category_id)->get();

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
                }])->with(['merchant' => function ($merchant) {
                    $merchant->with('city:id,name');
                }, 'product_stock', 'product_photo', 'is_wishlist'])->where('category_id', $obj->id)->get();

                array_push($collection_product, $products);
            }
        }

        $collection = new Collection($collection_product);

        $filtered_data = $this->filter($collection->collapse(), $filter);
        $sorted_data = $this->sorting($filtered_data, $sortby);

        $immutable_data = $sorted_data->map(function ($product) {
            $product->reviews = null;
            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 1) : 0.0;
            //            $product->avg_rating =  null;
            return $product;
        });

        $data = static::paginate($immutable_data->toArray(), (int) $limit, $current_page);

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

        $variant_value_product = VariantValueProduct::where('product_id', $id)->get();

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
        $product = new Product();
        $products = $product->withCount(['order_details' => function ($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->with(['product_stock', 'product_photo', 'is_wishlist', 'merchant.city:id,name'])->orderBy('order_details_count', 'DESC');

        $filtered_data = $this->filter($products, $filter);
        $sorted_data = $this->sorting($filtered_data, $sortby);

        $immutable_data = $sorted_data->get()->map(function ($product) {
            $product->reviews = null;
            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 1) : 0.0;
            //            $product->avg_rating =  null;
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

    public function getBestSellingProductByMerchantId($merchant_id, $filter = [], $sortby = null, $limit = 10, $current_page = 1)
    {
        $product = new Product();
        $merchant = Merchant::with(['city'])->find($merchant_id);
        $products = $product->where('merchant_id', $merchant_id)->withCount(['order_details' => function ($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->with(['product_stock', 'product_photo', 'is_wishlist', 'order_details' => function ($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }]);

        $filtered_data = $this->filter($products, $filter);
        $sorted_data = $this->sorting($filtered_data, $sortby);

        $immutable_data = $sorted_data->get()->map(function ($product) {
            $id = $product->id;
            $product->reviews = null;
            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 1) : 0.0;
            // $product->avg_rating =  null;

            $product->sold = 0;
            foreach ($product->order_details as $order_detail) {
                $product->sold += $order_detail->quantity;
            }

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
        $immutable_data = collect($immutable_data)->sortBy('sold', SORT_REGULAR, true);

        $data = static::paginate($immutable_data->toArray(), (int) $limit, $current_page);
        $data = array_merge(['merchant' => $merchant], $data);

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
        $product = new Product();
        $products = $product->withCount(['order_details' => function ($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->with(['product_stock', 'product_photo', 'is_wishlist', 'merchant.city:id,name'])->latest();

        $filtered_data = $this->filter($products, $filter);
        $sorted_data = $this->sorting($filtered_data, $sortby);

        $immutable_data = $sorted_data->get()->map(function ($product) {
            $product->reviews = null;
            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 1) : 0.0;
            //            $product->avg_rating =  null;
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

    public function getMerchantFeaturedProduct($merchant_id)
    {
        $merchant = Merchant::with(['province:id,name', 'city:id,name', 'district:id,name'])->where('id', $merchant_id)->first(['id', 'name', 'address', 'province_id', 'city_id', 'district_id', 'postal_code', 'photo_url']);
        $merchant['url_deeplink'] = 'https://plnmarketplace.page.link/?link=https://plnmarketplace.page.link/profile-toko-seller?id=' . $merchant_id;


        $product = new Product();

        $products = $product->with(['product_photo:id,product_id,url'])->where([['merchant_id', $merchant_id], ['is_featured_product', true]])
            ->select('id', 'name', 'price');

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
            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 1) : 0.0;
            //            $product->avg_rating =  null;
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
                }])->with(['product_stock', 'product_photo', 'is_wishlist', 'merchant.city:id,name'])->where('category_id', $obj->id)->orderBy('order_details_count', 'ASC')->get();

                array_push($collection_product, $products);
            }
        }
        $collection = new Collection($collection_product);

        $filtered_data = $this->filter($collection->collapse(), $filter);
        $sorted_data = $this->sorting($filtered_data, $sortby);

        $immutable_data = $sorted_data->map(function ($product) {
            $product->reviews = null;
            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 1) : 0.0;
            //            $product->avg_rating =  null;
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
                $query->whereHas('merchant:city_id', function ($city) use ($location) {
                    if (strpos($location, ',')) {
                        $city->whereIn('merchant:city_id', explode(',', $location));
                    } else {
                        $city->where('merchant:city_id', 'LIKE', $location);
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
}

<?php

namespace App\Http\Services\Product;

use App\Http\Services\Service;
use App\Models\MasterData;
use App\Models\MasterEvStore;
use App\Models\MasterVariant;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\Review;
use App\Models\VariantValueProduct;
use App\Models\ProductEvSubsidy;
use Carbon\Carbon;
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
            ->where('status', 1)
            ->with([
                'product_stock',
                'product_photo',
                'merchant',
                'merchant.city:id,name',
                'merchant.promo_merchant' => function ($pd) {
                    $pd->where(function ($query) {
                        $query->where('start_date', '<=', date('Y-m-d H:i:s'))
                            ->where('end_date', '>=', date('Y-m-d H:i:s'));
                    });
                },
                'merchant.promo_merchant.promo_master',
                'merchant.promo_merchant.promo_master.promo_values',
                'is_wishlist', 'varian_product' => function ($query) {
                    $query->with(['variant_stock'])->where('main_variant', true);
                },
                'ev_subsidy',
            ])
            ->whereHas('merchant', function ($merchant) {
                $merchant->where('status', 1);
            });

        $filtered_data = $this->filter($products, $filter);
        $sorted_data = $this->sorting($filtered_data, $sortby);
        // return $sorted_data->paginate($limit);

        $data = $this->productPaginate($sorted_data, $limit);

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function getProductByMerchantIdSeller($merchant_id, $filter = [], $sortby = null, $current_page = 1, $limit, $featured)
    {
        $product = new Product();

        $products = $product
            ->where(['merchant_id' => $merchant_id])
            ->withCount(['order_details' => function ($order_details) {
                $order_details->whereHas('order', function ($order) {
                    $order->whereHas('progress_done');
                });
            }])
            ->with([
                'product_photo',
                'product_stock',
                'is_wishlist',
                'varian_product' => function ($query) {
                    $query->with(['variant_stock'])->where('main_variant', true);
                },
                'ev_subsidy',
            ])->when($featured == true, function ($query) {
                $query->where('is_featured_product', true);
            });

        $filtered_data = $this->filter($products, $filter);
        $sorted_data = $this->sorting($filtered_data, $sortby);

        $data = $this->productPaginate($sorted_data, $limit);

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
        $products = $product->with([
            'product_stock',
            'product_photo',
            'is_wishlist',
            'ev_subsidy',
        ])->where('merchant_id', $merchant_id);

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
        }])->with(['product_stock', 'product_photo', 'is_wishlist', 'ev_subsidy'])->where('etalase_id', $etalase_id);

        $immutable_data = $products->get()->map(function ($product) {
            $product->reviews = null;
            // $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 1) : 0.0;
            // $product->avg_rating = 0.0;
            return $product;
        });

        $data = static::paginate($immutable_data->toArray(), $limit, $current_page);

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
        }])->where('status', 1)->with([
            'product_stock:id,product_id,amount,uom',
            'product_photo:id,product_id,url',
            'is_wishlist',
            'merchant',
            'merchant.city:id,name',
            'merchant.promo_merchant' => function ($pd) {
                $pd->where(function ($query) {
                    $query->where('start_date', '<=', date('Y-m-d H:i:s'))
                        ->where('end_date', '>=', date('Y-m-d H:i:s'));
                });
            },
            'merchant.promo_merchant.promo_master',
            'merchant.promo_merchant.promo_master.promo_values',
            'varian_product' => function ($query) {
                $query->with(['variant_stock'])->where('main_variant', true);
            },
            'ev_subsidy',
        ])
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

    public function searchProductByNameV2($keyword, $limit, $filter = [], $sortby = null, $current_page = 1)
    {
        $product = new Product();
        $products = $product->withCount(['order_details' => function ($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->where('status', 1)->with([
            'product_stock:id,product_id,amount,uom',
            'product_photo:id,product_id,url',
            'is_wishlist',
            'merchant',
            'merchant.city:id,name',
            'merchant.promo_merchant' => function ($pd) {
                $pd->where(function ($query) {
                    $query->where('start_date', '<=', date('Y-m-d H:i:s'))
                        ->where('end_date', '>=', date('Y-m-d H:i:s'));
                });
            },
            'merchant.promo_merchant.promo_master',
            'merchant.promo_merchant.promo_master.promo_values',
            'varian_product' => function ($query) {
                $query->with(['variant_stock'])->where('main_variant', true);
            },
            'ev_subsidy',
        ])
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
            })->where('product.name', 'ILIKE', '%' . $keyword . '%');
        // ->orWhereHas('merchant', function ($query) use ($keyword) {
        //     $query->where('name', 'ILIKE', '%' . $keyword . '%')->where('status', 1);
        // });

        $filtered_data = $this->filter($products, $filter);
        $sorted_data = $this->sorting($filtered_data, $sortby);

        $data = $this->productPaginate($sorted_data, $limit);

        $response['success'] = true;
        $response['message'] = 'Produk berhasil didapatkan.';
        $response['data'] = $data;
        return $response;
    }

    public function getProductFeatured($merchant_id, $limit, $filter = [], $sortby = null, $current_page)
    {
        $product = new Product();

        $products = $product
            ->where(['merchant_id' => $merchant_id, 'status' => 1, 'is_featured_product' => true])
            ->withCount(['order_details' => function ($order_details) {
                $order_details->whereHas('order', function ($order) {
                    $order->whereHas('progress_done');
                });
            }])
            ->with([
                'product_photo',
                'product_stock',
                'merchant',
                'merchant.city:id,name',
                'merchant.promo_merchant' => function ($pd) {
                    $pd->where(function ($query) {
                        $query->where('start_date', '<=', date('Y-m-d H:i:s'))
                            ->where('end_date', '>=', date('Y-m-d H:i:s'));
                    });
                },
                'merchant.promo_merchant.promo_master',
                'merchant.promo_merchant.promo_master.promo_values',
                'varian_product' => function ($query) {
                    $query->with(['variant_stock'])->where('main_variant', true);
                },
                'ev_subsidy',
            ])
            ->orderBy('updated_at', 'desc');

        $filtered_data = $this->filter($products, $filter);
        $sorted_data = $this->sorting($filtered_data, $sortby);

        $data = $this->productPaginate($sorted_data, $limit);

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
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
            ->with([
                'product_photo',
                'product_stock',
                'merchant',
                'merchant.city:id,name',
                'merchant.promo_merchant' => function ($pd) {
                    $pd->where(function ($query) {
                        $query->where('start_date', '<=', date('Y-m-d H:i:s'))
                            ->where('end_date', '>=', date('Y-m-d H:i:s'));
                    });
                },
                'merchant.promo_merchant.promo_master',
                'merchant.promo_merchant.promo_master.promo_values',
                'varian_product' => function ($query) {
                    $query->with(['variant_stock'])->where('main_variant', true);
                },
                'ev_subsidy',
            ]);

        $filtered_data = $this->filter($products, $filter);
        $sorted_data = $this->sorting($filtered_data, $sortby);

        $data = $this->productPaginate($sorted_data, $size);

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function getProductByMerchantIdBuyerAndSearch($merchant_id, $size, $filter = [], $sortby = null, $current_page)
    {
        $product = new Product();

        $products = $product
            ->where(['merchant_id' => $merchant_id, 'status' => 1])
            ->withCount(['order_details' => function ($order_details) {
                $order_details->whereHas('order', function ($order) {
                    $order->whereHas('progress_done');
                });
            }])
            ->with([
                'product_photo',
                'product_stock',
                'merchant',
                'merchant.city:id,name',
                'merchant.promo_merchant' => function ($pd) {
                    $pd->where('status', 1);
                    $pd->where(function ($query) {
                        $query->where('start_date', '<=', date('Y-m-d H:i:s'))
                            ->where('end_date', '>=', date('Y-m-d H:i:s'));
                    });
                    $pd->whereHas('promo_master', function ($pm) {
                        $pm->where('status', 1);
                    });
                },
                'merchant.promo_merchant.promo_master' => function ($pm) {
                    $pm->where('status', 1);
                },
                'varian_product' => function ($query) {
                    $query->with(['variant_stock'])->where('main_variant', true);
                },
                'ev_subsidy',
            ]);

        if (!empty($filter['category_id_parent'])) {
            $cat_id_parent = $filter['category_id_parent'];

            $categories = MasterData::with(['child' => function ($j) {
                $j->with('child');
            }])->where('type', 'product_category')->where('id', $cat_id_parent)->get();

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

            $products = $products->whereIn('category_id', $cat_child_id);
        }

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
        }])->where('status', 1)->with([
            'product_stock',
            'product_photo',
            'is_wishlist',
            'merchant',
            'merchant.city:id,name',
            'merchant.promo_merchant' => function ($pd) {
                $pd->where(function ($query) {
                    $query->where('start_date', '<=', date('Y-m-d H:i:s'))
                        ->where('end_date', '>=', date('Y-m-d H:i:s'));
                });
            },
            'merchant.promo_merchant.promo_master',
            'merchant.promo_merchant.promo_master.promo_values',
            'varian_product' => function ($query) {
                $query->with(['variant_stock'])->where('main_variant', true);
            },
            'ev_subsidy',
        ])->whereHas('merchant', function ($merchant) {
            $merchant->where('status', 1);
        })->whereIn('category_id', $cat_child_id);

        $filtered_data = $this->filter($products, $filter);
        $sorted_data = $this->sorting($filtered_data, $sortby);

        $data = $this->productPaginate($sorted_data, $limit);

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function getProductById($id, $seller = false)
    {
        $product = Product::withCount(['order_details' => function ($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])
            ->with([
                'product_stock',
                'product_photo',
                'is_wishlist',
                'merchant' => function ($region) {
                    $region->with(['province', 'city', 'district', 'expedition']);
                },
                'merchant.promo_merchant' => function ($pd) {
                    $pd->where(function ($query) {
                        $query->where('start_date', '<=', date('Y-m-d H:i:s'))
                            ->where('end_date', '>=', date('Y-m-d H:i:s'));
                    });
                },
                'merchant.promo_merchant.promo_master',
                'merchant.promo_merchant.promo_master.promo_values',
                'etalase', 'category', 'order_details' => function ($order_details) {
                    $order_details->whereHas('order', function ($order) {
                        $order->whereHas('progress_done');
                    });
                },
                'reviews' => function ($reviews) {
                    $reviews->orderBy('created_at', 'desc')->limit(3)->with(['customer', 'review_photo'])->where('status', 1);
                },
                'discussion_master' => function ($master) {
                    $master->orderBy('created_at', 'desc')->limit(2)->with(['discussion_response']);
                },
                'ev_subsidy',
            ])
            ->where('id', $id)->first();

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

        $variant_value_product = VariantValueProduct::with(['variant_stock'])
            ->when($seller == false, function ($query) {
                return $query->where('status', 1);
            })
            ->where('product_id', $id)->orderBy('main_variant', 'desc')->get();

        if (!$product) {
            $response['success'] = false;
            $response['message'] = 'Gagal mendapatkan data produk!';
            $response['data'] = $product;
            return $response;
        }

        $product['variants'] = $master_variants;
        $product['variant_value_products'] = $variant_value_product;

        $is_shipping_discount = false;
        $is_flash_sale_discount = false;
        $promo_value = 0;
        $promo_type = '';
        $promo_min_order = 0;
        $wording_title = '';
        $wording_subtitle = '';

        $item = $product->toArray();

        if (isset($item['merchant']['promo_merchant']) && $item['merchant']['can_shipping_discount'] == true) {
            foreach ($item['merchant']['promo_merchant'] as $promo) {
                if (isset($promo['promo_master']['event_type']) && $promo['promo_master']['event_type'] == 'ongkir') {
                    if ($promo['promo_master']['value_2'] >= $promo['promo_master']['value_1']) {
                        $value_ongkir = $promo['promo_master']['value_2'];
                    } else {
                        $value_ongkir = $promo['promo_master']['value_1'];
                    }

                    $max_merchant = ($promo['usage_value'] + $value_ongkir) > $promo['max_value'];
                    $max_master = ($promo['promo_master']['usage_value'] + $value_ongkir) > $promo['promo_master']['max_value'];

                    if ($max_merchant && !$max_master) {
                        $is_shipping_discount = true;
                        break;
                    }

                    if (!$max_merchant && $max_master) {
                        $is_shipping_discount = true;
                        break;
                    }

                    if (!$max_merchant && !$max_master) {
                        $is_shipping_discount = true;
                        break;
                    }
                }
            }
        }

        if (isset($item['merchant']['promo_merchant']) && $item['merchant']['can_flash_sale_discount'] == true) {
            foreach ($item['merchant']['promo_merchant'] as $promo) {
                if (isset($promo['promo_master']['event_type']) && $promo['promo_master']['event_type'] == 'flash_sale') {
                    $value_flash_sale_m = 0;

                    $value_flash_sale_m = $promo['promo_master']['value_1'];
                    if ($promo['promo_master']['promo_value_type'] == 'percentage') {
                        $value_flash_sale_m = $item['price'] * ($promo['promo_master']['value_1'] / 100);
                        if ($value_flash_sale_m >= $promo['promo_master']['max_discount_value']) {
                            $value_flash_sale_m = $promo['promo_master']['max_discount_value'];
                        }
                    }

                    foreach ($promo['promo_master']['promo_values'] as $promo_value_) {
                        $value_flash_sale_m = $promo['promo_master']['value_1'];
                        if ($promo['promo_master']['promo_value_type'] == 'percentage') {
                            $value_flash_sale_m = $item['price'] * ($promo['promo_master']['value_1'] / 100);
                            if ($value_flash_sale_m >= $promo['promo_master']['max_discount_value']) {
                                $value_flash_sale_m = $promo['promo_master']['max_discount_value'];
                            }
                        }

                        if ($item['price'] >= $promo_value_['min_value'] && $item['price'] <= $promo_value_['max_value'] && $promo_value_['status'] == 1) {
                            if ($value_flash_sale_m >= $promo_value_['max_discount_value']) {
                                $value_flash_sale_m = $promo_value_['max_discount_value'];
                            }

                            break;
                        }
                    }

                    $max_merchant = ($promo['usage_value'] + $value_flash_sale_m) > $promo['max_value'];
                    $max_master = ($promo['promo_master']['usage_value'] + $value_flash_sale_m) > $promo['promo_master']['max_value'];

                    if ($max_merchant && !$max_master) {
                        $is_flash_sale_discount = true;
                        $promo_value = $promo['promo_master']['value_1'];
                        $promo_type = $promo['promo_master']['promo_value_type'];
                        $promo_min_order = $promo['promo_master']['min_order_value'];
                        break;
                    }

                    if (!$max_merchant && $max_master) {
                        $is_flash_sale_discount = true;
                        $promo_value = $promo['promo_master']['value_1'];
                        $promo_type = $promo['promo_master']['promo_value_type'];
                        $promo_min_order = $promo['promo_master']['min_order_value'];
                        break;
                    }

                    if (!$max_merchant && !$max_master) {
                        $is_flash_sale_discount = true;
                        $promo_value = $promo['promo_master']['value_1'];
                        $promo_type = $promo['promo_master']['promo_value_type'];
                        $promo_min_order = $promo['promo_master']['min_order_value'];
                        break;
                    }
                }
            }
        }

        unset($item['merchant']['promo_merchant']);
        $item['merchant']['is_shipping_discount'] = $is_shipping_discount;
        $item['is_flash_sale_discount'] = $is_flash_sale_discount;
        $item['promo_value'] = $promo_value;
        $item['promo_type'] = $promo_type;

        if ($promo_value != 0) {
            $master_data = MasterData::whereIn('key', ['promo_wording_title', 'promo_wording_subtitle'])->get();

            $wording_title = collect($master_data)->where('key', 'promo_wording_title')->first()['value'];
            $wording_subtitle = collect($master_data)->where('key', 'promo_wording_subtitle')->first()['value'];

            $item['promo_wording_title'] = $wording_title;
            $if_type_wording = $promo_type == 'percentage' ? $promo_value . '% ' : 'Rp ' . number_format($promo_value, 0, ',', '.') . ' ';
            $item['promo_wording_subtitle'] = explode('/', $wording_subtitle)[0] . $if_type_wording . explode('/', $wording_subtitle)[1] . "Rp " . number_format($promo_min_order, 0, ',', '.');
        }

        $item['avg_rating'] = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 1) : 0.0;
        $item['reviews'] = null;

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $item;
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
        $products = $product
            ->where('status', 1)
            ->with([
                'product_stock',
                'product_photo',
                'is_wishlist',
                'merchant',
                'merchant.city:id,name',
                'merchant.promo_merchant' => function ($pd) {
                    $pd->where(function ($query) {
                        $query->where('start_date', '<=', date('Y-m-d H:i:s'))
                            ->where('end_date', '>=', date('Y-m-d H:i:s'));
                    });
                },
                'merchant.promo_merchant.promo_master',
                'merchant.promo_merchant.promo_master.promo_values',
                'varian_product' => function ($query) {
                    $query->with(['variant_stock'])->where('main_variant', true);
                },
            ])
            ->whereHas('merchant', function ($merchant) {
                $merchant->where('status', 1);
            })
            ->inRandomOrder();

        $filtered_data = $this->filter($products, $filter);
        $sorted_data = $this->sorting($filtered_data, $sortby);

        $data = $this->productPaginate($sorted_data, $limit);

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function getRecommendProductNew($filter = [], $sortby = null, $limit = 10, $current_page = 1)
    {
        Log::info("T00001", [
            'path_url' => "product.recommend",
            'query' => [],
            'body' => Carbon::now('Asia/Jakarta'),
            'response' => '',
        ]);

        $categories = MasterData::with([
            'child' => fn ($j) => $j->whereNotIn('key', ['prodcat_mobil_listrik', 'prodcat_motor_listrik', 'prodcat_sepeda_listrik']),
            'child.child' => fn ($q) => $q->whereNotIn('key', ['prodcat_mobil_listrik', 'prodcat_mobil_listrik_', 'prodcat_sepeda_listrik_']),
        ])->where([
            'type' => 'product_category',
            'key' => 'prodcat_electric_vehicle',
        ])->get();

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
        }])->where('status', 1)->with([
            'product_stock', 'product_photo', 'is_wishlist',
            'merchant' => function ($merchant) {
                $merchant->with(['city:id,name']);
            },
            'varian_product' => function ($query) {
                $query->with(['variant_stock'])->where('main_variant', true);
            },
            'ev_subsidy',
        ])->whereHas('merchant', function ($merchant) {
            $merchant->where('status', 1);
        })->whereIn('category_id', $cat_child_id);

        $data = $this->productPaginate($products, $limit);

        if ($data->isEmpty()) {
            $response['success'] = false;
            $response['message'] = 'Produk tidak tersedia!';
            return $response;
        }

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

        $product = new Product();
        // $merchant = Merchant::with(['city'])->find($merchant_id);
        $products = $product
            ->where('merchant_id', $merchant_id)
            ->withCount(['order_details' => function ($details) {
                $details->whereHas('order', function ($order) {
                    $order->whereHas('progress_done');
                });
            }])
            ->with([
                'product_stock',
                'product_photo',
                'is_wishlist',
                'order_details' => function ($details) {
                    $details->whereHas('order', function ($order) {
                        $order->whereHas('progress_done');
                    });
                },
                'ev_subsidy',
            ])->whereHas('merchant', function ($merchant) {
                $merchant->where('status', 1);
            })->orderBy('order_details_count', 'DESC');

        $itemsPaginated = $products->paginate($limit);

        $itemsTransformed = $itemsPaginated
            ->getCollection()
            ->map(function ($product) {
                $id = $product->id;
                $product->reviews = null;
                // $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 1) : 0.0;
                // $product->avg_rating = 0.0;

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
            })->toArray();

        $data = new \Illuminate\Pagination\LengthAwarePaginator(
            $itemsTransformed,
            $itemsPaginated->total(),
            $itemsPaginated->perPage(),
            $itemsPaginated->currentPage(),
            [
                // 'path' => \Illuminate\Http\Request::url(),
                'query' => [
                    'page' => $itemsPaginated->currentPage(),
                ],
            ]
        );

        // $filtered_data = $this->filter($products, $filter);
        // $sorted_data = $this->sorting($filtered_data, $sortby);

        // $immutable_data = $sorted_data->get()->map(function ($product) {
        //     $id = $product->id;
        //     $product->reviews = null;
        //     // $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 1) : 0.0;
        //     $product->avg_rating = 0.0;

        //     $product->sold = 0;
        //     foreach ($product->order_details as $order_detail) {
        //         $product->sold += $order_detail->quantity;
        //     }

        //     $product->variants = MasterVariant::whereHas('variants', function ($v) use ($id) {
        //         $v->whereHas('variant_values', function ($vv) use ($id) {
        //             $vv->where('product_id', $id);
        //         })->with(['variant_values' => function ($vv) use ($id) {
        //             $vv->where('product_id', $id);
        //         }]);
        //     })->with(['variants' => function ($v) use ($id) {
        //         $v->whereHas('variant_values', function ($vv) use ($id) {
        //             $vv->where('product_id', $id);
        //         })->with(['variant_values' => function ($vv) use ($id) {
        //             $vv->where('product_id', $id);
        //         }]);
        //     }])->get();

        //     return $product;
        // });

        // $immutable_data = collect($immutable_data)->sortBy('sold', SORT_REGULAR, true);
        // $collection = new Collection($immutable_data);

        // $filtered_data = $this->filter($collection->collapse(), $filter);
        // $sorted_data = $this->sorting($filtered_data, $sortby);

        // $immutable_data = $sorted_data->map(function ($product) {
        //     $product->reviews = null;
        //     // $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 1) : 0.0;
        //     $product->avg_rating = 0.0;
        //     return $product;
        // });

        // $data = static::paginate([], (int) $limit, $current_page);
        // $data = array_merge(['merchant' => $merchant], $data);
        // $data = [];

        // if ($product->isEmpty()) {
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
        $products = $product
            ->with([
                'product_stock',
                'product_photo',
                'is_wishlist',
                'merchant',
                'merchant.city:id,name',
                'merchant.promo_merchant' => function ($pd) {
                    $pd->where(function ($query) {
                        $query->where('start_date', '<=', date('Y-m-d H:i:s'))
                            ->where('end_date', '>=', date('Y-m-d H:i:s'));
                    });
                },
                'merchant.promo_merchant.promo_master',
                'merchant.promo_merchant.promo_master.promo_values',
                'varian_product' => function ($query) {
                    $query->with(['variant_stock'])->where('main_variant', true);
                },
                'ev_subsidy',
            ])
            ->whereHas('merchant', function ($merchant) {
                $merchant->where('status', 1);
            })
            ->where('status', 1)->latest();

        $filtered_data = $this->filter($products, $filter);
        $sorted_data = $this->sorting($filtered_data, $sortby);

        $data = $this->productPaginate($sorted_data, $limit);

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function getSpecialProductNew($filter = [], $sortby = null, $limit = 10, $current_page = 1)
    {
        Log::info("T00001", [
            'path_url' => "product.recommend",
            'query' => [],
            'body' => Carbon::now('Asia/Jakarta'),
            'response' => '',
        ]);

        $categories = MasterData::with([
            'child' => fn ($j) => $j->whereNotIn('key', ['prodcat_mobil_listrik', 'prodcat_motor_listrik', 'prodcat_sepeda_listrik']),
            'child.child' => fn ($q) => $q->whereNotIn('key', ['prodcat_mobil_listrik', 'prodcat_mobil_listrik_', 'prodcat_sepeda_listrik_']),
        ])->where([
            'type' => 'product_category',
            'key' => 'prodcat_electric_vehicle',
        ])->get();

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
        }])->where('status', 1)->with([
            'product_stock', 'product_photo', 'is_wishlist',
            'merchant' => function ($merchant) {
                $merchant->with(['city:id,name']);
            },
            'varian_product' => function ($query) {
                $query->with(['variant_stock'])->where('main_variant', true);
            },
            'ev_subsidy',
        ])->whereHas('merchant', function ($merchant) {
            $merchant->where('status', 1);
        })->whereIn('category_id', $cat_child_id);

        $data = $this->productPaginate($products, $limit);

        if ($data->isEmpty()) {
            $response['success'] = false;
            $response['message'] = 'Produk tidak tersedia!';
            return $response;
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function getMerchantFeaturedProduct($merchant_id)
    {
        $merchant = Merchant::with([
            'province:id,name',
            'city:id,name',
            'district:id,name',
            'city:id,name',
            'promo_merchant' => function ($pd) {
                $pd->where(function ($query) {
                    $query->where('start_date', '<=', date('Y-m-d'))
                        ->where('end_date', '>=', date('Y-m-d'));
                });
            },
            'promo_merchant.promo_master',
        ])
            ->where('id', $merchant_id)
            ->first(['id', 'name', 'address', 'province_id', 'city_id', 'district_id', 'postal_code', 'photo_url', 'can_shipping_discount', 'can_flash_sale_discount', 'is_shipping_discount']);

        $merchant->url_deeplink = 'https://plnmarketplace.page.link/?link=https://plnmarketplace.page.link/profile-toko-seller?id=' . $merchant_id;

        $is_shipping_discount = false;
        $is_flash_sale_discount = false;
        $promo_value = 0;
        $promo_type = '';

        $item = $merchant->toArray();

        if (isset($item['promo_merchant']) && $item['can_shipping_discount'] == true) {
            foreach ($item['promo_merchant'] as $promo) {
                if (isset($promo['promo_master']['event_type']) && $promo['promo_master']['event_type'] == 'ongkir') {
                    if ($promo['promo_master']['value_2'] >= $promo['promo_master']['value_1']) {
                        $value_ongkir = $promo['promo_master']['value_2'];
                    } else {
                        $value_ongkir = $promo['promo_master']['value_1'];
                    }

                    $max_merchant = ($promo['usage_value'] + $value_ongkir) > $promo['max_value'];
                    $max_master = ($promo['promo_master']['usage_value'] + $value_ongkir) > $promo['promo_master']['max_value'];

                    if ($max_merchant && !$max_master) {
                        $is_shipping_discount = true;
                        break;
                    }

                    if (!$max_merchant && $max_master) {
                        $is_shipping_discount = true;
                        break;
                    }

                    if (!$max_merchant && !$max_master) {
                        $is_shipping_discount = true;
                        break;
                    }
                }
            }
        }

        if (isset($item['promo_merchant']) && $item['can_flash_sale_discount'] == true) {
            foreach ($item['promo_merchant'] as $promo) {
                if (isset($promo['promo_master']['event_type']) && $promo['promo_master']['event_type'] == 'flash_sale') {
                    $value_flash_sale_m = 0;

                    $value_flash_sale_m = $promo['promo_master']['value_1'];
                    if ($promo['promo_master']['promo_value_type'] == 'percentage') {
                        $value_flash_sale_m = $item['price'] * ($promo['promo_master']['value_1'] / 100);
                        if ($value_flash_sale_m >= $promo['promo_master']['max_discount_value']) {
                            $value_flash_sale_m = $promo['promo_master']['max_discount_value'];
                        }
                    }

                    foreach ($promo['promo_master']['promo_values'] as $promo_value) {
                        $value_flash_sale_m = $promo['promo_master']['value_1'];
                        if ($promo['promo_master']['promo_value_type'] == 'percentage') {
                            $value_flash_sale_m = $item['price'] * ($promo['promo_master']['value_1'] / 100);
                            if ($value_flash_sale_m >= $promo['promo_master']['max_discount_value']) {
                                $value_flash_sale_m = $promo['promo_master']['max_discount_value'];
                            }
                        }

                        if ($item['price'] >= $promo_value['min_value'] && $item['price'] <= $promo_value['max_value'] && $promo_value['status'] == 1) {
                            if ($value_flash_sale_m >= $promo_value['max_discount_value']) {
                                $value_flash_sale_m = $promo_value['max_discount_value'];
                            }

                            break;
                        }
                    }

                    $max_merchant = ($promo['usage_value'] + $value_flash_sale_m) > $promo['max_value'];
                    $max_master = ($promo['promo_master']['usage_value'] + $value_flash_sale_m) > $promo['promo_master']['max_value'];

                    if ($max_merchant && !$max_master) {
                        $is_flash_sale_discount = true;
                        $promo_value = $promo['promo_master']['value_1'];
                        $promo_type = $promo['promo_master']['promo_value_type'];
                        break;
                    }

                    if (!$max_merchant && $max_master) {
                        $is_flash_sale_discount = true;
                        $promo_value = $promo['promo_master']['value_1'];
                        $promo_type = $promo['promo_master']['promo_value_type'];
                        break;
                    }

                    if (!$max_merchant && !$max_master) {
                        $is_flash_sale_discount = true;
                        $promo_value = $promo['promo_master']['value_1'];
                        $promo_type = $promo['promo_master']['promo_value_type'];
                        break;
                    }
                }
            }
        }

        unset($merchant['promo_merchant']);
        $merchant['is_shipping_discount'] = $is_shipping_discount;

        $product = new Product();
        $products = $product
            ->with(['product_photo:id,product_id,url', 'ev_subsidy'])->where('status', 1)
            ->where([['merchant_id', $merchant_id], ['is_featured_product', true]])
            ->whereHas('merchant', function ($merchant) {
                $merchant->where('status', 1);
            })
            ->select('id', 'name', 'price');

        $immutable_data = $products->get()->map(function ($product) use ($is_flash_sale_discount, $promo_value, $promo_type) {
            $product->url_deeplink = 'https://plnmarketplace.page.link?link=https://plnmarketplace.page.link/detail-product?id=' . $product->id;

            $product['is_flash_sale_discount'] = $is_flash_sale_discount;
            $product['promo_value'] = $promo_value;
            $product['promo_type'] = $promo_type;
            $product['reviews'] = null;
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
        }])
            ->with(['product_stock', 'product_photo', 'is_wishlist', 'ev_subsidy'])->where([['merchant_id', $merchant_id], ['name', 'ILIKE', '%' . $keyword . '%']]);

        $products = $this->filter($products, $filter);
        $products = $this->sorting($products, $sortby);

        $immutable_data = $products->get()->map(function ($product) {
            $product->reviews = null;
            // $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 1) : 0.0;
            // $product->avg_rating = 0.0;
            return $product;
        });

        $data = static::paginate($immutable_data->toArray(), $limit, $page);

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function searchProductBySellerV2($merchant_id, $keyword, $limit, $filter = [], $sortby = null, $page = 1)
    {
        $product = new Product();
        $products = $product->withCount(['order_details' => function ($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->with(['product_stock', 'product_photo', 'is_wishlist', 'ev_subsidy'])->where([['merchant_id', $merchant_id], ['name', 'ILIKE', '%' . $keyword . '%']]);

        $products = $this->filter($products, $filter);
        $products = $this->sorting($products, $sortby);

        $immutable_data = $products->get()->map(function ($product) {
            $product->reviews = null;
            // $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 1) : 0.0;
            // $product->avg_rating = 0.0;
            return $product;
        });

        $data = static::paginate($immutable_data->toArray(), $limit, $page);

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function filterProductBySeller($merchant_id, $status, $limit, $filter = [], $sortby = null, $page = 1)
    {
        $product = new Product();
        $products = $product->withCount(['order_details' => function ($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->with(['product_stock', 'product_photo', 'is_wishlist', 'ev_subsidy'])
            ->where('merchant_id', $merchant_id)
            ->when(!empty($status), function ($query) use ($status) {
                switch ($status) {
                    case 'wait_approval':
                        $query->where('status', 0);
                        break;
                    case 'available':
                        $query->where('status', 1);
                        break;
                    case 'declined':
                        $query->where('status', 2);
                        break;
                    case 'blocked':
                        $query->where('status', 3);
                        break;
                    case 'archived':
                        $query->where('status', 9);
                        break;
                    default:
                        break;
                }
            });

        $products = $this->filter($products, $filter);
        $products = $this->sorting($products, $sortby);

        $immutable_data = $products->get()->map(function ($product) {
            $product->reviews = null;
            //            $product->avg_rating = ($product->reviews()->count() > 0) ? round($product->reviews()->avg('rate'), 1) : 0.0;
            // $product->avg_rating = 0.0;
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

        $category_ids = [];
        foreach ($cat_child as $cat) {
            foreach ($cat as $obj) {
                array_push($category_ids, $obj->id);
            }
        }

        $products = new Product();
        $products = $products->withCount(['order_details' => function ($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->with([
            'product_stock', 'product_photo', 'is_wishlist', 'merchant.city:id,name',
            'merchant.promo_merchant' => function ($pd) {
                $pd->where(function ($query) {
                    $query->where('start_date', '<=', date('Y-m-d H:i:s'))
                        ->where('end_date', '>=', date('Y-m-d H:i:s'));
                });
            },
            'merchant.promo_merchant.promo_master',
            'merchant.promo_merchant.promo_master.promo_values',
            'varian_product' => function ($query) {
                $query->with(['variant_stock'])->where('main_variant', true);
            },
            'ev_subsidy',
        ])
            ->whereHas('merchant', function ($merchant) {
                $merchant->where('status', 1);
            })
            ->whereIn('category_id', $category_ids)
            ->where('status', 1)
            ->orderBy('order_details_count', 'ASC');

        $filter_data = $this->filter($products, $filter);
        $sorted_data = $this->sorting($filter_data, $sortby);

        $data = $this->productPaginate($sorted_data, $limit);

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function getElectricVehicleByCategory($category_key, $sub_category_key, $sortby = null, $limit = 10)
    {
        $products = Product::where('status', 1)
            ->withCount([
                'order_details' => fn ($d) => $d->whereHas('order', fn ($o) => $o->whereHas('progress_done')),
            ])
            ->with([
                'product_stock',
                'product_photo',
                'category',
                'is_wishlist',
                'merchant',
                'merchant.city',
                'merchant.promo_merchant' => function ($pd) {
                    $pd->where(function ($query) {
                        $query->where('start_date', '<=', date('Y-m-d H:i:s'))
                            ->where('end_date', '>=', date('Y-m-d H:i:s'));
                    });
                },
                'merchant.promo_merchant.promo_master',
                'merchant.promo_merchant.promo_master.promo_values',
                'varian_product' => fn ($q) => $q->where('main_variant', true), 'varian_product.variant_stock',
                'ev_subsidy',
            ])
            ->whereHas('merchant.official_store', fn ($m) => $m->where([
                'category_key' => $category_key,
                'sub_category_key' => $sub_category_key,
            ]));

        $filtered_data = $this->filter($products);
        $sorted_data = $this->sorting($filtered_data, $sortby);

        $data = $this->productPaginate($sorted_data, $limit);

        if ($data->isEmpty()) {
            $response['success'] = false;
            $response['message'] = 'Produk belum tersedia saat ini!';
            return $response;
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan produk kendaraan listrik!';
        $response['data'] = $data;
        return $response;
    }

    public function getElectricVehicleWithCategoryById($category_key, $sub_category_key, $id)
    {
        $product = Product::where('status', 1)
            ->withCount([
                'order_details' => fn ($d) => $d->whereHas('order', fn ($o) => $o->whereHas('progress_done')),
            ])
            ->with([
                'product_stock',
                'product_photo',
                'category',
                'is_wishlist',
                'merchant',
                'merchant.city:id,name',
                'merchant.promo_merchant' => function ($pd) {
                    $pd->where(function ($query) {
                        $query->where('start_date', '<=', date('Y-m-d H:i:s'))
                            ->where('end_date', '>=', date('Y-m-d H:i:s'));
                    });
                },
                'merchant.promo_merchant.promo_master',
                'merchant.promo_merchant.promo_master.promo_values',
                'varian_product' => fn ($q) => $q->where('main_variant', true), 'varian_product.variant_stock',
                'ev_subsidy',
            ])
            ->whereHas('merchant.official_store', fn ($m) => $m->where([
                'category_key' => $category_key,
                'sub_category_key' => $sub_category_key,
            ]))
            ->where('id', $id)->first();

        if (!$product) {
            $response['success'] = false;
            $response['message'] = 'Produk tidak ditemukan!';
            $response['data'] = null;
            return $response;
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan produk kendaraan listrik!';
        $response['data'] = $product;
        return $response;
    }

    public function getElectricVehicleByCategoryMaster($category_key, $master_data_key, $sortby = null, $limit = 10)
    {
        $merchantEV = MasterEvStore::with('merchant')->where('category_key', $category_key)->get();
        $merchantEV = collect($merchantEV)->pluck('merchant')->pluck('id');

        $products = Product::where('status', 1)
            ->withCount([
                'order_details' => fn ($d) => $d->whereHas('order', fn ($o) => $o->whereHas('progress_done')),
            ])
            ->with([
                'product_stock', 'product_photo', 'merchant.city', 'category', 'is_wishlist',
                'varian_product' => fn ($q) => $q->where('main_variant', true), 'varian_product.variant_stock',
                'ev_subsidy',
            ])
            ->whereIn('merchant_id', $merchantEV)
            ->whereHas('category', fn ($c) => $c->where('key', $master_data_key));

        $filtered_data = $this->filter($products);
        $sorted_data = $this->sorting($filtered_data, $sortby);

        $data = $this->productPaginate($sorted_data, $limit);

        //if data empty
        if ($data->isEmpty()) {
            $response['success'] = false;
            $response['message'] = 'Produk belum tersedia saat ini!';
            return $response;
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan produk kendaraan listrik!';
        $response['data'] = $data;
        return $response;
    }

    public function getElectricVehicleWithCategoryMasterById($category_key, $master_data_key, $id)
    {
        $merchantEV = MasterEvStore::with('merchant')->where('category_key', $category_key)->get();
        $merchantEV = collect($merchantEV)->pluck('merchant')->pluck('id');

        $product = Product::withCount([
            'order_details' => fn ($d) => $d->whereHas('order', fn ($o) => $o->whereHas('progress_done')),
        ])
            ->with([
                'product_stock', 'product_photo', 'merchant.city', 'category', 'is_wishlist',
                'varian_product' => function ($query) {
                    $query->with(['variant_stock'])->where('main_variant', true);
                },
                'ev_subsidy',
            ])
            ->whereIn('merchant_id', $merchantEV)
            ->whereHas('category', fn ($c) => $c->where('key', $master_data_key))
            ->where([
                'id' => $id,
                'status' => 1,
            ])->first();

        if (!$product) {
            $response['success'] = false;
            $response['message'] = 'Produk tidak ditemukan!';
            $response['data'] = null;
            return $response;
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan produk kendaraan listrik!';
        $response['data'] = $product;
        return $response;
    }

    public function getOtherEvProductByCategory($category_id, $filter = [], $sortby = null, $limit = 10, $current_page = 1)
    {
        if (!$category_id) {
            $categories = MasterData::with([
                'child' => fn ($j) => $j->whereNotIn('key', ['prodcat_mobil_listrik', 'prodcat_motor_listrik', 'prodcat_sepeda_listrik']),
                'child.child' => fn ($q) => $q->whereNotIn('key', ['prodcat_mobil_listrik', 'prodcat_mobil_listrik_', 'prodcat_sepeda_listrik_']),
            ])->where([
                'type' => 'product_category',
                'key' => 'prodcat_electric_vehicle',
            ])->get();
        } else {
            $categories = MasterData::with([
                'child' => fn ($j) => $j->whereHas('child', fn ($q) => $q->where('id', $category_id)),
                'child.child' => fn ($q) => $q->where('id', $category_id),
            ])
                ->whereHas('child.child', fn ($q) => $q->where('id', $category_id))
                ->where([
                    'type' => 'product_category',
                    'key' => 'prodcat_electric_vehicle',
                ])->get();
        }

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
        }])->where('status', 1)->with([
            'product_stock', 'product_photo', 'is_wishlist',
            'merchant' => function ($merchant) {
                $merchant->with(['city:id,name', 'promo_merchant.promo_master']);
            },
            'varian_product' => function ($query) {
                $query->with(['variant_stock'])->where('main_variant', true);
            },
            'ev_subsidy',
        ])->whereHas('merchant', function ($merchant) {
            $merchant->where('status', 1);
        })->whereIn('category_id', $cat_child_id);

        $data = $this->productPaginate($products, $limit);

        if ($data->isEmpty()) {
            $response['success'] = false;
            $response['message'] = 'Produk tidak tersedia!';
            return $response;
        }

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
        }])->where('status', 1)
            ->with([
                'product_stock',
                'product_photo',
                'is_wishlist', 'merchant',
                'merchant.city:id,name',
                'merchant.promo_merchant' => function ($pd) {
                    $pd->where(function ($query) {
                        $query->where('start_date', '<=', date('Y-m-d H:i:s'))
                            ->where('end_date', '>=', date('Y-m-d H:i:s'));
                    });
                },
                'merchant.promo_merchant.promo_master',
                'merchant.promo_merchant.promo_master.promo_values',
                'ev_subsidy',
            ])
            ->whereHas('merchant', function ($merchant) {
                $merchant->where('status', 1);
            });

        $filtered_data = $this->filter($products, $filter);
        $sorted_data = $this->sorting($filtered_data, $sortby);

        $data = $this->productPaginate($sorted_data, $limit);

        //check if value min_price is greater than max_price
        if (isset($filter['min_price']) && isset($filter['max_price']) && $filter['min_price'] > $filter['max_price']) {
            $response['success'] = false;
            $response['message'] = 'Minimal harga tidak boleh lebih besar dari maksimal harga!';
            return $response;
        } else if (isset($filter['min_price']) && isset($filter['max_price']) && $filter['min_price'] == $filter['max_price']) {
            $response['success'] = false;
            $response['message'] = 'Minimal harga tidak boleh sama dengan maksimal harga!';
            return $response;
        }

        //check if filter product is empty
        if ($data->isEmpty()) {
            $response['success'] = false;
            $response['message'] = 'Produk tidak ditemukan!';
            $response['data'] = ['data' => []];
            return $response;
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function getproductMerchantEtalaseId($merchant_id, $etalase_id, $filter = '', $sortby = null, $limit)
    {
        $product = new Product();
        $products = $product
            ->withCount([
                'order_details' => function ($details) {
                    $details->whereHas('order', function ($order) {
                        $order->whereHas('progress_done');
                    });
                },
            ])
            ->with([
                'product_stock',
                'product_photo',
                'is_wishlist',
                'varian_product' => function ($query) {
                    $query->with(['variant_stock'])->where('main_variant', true);
                },
                'merchant.city:id,name',
                'merchant.promo_merchant' => function ($pd) {
                    $pd->where(function ($query) {
                        $query->where('start_date', '<=', date('Y-m-d H:i:s'))
                            ->where('end_date', '>=', date('Y-m-d H:i:s'));
                    });
                },
                'merchant.promo_merchant.promo_master',
                'merchant.promo_merchant.promo_master.promo_values',
                'ev_subsidy',
            ])
            ->where([
                'merchant_id' => $merchant_id,
                'etalase_id' => $etalase_id,
                'status' => 1,
            ])
            ->whereHas('merchant', function ($merchant) {
                $merchant->where('status', 1);
            });

        $filtered_data = static::filter($products, $filter);
        $sorted_data = static::sorting($filtered_data, $sortby);

        $data = static::productPaginate($sorted_data, $limit);

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function getProductEvSubsidy($limit, $filter = [], $sortby = null, $current_page = 1)
    {
        $categories = MasterData::with(['child', 'child.child'])->where([
            'type' => 'product_category',
            'key' => 'prodcat_electric_vehicle',
        ])->get();

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

        $merchant_product_ev = ProductEvSubsidy::where('merchant_id', auth()->user()->merchant_id)->get();

        $product = new Product();
        $products = $product->withCount(['order_details' => function ($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])->where([
            'status' => 1,
            'merchant_id' => auth()->user()->merchant_id,
        ])->with([
            'product_stock', 'product_photo', 'is_wishlist',
            'merchant.city:id,name',
            'merchant.promo_merchant' => function ($pd) {
                $pd->where(function ($query) {
                    $query->where('start_date', '<=', date('Y-m-d H:i:s'))
                        ->where('end_date', '>=', date('Y-m-d H:i:s'));
                });
            },
            'merchant.promo_merchant.promo_master',
            'merchant.promo_merchant.promo_master.promo_values',
            'varian_product' => function ($query) {
                $query->with(['variant_stock'])->where('main_variant', true);
            },
            'ev_subsidy',
        ])->whereHas('merchant', function ($merchant) {
            $merchant->where('status', 1);
        })->whereIn('category_id', $cat_child_id)
            ->whereNotIn('id', collect($merchant_product_ev)->pluck('product_id')->toArray());

        $products;
        $filtered_data = $this->filter($products, $filter);
        $sorted_data = $this->sorting($filtered_data, $sortby);

        $data = $this->productPaginate($sorted_data, $limit);

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data produk!';
        $response['data'] = $data;
        return $response;
    }

    public function getReviewByProduct($product_id, $limit = 10)
    {
        $review = Review::with(['review_photo', 'merchant', 'customer', 'product' => function ($product) {
            $product->with(['product_photo']);
        }, 'order' => function ($order) {
            $order->with(['detail']);
        }])->where('product_id', $product_id)->where('status', 1)->paginate($limit);

        $response['success'] = true;
        $response['message'] = 'Review berhasil didapatkan!';
        $response['data'] = $review;
        return $response;
    }

    public function countProductWithFilter($filter = [], $sortby = null)
    {
        $product = new Product();
        $products = $product->withCount(['order_details' => function ($details) {
            $details->whereHas('order', function ($order) {
                $order->whereHas('progress_done');
            });
        }])
            ->where('status', 1)
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

    public function checkProductStock($request)
    {
        $product = Product::with([
            'stock_active',
            'varian_value_product' => function ($variant) {
                $variant->with("variant_stock");
            },
        ])->whereIn("id", $request->product_id)->get();

        $response['success'] = true;
        $response['message'] = "Berhasil mendapatkan stok produk";
        $response['data'] = $product;

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
            $min_rating = $filter['min_rating'] ?? null;
            $max_rating = $filter['max_rating'] ?? null;
            $merchant_id = $filter['merchant_id'] ?? null;
            $status = $filter['status'] ?? null;

            $data = $model->when(!empty($keyword), function ($query) use ($keyword) {
                $query->where('name', 'ILIKE', "%{$keyword}%");
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
            })->when(!empty($min_rating), function ($query) use ($min_rating) {
                $query->where('avg_rating', '>=', $min_rating);
            })->when(!empty($max_rating), function ($query) use ($max_rating) {
                $query->where('avg_rating', '<=', $max_rating);
            })->when(!empty($merchant_id), function ($query) use ($merchant_id) {
                $query->where('merchant_id', $merchant_id);
            })->when(!empty($status), function ($query) use ($status) {
                switch ($status) {
                    case 'wait_approval':
                        $query->where('status', 0);
                        break;
                    case 'available':
                        $query->where('status', 1);
                        break;
                    case 'declined':
                        $query->where('status', 2);
                        break;
                    case 'blocked':
                        $query->where('status', 3);
                        break;
                    case 'archived':
                        $query->where('status', 9);
                        break;
                    default:
                        break;
                }
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
            })->when($sortby == 'lowest', function ($query) {
                $query->orderBy('created_at', 'asc');
            })->when($sortby == 'lower_price', function ($query) {
                $query->orderBy('price', 'asc');
            })->when($sortby == 'higher_price', function ($query) {
                $query->orderBy('price', 'desc');
            })->when($sortby == 'rating', function ($query) {
                $query->orderBy('avg_rating', 'desc');
            })->when($sortby == 'review', function ($query) {
                $query->orderBy('review_count', 'desc');
            })->when($sortby == 'sold', function ($query) {
                $query->orderBy('items_sold', 'desc');
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
                $is_shipping_discount = false;
                $is_flash_sale_discount = false;
                $promo_value = 0;
                $promo_type = '';

                $item = $item->toArray();

                if (isset($item['merchant']['promo_merchant']) && $item['merchant']['can_shipping_discount'] == true) {
                    foreach ($item['merchant']['promo_merchant'] as $promo) {
                        if (isset($promo['promo_master']['event_type']) && $promo['promo_master']['event_type'] == 'ongkir') {
                            if ($promo['promo_master']['value_2'] >= $promo['promo_master']['value_1']) {
                                $value_ongkir = $promo['promo_master']['value_2'];
                            } else {
                                $value_ongkir = $promo['promo_master']['value_1'];
                            }

                            $max_merchant = ($promo['usage_value'] + $value_ongkir) > $promo['max_value'];
                            $max_master = ($promo['promo_master']['usage_value'] + $value_ongkir) > $promo['promo_master']['max_value'];

                            if ($max_merchant && !$max_master) {
                                $is_shipping_discount = true;
                                break;
                            }

                            if (!$max_merchant && $max_master) {
                                $is_shipping_discount = true;
                                break;
                            }

                            if (!$max_merchant && !$max_master) {
                                $is_shipping_discount = true;
                                break;
                            }
                        }
                    }
                }

                if (isset($item['merchant']['promo_merchant']) && $item['merchant']['can_flash_sale_discount'] == true) {
                    foreach ($item['merchant']['promo_merchant'] as $promo) {
                        if (isset($promo['promo_master']['event_type']) && $promo['promo_master']['event_type'] == 'flash_sale') {
                            $value_flash_sale_m = 0;

                            $value_flash_sale_m = $promo['promo_master']['value_1'];
                            if ($promo['promo_master']['promo_value_type'] == 'percentage') {
                                $value_flash_sale_m = $item['price'] * ($promo['promo_master']['value_1'] / 100);
                                if ($value_flash_sale_m >= $promo['promo_master']['max_discount_value']) {
                                    $value_flash_sale_m = $promo['promo_master']['max_discount_value'];
                                }
                            }

                            foreach ($promo['promo_master']['promo_values'] as $promo_value) {
                                $value_flash_sale_m = $promo['promo_master']['value_1'];
                                if ($promo['promo_master']['promo_value_type'] == 'percentage') {
                                    $value_flash_sale_m = $item['price'] * ($promo['promo_master']['value_1'] / 100);
                                    if ($value_flash_sale_m >= $promo['promo_master']['max_discount_value']) {
                                        $value_flash_sale_m = $promo['promo_master']['max_discount_value'];
                                    }
                                }

                                if ($item['price'] >= $promo_value['min_value'] && $item['price'] <= $promo_value['max_value'] && $promo_value['status'] == 1) {
                                    if ($value_flash_sale_m >= $promo_value['max_discount_value']) {
                                        $value_flash_sale_m = $promo_value['max_discount_value'];
                                    }

                                    break;
                                }
                            }

                            $max_merchant = ($promo['usage_value'] + $value_flash_sale_m) > $promo['max_value'];
                            $max_master = ($promo['promo_master']['usage_value'] + $value_flash_sale_m) > $promo['promo_master']['max_value'];

                            if ($max_merchant && !$max_master) {
                                $is_flash_sale_discount = true;
                                $promo_value = $promo['promo_master']['value_1'];
                                $promo_type = $promo['promo_master']['promo_value_type'];
                                break;
                            }

                            if (!$max_merchant && $max_master) {
                                $is_flash_sale_discount = true;
                                $promo_value = $promo['promo_master']['value_1'];
                                $promo_type = $promo['promo_master']['promo_value_type'];
                                break;
                            }

                            if (!$max_merchant && !$max_master) {
                                $is_flash_sale_discount = true;
                                $promo_value = $promo['promo_master']['value_1'];
                                $promo_type = $promo['promo_master']['promo_value_type'];
                                break;
                            }
                        }
                    }
                }

                unset($item['merchant']['promo_merchant']);
                $item['merchant']['is_shipping_discount'] = $is_shipping_discount;
                $item['is_flash_sale_discount'] = $is_flash_sale_discount;
                $item['promo_value'] = $promo_value;
                $item['promo_type'] = $promo_type;
                $item['reviews'] = null;
                return $item;
            })->toArray();

        $itemsTransformedAndPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $itemsTransformed,
            $itemsPaginated->total(),
            $itemsPaginated->perPage(),
            $itemsPaginated->currentPage(),
            [
                // 'path' => \Illuminate\Http\Request::url(),
                'query' => [
                    'page' => $itemsPaginated->currentPage(),
                ],
            ]
        );

        return $itemsTransformedAndPaginated;
    }
}

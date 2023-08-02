<?php

namespace App\Http\Services\Wishlist;

use App\Http\Services\Service;
use App\Models\Wishlist;

class WishlistQueries extends Service
{
    public function findWishlistExist($customer_id, $merchant_id, $product_id)
    {
        $wishlist = Wishlist::where('customer_id', $customer_id)->where('product_id', $product_id)->where('merchant_id', $merchant_id)->first();
        return $wishlist;
    }

    public function getListWishlistByCustomer($customer_id, $limit = 10, $page = 1, $sortby = null)
    {
        $wishlist = Wishlist::with(['customer', 'merchant' => function ($merchant) {
            $merchant->with([
                'province',
                'city',
                'district',
                'expedition',
                'promo_merchant' => function ($pd) {
                    $pd->where(function ($query) {
                        $query->where('start_date', '<=', date('Y-m-d H:i:s'))
                            ->where('end_date', '>=', date('Y-m-d H:i:s'));
                    });
                },
                'promo_merchant.promo_master',
                'promo_merchant.promo_master.promo_values',
            ]);
            $merchant->with('orders', function ($orders) {
                $orders->whereHas('progress_active', function ($progress) {
                    $progress->whereIn('status_code', ['01', '02']);
                });
            });
        }, 'product' => function ($product) {
            $product->withCount(['order_details' => function ($details) {
                $details->whereHas('order', function ($order) {
                    $order->whereHas('progress_done');
                });
            }])->with(['product_stock', 'product_photo', 'ev_subsidy']);
        }])->where('customer_id', $customer_id)->where('is_valid', true);

        $collect_data = collect($wishlist->get());

        if ($sortby == 'newest') {
            $collect_data = $collect_data->sortByDesc('created_at');
        } else if ($sortby == 'oldest') {
            $collect_data = $collect_data->sortBy('created_at', SORT_NATURAL | SORT_FLAG_CASE);
        } else if ($sortby == 'review') {
            $collect_data = $collect_data->sortByDesc('review_count');
        } else if ($sortby == 'sold') {
            $collect_data = $collect_data->sortByDesc('items_sold');
        }

        $data = $collect_data->map(function ($item) {
            $item['merchant']['order_count'] = count($item['merchant']['orders']);
            $item['product']['strike_price'] = $item['product']['strike_price'] == 0 ? null : $item['product']['strike_price'];
            unset($item['merchant']['orders']);

            return $item;
        })->toArray();

        return [
            'success' => true,
            'message' => 'Berhasil mendapatkan data wishlist!',
            'data' => static::wishlistPaginate($data, $limit, $page),
        ];
    }

    public function searchListWishlistByName($customer_id, $limit = 10, $page = 1, $sortby = null, $keyword)
    {
        $wishlist = Wishlist::with(['customer', 'merchant' => function ($merchant) {
            $merchant->with(['province', 'city', 'district', 'expedition']);
            $merchant->with('orders', function ($orders) {
                $orders->whereHas('progress_active', function ($progress) {
                    $progress->whereIn('status_code', ['01', '02']);
                });
            });
        }, 'product' => function ($product) use ($keyword) {
            $product->withCount(['order_details' => function ($details) {
                $details->whereHas('order', function ($order) {
                    $order->whereHas('progress_done');
                });
            }])->with(['product_stock', 'product_photo', 'ev_subsidy']);
        }])->whereHas('merchant', function ($merchant) {
            $merchant->where('status', 1);
        })->where('customer_id', $customer_id)->where('is_valid', true);

        if (!empty($keyword)) {
            $keywords = explode(' ', $keyword);
            foreach ($keywords as $key) {
                $wishlist->whereHas('product', function ($product) use ($key) {
                    $product->where('name', 'ilike', '%' . $key . '%');
                });
            }
        }

        $collect_data = collect($wishlist->get());

        if ($sortby == 'newest') {
            $collect_data = $collect_data->sortByDesc('created_at');
        } else if ($sortby == 'oldest') {
            $collect_data = $collect_data->sortBy('created_at', SORT_NATURAL | SORT_FLAG_CASE);
        } else if ($sortby == 'review') {
            $collect_data = $collect_data->sortByDesc('review_count');
        } else if ($sortby == 'sold') {
            $collect_data = $collect_data->sortByDesc('items_sold');
        }

        $data = $collect_data->map(function ($item) {
            $item['merchant']['order_count'] = count($item['merchant']['orders']);
            $item['product']['strike_price'] = $item['product']['strike_price'] == 0 ? null : $item['product']['strike_price'];
            unset($item['merchant']['orders']);

            return $item;
        })->toArray();

        return [
            'success' => true,
            'message' => 'Berhasil mendapatkan data wishlist!',
            'data' => static::wishlistPaginate($data, $limit, $page),
        ];
    }

    private function wishlistPaginate($wishlist, $limit = 10, $page = 1)
    {
        $itemsPaginated = static::paginate($wishlist, $limit, $page);

        $itemsTransformed = array_map(function ($item) {
            $is_shipping_discount = false;
            $is_flash_sale_discount = false;
            $promo_value = 0;
            $promo_type = '';

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
                            $value_flash_sale_m = $item['product']['price'] * ($promo['promo_master']['value_1'] / 100);
                            if ($value_flash_sale_m >= $promo['promo_master']['max_discount_value']) {
                                $value_flash_sale_m = $promo['promo_master']['max_discount_value'];
                            }
                        }

                        foreach ($promo['promo_master']['promo_values'] as $promo_value) {
                            $value_flash_sale_m = $promo['promo_master']['value_1'];
                            if ($promo['promo_master']['promo_value_type'] == 'percentage') {
                                $value_flash_sale_m = $item['product']['price'] * ($promo['promo_master']['value_1'] / 100);
                                if ($value_flash_sale_m >= $promo['promo_master']['max_discount_value']) {
                                    $value_flash_sale_m = $promo['promo_master']['max_discount_value'];
                                }
                            }

                            if ($item['product']['price'] >= $promo_value['min_value'] && $item['product']['price'] <= $promo_value['max_value'] && $promo_value['status'] == 1) {
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
            $item['product']['is_flash_sale_discount'] = $is_flash_sale_discount;
            $item['product']['promo_value'] = $promo_value;
            $item['product']['promo_type'] = $promo_type;

            $item['product']['reviews'] = null;
            $item['product']['review_count'] = $item['product']['review_count'];
            $item['product']['items_sold'] = $item['product']['items_sold'];
            return $item;
        }, collect($itemsPaginated['data'])->toArray());

        $itemsPaginated['data'] = $itemsTransformed;
        return $itemsPaginated;
    }
}

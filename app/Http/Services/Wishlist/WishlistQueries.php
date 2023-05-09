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
        }, 'product' => function ($product) {
            $product->withCount(['order_details' => function ($details) {
                $details->whereHas('order', function ($order) {
                    $order->whereHas('progress_done');
                });
            }])->with(['product_stock', 'product_photo']);
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

        return [
            'success' => true,
            'message' => 'Berhasil mendapatkan data wishlist!',
            'data' => static::wishlistPaginate($collect_data->toArray(), $limit, $page)
        ];
    }

    public function searchListWishlistByName($data, $limit = 10, $page = 1, $sortby = null)
    {
        $keyword = $data['keyword'];
        $wishlist = Wishlist::with(['customer', 'merchant' => function ($merchant) {
            $merchant->with(['province', 'city', 'district', 'expedition']);
        }, 'product' => function ($product) use ($keyword) {
            $product->withCount(['order_details' => function ($details) {
                $details->whereHas('order', function ($order) {
                    $order->whereHas('progress_done');
                });
            }])->with(['product_stock', 'product_photo']);
        }])->whereHas('product', function ($query) use ($keyword) {
            $query->where('name', 'ILIKE', '%' . $keyword . '%')->orWhereHas('merchant', function ($query) use ($keyword) {
                $query->where('name', 'ILIKE', '%' . $keyword . '%');
            });
        })->where('customer_id', $data['customer_id'])->where('is_valid', true);

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

        return [
            'success' => true,
            'message' => 'Berhasil mendapatkan data wishlist!',
            'data' => static::wishlistPaginate($collect_data->toArray(), $limit, $page)
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

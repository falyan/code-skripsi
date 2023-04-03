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
            $merchant->with(['province', 'city', 'district', 'expedition']);
        }, 'product' => function ($product) {
            $product->withCount(['order_details' => function ($details) {
                $details->whereHas('order', function ($order) {
                    $order->whereHas('progress_done');
                });
            }])->with(['product_stock', 'product_photo']);
        }])->where('customer_id', $customer_id)->where('is_valid', true);

        $immutable_data = $wishlist->get()->map(function ($wl) {
            $wl->review_count = $wl->product->review_count;
            $wl->items_sold = $wl->product->items_sold;
            return $wl;
        });

        $collect_data = collect($immutable_data);

        if ($sortby == 'newest') {
            $collect_data = $collect_data->sortByDesc('created_at');
        } else if ($sortby == 'oldest') {
            $collect_data = $collect_data->sortBy('created_at', SORT_NATURAL | SORT_FLAG_CASE);
        } else if ($sortby == 'review') {
            $collect_data = $collect_data->sortByDesc('review_count');
        } else if ($sortby == 'sold') {
            $collect_data = $collect_data->sortByDesc('items_sold');
        }

        $data = static::paginate($collect_data->toArray(), $limit, $page);

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data wishlist!';
        $response['data'] = $data;
        return $response;
    }

    public function searchListWishlistByName($data, $limit = 10, $page = 1)
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
        })->where('customer_id', $data['customer_id'])->where('is_valid', true)->get();

        $data = static::paginate($wishlist->toArray(), $limit, $page);

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data wishlist!';
        $response['data'] = $data;
        return $response;
    }
}

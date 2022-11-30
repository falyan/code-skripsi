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

    public function getListWishlistByCustomer($customer_id, $limit = 10, $page = 1)
    {
        $wishlist = Wishlist::with(['customer', 'merchant' => function ($merchant) {
            $merchant->with(['province', 'city', 'district', 'expedition']);
        }, 'product' => function ($product) {
            $product->withCount(['order_details' => function ($details) {
                $details->whereHas('order', function ($order) {
                    $order->whereHas('progress_done');
                });
            }])->with(['product_stock', 'product_photo']);
        }])->where('customer_id', $customer_id)->where('is_valid', true)->get();

        // $wishlists = $wishlist->get()->map(function ($wl) {
        //     $wl->avg_rating = ($wl->product->reviews()->count() > 0) ? round($wl->product->reviews()->avg('rate'), 1) : 0.0;
        //     return $wl;
        // });

        $data = static::paginate($wishlist->toArray(), $limit, $page);

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

        // $wishlists = $wishlist->get()->map(function ($wl) {
        //     $wl->avg_rating = ($wl->product->reviews()->count() > 0) ? round($wl->product->reviews()->avg('rate'), 1) : 0.0;
        //     return $wl;
        // });

        $data = static::paginate($wishlist->toArray(), $limit, $page);

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data wishlist!';
        $response['data'] = $data;
        return $response;
    }
}

<?php

namespace App\Http\Services\Wishlist;

use App\Models\Wishlist;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class WishlistQueries{
    public function findWishlistExist($customer_id, $merchant_id, $product_id){
        $wishlist = Wishlist::where('customer_id', $customer_id)->where('product_id', $product_id)->where('merchant_id', $merchant_id)->first();
        return $wishlist;
    }

    public function getListWishlistByCustomer($customer_id)
    {
        $wishlist = Wishlist::with(['customer', 'merchant' => function ($merchant) {
            $merchant->with(['province', 'city', 'district', 'expedition']);
        }, 'product' => function ($product) {
            $product->withCount(['order_details' => function ($details) {
                $details->whereHas('order', function ($order) {
                    $order->whereHas('progress_done');
                });
            }])->with(['product_stock', 'product_photo']);
        }])->where('customer_id', $customer_id)->where('is_valid', true)->paginate(10);

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data wishlist!';
        $response['data'] = $wishlist;
        return $response;
    }
}

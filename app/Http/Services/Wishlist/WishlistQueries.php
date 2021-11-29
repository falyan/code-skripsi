<?php

namespace App\Http\Services\Wishlist;

use App\Models\Wishlist;

class WishlistQueries{
    public function findWishlistExist($customer_id, $merchant_id, $product_id){
        $wishlist = Wishlist::where('customer_id', $customer_id)->where('product_id', $product_id)->where('merchant_id', $merchant_id)->first();
        return $wishlist;
    }
}

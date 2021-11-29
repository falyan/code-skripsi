<?php

namespace App\Http\Services\Wishlist;

use App\Models\Wishlist;

class WishlistCommands{
    public function addOrRemoveWishlist($data){
        $wishlistQuery = new WishlistQueries();
        $wishlist_exist = $wishlistQuery->findWishlistExist($data['customer_id'], $data['merchant_id'], $data['product_id']);

        if ($wishlist_exist != null){
            if (!$wishlist_exist->delete()){
                 $response['success'] = false;
                 $response['message'] = 'Gagal menghapus produk dari wishlist!';
                 $response['data'] = $wishlist_exist;
                 return $response;
            }
            $response['success'] = true;
            $response['message'] = 'Berhasil menghapus produk dari wishlist!';
            return $response;
        }

        $wishlist = new Wishlist();
        $wishlist->customer_id = $data['customer_id'];
        $wishlist->merchant_id = $data['merchant_id'];
        $wishlist->product_id = $data['product_id'];
        $wishlist->is_valid = true;

        if (!$wishlist->save()){
            $response['success'] = false;
            $response['message'] = 'Gagal menyimpan produk ke wishlist!';
            $response['data'] = $wishlist;
            return $response;
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil menyimpan produk ke wishlist!';
        $response['data'] = $wishlist;
        return $response;
    }
}

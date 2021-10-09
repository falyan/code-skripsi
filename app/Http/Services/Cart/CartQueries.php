<?php

namespace App\Http\Services\Cart;

use App\Http\Resources\Etalase\EtalaseCollection;
use App\Http\Resources\Etalase\EtalaseResource;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\Etalase;
use Exception;
use Illuminate\Support\Facades\Auth;

class CartQueries{
    public static function getTotalCart($related_customer_id, $buyer_id = null){
        $cart = Cart::findByRelatedId($buyer_id, $related_customer_id);

        return [
            'product' => count($cart->cart_detail->toArray()),
            'total_item' => array_sum($cart->cart_detail->pluck('quantity')->toArray())
        ];
    }

    public static function getDetailCart($buyer_id = null, $related_id){
        if ($buyer_id != null){
            $cart = Cart::with(['cart_detail' => function($product)
            {$product->with(['product' => function($product_detail)
            {$product_detail->with(['product_stock', 'product_photo', 'merchant']);}]);}])
                ->where('buyer_id', $buyer_id)->get();

//            if ($cart->isEmpty()){
//                $response['success'] = false;
//                $response['message'] = 'Gagal mendapatkan data keranjang.';
//                return $response;
//            }

            $response['success'] = true;
            $response['message'] = 'Berhasil mendapatkan data keranjang.';
            $response['data'] = $cart;
            return $response;
        }else{
            $cart = Cart::with(['cart_detail' => function($product)
            {$product->with(['product' => function($product_detail)
            {$product_detail->with(['product_stock', 'product_photo', 'merchant']);}]);}])
                ->where('related_pln_mobile_customer_id', $related_id)->get();

//            if ($cart->isEmpty()){
//                $response['success'] = false;
//                $response['message'] = 'Gagal mendapatkan data keranjang.';
//                return $response;
//            }

            $response['success'] = true;
            $response['message'] = 'Berhasil mendapatkan data keranjang.';
            $response['data'] = $cart;
            return $response;
        }
    }
}

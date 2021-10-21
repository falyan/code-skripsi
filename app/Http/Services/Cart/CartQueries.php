<?php

namespace App\Http\Services\Cart;

use App\Http\Resources\Cart\DetailCartResource;
use App\Http\Resources\Etalase\EtalaseCollection;
use App\Http\Resources\Etalase\EtalaseResource;
use App\Http\Services\Service;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\Etalase;
use App\Models\Merchant;
use App\Models\Product;
use Exception;
use Illuminate\Support\Facades\Auth;

class CartQueries extends Service
{
    public static function getTotalCart($related_customer_id, $buyer_id = null){
        $carts = Cart::findByRelatedId($buyer_id, $related_customer_id);
        // dd($carts[4]->cart_detail);
        $per_quantities = [];
        foreach ($carts as $cart) {
            array_push($per_quantities, $cart->cart_detail->pluck('quantity')->first());
        }

        $validated_quantities = array_filter($per_quantities, function($qty) {
            return isset($qty);
        });

        return [
            'product' => count($validated_quantities),
            'total_item' => array_sum($validated_quantities)
        ];
    }

    public static function getDetailCart($buyer_id = null, $related_id){
        if ($buyer_id != null){
            $cart = Cart::with(['merchants' => function ($merchant) {
                $merchant->with(['expedition', 'city']);
            }, 'cart_detail' => function($cart_detail) {
                $cart_detail->with(['product' => function($product) {
                    $product->with(['product_stock', 'product_photo']);
                }]);
            }])->where('buyer_id', $buyer_id)->get();

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

            $cart = Cart::with(['merchants' => function ($merchant) {
                $merchant->with('expedition', 'city');
            }, 'cart_detail' => function($cart_detail) {
                $cart_detail->with(['product' => function($product) {
                    $product->with(['product_stock', 'product_photo']);
                }]);
            }])->where('related_pln_mobile_customer_id', $related_id)->get();

            $response['success'] = true;
            $response['message'] = 'Berhasil mendapatkan data keranjang.';
            $response['data'] = $cart;

            return $response;
        }
    }
}

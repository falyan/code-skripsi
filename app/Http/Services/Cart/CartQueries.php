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
    public static function getTotalCart($buyer_id = null){
        $carts = Cart::findByRelatedId($buyer_id);

        $per_quantities = [];
        foreach ($carts as $cart) {
            $products = $cart->cart_detail->pluck('quantity')->toArray();

            foreach ($products as $product) {
                array_push($per_quantities, $product);
            }
        };

        return [
            'product' => count($per_quantities),
            'total_item' => array_sum($per_quantities)
        ];
    }

    public static function getDetailCart($buyer_id = null, $related_id)
    {
        $cart = Cart::with(['merchants' => function ($merchant) {
            $merchant->with(['expedition', 'city']);
            $merchant->with('orders', function ($orders) {
                $orders->whereHas('progress_active', function ($progress) {
                    $progress->whereIn('status_code', ['01', '02']);
                });
            });
        }, 'cart_detail' => function ($cart_detail) {
            $cart_detail->with(['product' => function ($product) {
                $product->with(['product_stock', 'product_photo']);
            }, 'variant_value_product']);
        }])
            ->whereHas('merchants', function ($merchant) {
                $merchant->where('status', 1);
            })
            ->when($buyer_id != null, function ($query) use ($buyer_id) {
                $query->where('buyer_id', $buyer_id);
            })
            ->when($buyer_id == null, function ($query) use ($related_id) {
                $query->where('related_pln_mobile_customer_id', $related_id);
            })
            ->get();

        if (count($cart) > 0) {
            $cart = array_map(function ($item) {
                $item['merchants']['order_count'] = count($item['merchants']['orders']);
                unset($item['merchants']['orders']);

                return $item;
            }, $cart->toArray());
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data keranjang.';
        $response['data'] = $cart;
        return $response;
    }
}

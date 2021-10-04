<?php

namespace App\Http\Services\Cart;

use App\Http\Resources\Etalase\EtalaseCollection;
use App\Http\Resources\Etalase\EtalaseResource;
use App\Models\Customer;
use App\Models\Etalase;
use Illuminate\Support\Facades\Auth;

class CartQueries{
    public static function getTotalCart(){
        $customer = Customer::find(Auth::user()->id);

        return [
            'product' => count($customer->cart->cart_detail->toArray()),
            'total_item' => array_sum($customer->cart->cart_detail->pluck('quantity')->toArray())
        ];
    }
}

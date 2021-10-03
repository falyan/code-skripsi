<?php

namespace App\Http\Services\Cart;

use App\Http\Resources\Etalase\EtalaseCollection;
use App\Http\Resources\Etalase\EtalaseResource;
use App\Models\Etalase;
use Illuminate\Support\Facades\Auth;

class CartQueries{
    public static function getTotalCart(){
        dd(Auth::user()->cart);
    }
}

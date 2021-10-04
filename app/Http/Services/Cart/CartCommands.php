<?php

namespace App\Http\Services\Cart;

use App\Models\Cart;
use App\Models\CartDetail;
use App\Models\Customer;
use App\Models\Etalase;
use App\Models\Merchant;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CartCommands
{
    static $error_codes = [400, 401, 402, 403, 404, 405, 406, 407, 408, 409, 410, 411, 412, 413, 414, 415, 416, 417, 418, 421, 422, 423, 424, 425, 426, 428, 429, 431, 451, 500, 501, 502, 503, 504, 505, 506, 507, 508, 510, 511];

    public static function aturToko($request, $merchant_id)
    {
    }

    public static function addCart()
    {
        $getUser = Customer::find(Auth::user()->id);

        DB::beginTransaction();

        try {
            $cart = Cart::where('buyer_id', $getUser->id)->first();

            if ($cart) {
                $cartDetail = CartDetail::create([
                    'cart_id' => $cart->id,
                    'product_id' => request('product_id'),
                    'quantity' => 1
                ]);
            } else {
                $cart = Cart::create([
                    'buyer_id' => $getUser->id
                ]);

                $cartDetail = CartDetail::create([
                    'cart_id' => $cart->id,
                    'product_id' => request('product_id'),
                    'quantity' => 1
                ]);
            }

            $data = [
                'cart' => $cart,
                'cart_detail' => $cartDetail
            ];

            DB::commit();
        } catch (Exception $th) {
            DB::rollBack();
            throw new Exception($th->getMessage(), $th->getCode());
        }

        return $data;
    }

    public static function deleteProduct($id)
    {
        try {
            $cardDetail = CartDetail::find($id);
            $cardDetail->delete();
        } catch (Exception $th) {
            throw new Exception($th->getMessage(), $th->getCode());
        }

        return null;
    }

    public static function QuantityUpdate($id)
    {
        try {
            $data = CartDetail::find($id)->update([
                'quantity' => request('quantity')
            ]);
        } catch (Exception $th) {
            throw new Exception($th->getMessage(), $th->getCode());
        }

        return $data;
    }
};

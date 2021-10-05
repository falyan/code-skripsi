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
        $getRelationMobile = request('related_pln_mobile_customer_id');
        $getProductId = request('product_id');
        $buyerID = request('buyer_id') ? request('buyer_id') : null;

        DB::beginTransaction();

        try {
            $cart = Cart::where('related_pln_mobile_customer_id', $getRelationMobile)->first();

            if ($cart) {
                $productExists = $cart->cart_detail->where('product_id', $getProductId)
                    ->first();

                if ($productExists) {
                    $cartDetail = $productExists->update([
                        'quantity' => $productExists->quantity + 1
                    ]);
                } else {
                    $cartDetail = CartDetail::create([
                        'cart_id' => $cart->id,
                        'product_id' => request('product_id'),
                        'quantity' => 1
                    ]);
                }
            } else {
                $cartCreate = Cart::create([
                    'related_pln_mobile_customer_id' => request('related_pln_mobile_customer_id'),
                    'buyer_id' => $buyerID
                ]);

                $cartDetail = CartDetail::create([
                    'cart_id' => $cartCreate->id,
                    'product_id' => request('product_id'),
                    'quantity' => 1
                ]);
            }

            DB::commit();
        } catch (Exception $th) {
            DB::rollBack();
            throw new Exception($th->getMessage(), $th->getCode());
        }

        return 'data saved';
    }

    public static function deleteProduct($key)
    {
        try {
            $cart = Cart::where('related_pln_mobile_customer_id', $key)->first();
            $existsData = $cart->cart_detail->where('product_id', request('product_id'))->first();

            if ($existsData) {
                $existsData->delete();
                return response()->json([
                    'success' => true,
                    'message' => 'produk berhasil dihapus',
                ], 404);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ID tidak ditemukan!',
                ], 404);
            }
        } catch (Exception $th) {
            throw new Exception($th->getMessage(), $th->getCode());
        }
    }

    public static function QuantityUpdate($key)
    {
        try {
            $cart = Cart::where('related_pln_mobile_customer_id', $key)->first();
            $existsData = $cart->cart_detail->where('product_id', request('product_id'))->first();

            if ($existsData) {
                $existsData->update([
                    'quantity' => request('quantity')
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'produk berhasil dihapus',
                ], 404);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ID tidak ditemukan!',
                ], 404);
            }
        } catch (Exception $th) {
            throw new Exception($th->getMessage(), $th->getCode());
        }

        return null;
    }
};

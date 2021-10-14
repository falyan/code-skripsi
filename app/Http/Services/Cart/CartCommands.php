<?php

namespace App\Http\Services\Cart;

use App\Http\Services\Service;
use App\Models\Cart;
use App\Models\CartDetail;
use App\Models\Customer;
use App\Models\Etalase;
use App\Models\Merchant;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CartCommands extends Service
{
    public static function addCart()
    {
        $getRelationMobile = request('related_pln_mobile_customer_id');
        $getProductId = request('product_id');
        $buyerID = request('buyer_id') ? request('buyer_id') : null;
        $related_merchant_id = request('related_merchant_id') ? request('related_merchant_id') : null;
        // $getUser = Customer::findByrelatedCustomerId($related_customer_id);

        DB::beginTransaction();

        try {
            $cart = Cart::where([['related_pln_mobile_customer_id', $getRelationMobile], ['merchant_id', $related_merchant_id]])->first();

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
                        'quantity' => 1,
                        'related_merchant_id' => $related_merchant_id
                    ]);
                }
            } else {
                $cartCreate = Cart::create([
                    'related_pln_mobile_customer_id' => request('related_pln_mobile_customer_id'),
                    'buyer_id' => $buyerID,
                    'merchant_id' => $related_merchant_id
                ]);

                $cartDetail = CartDetail::create([
                    'cart_id' => $cartCreate->id,
                    'product_id' => request('product_id'),
                    'quantity' => 1,
                    'related_merchant_id' => $related_merchant_id
                ]);
            }

            DB::commit();
        } catch (Exception $th) {
            DB::rollBack();
            throw new Exception($th->getMessage(), $th->getCode());
        }

        return 'data saved';
    }

    public static function deleteProduct($cart_detail_id, $cart_id)
    {
        try {
            $data = CartDetail::where([['id', $cart_detail_id], ['cart_id', $cart_id]])->firstOrFail();

            if ($data) {
                $data->delete();
                return response()->json([
                    'success' => true,
                    'message' => 'produk berhasil dihapus',
                ], 200);
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

    public static function QuantityUpdate($cart_detail_id, $cart_id)
    {
        try {
            $data = CartDetail::where([['id', $cart_detail_id], ['cart_id', $cart_id]])->firstOrFail();

            if ($data) {
                $data->update([
                    'quantity' => request('quantity')
                ]);

                if ($data->quantity < 1){
                    $data->delete();

                    return response()->json([
                        'success' => true,
                        'message' => 'Produk berhasil dihapus',
                    ], 200);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Produk berhasil diubah',
                ], 200);
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

    public function deleteAllCart($related_id, $buyer_id = null){
        if ($buyer_id != null){
            $carts = Cart::where('buyer_id', $buyer_id)->get();
            foreach ($carts as $cart){
                if ($cart->delete() < 1){
                    $response['success'] = false;
                    $response['message'] = 'Gagal menghapus keranjang';
                    return $response;
                }
            }
            $response['success'] = true;
            $response['message'] = 'Berhasil menghapus keranjang';
            return $response;
        }
        $carts = Cart::where('related_pln_mobile_customer_id', $related_id)->get();
        foreach ($carts as $cart){
            if ($cart->delete() < 1){
                $response['success'] = false;
                $response['message'] = 'Gagal menghapus keranjang';
                return $response;
            }
        }
        $response['success'] = true;
        $response['message'] = 'Berhasil menghapus keranjang';
        return $response;
    }
}

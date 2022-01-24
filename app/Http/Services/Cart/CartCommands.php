<?php

namespace App\Http\Services\Cart;

use App\Http\Services\Service;
use App\Models\Cart;
use App\Models\CartDetail;
use App\Models\Customer;
use App\Models\Etalase;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\VariantStock;
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
        $buyerID = Auth::id() ?? null;
        $related_merchant_id = request('related_merchant_id') ? request('related_merchant_id') : null;
        $variant_id = request('variant_value_product_id') ? request('variant_value_product_id') : null;
        // $getUser = Customer::findByrelatedCustomerId($related_customer_id);

        DB::beginTransaction();

        try {
            $cart = Cart::when(!empty($buyerID), function ($q) use ($buyerID) {
                $q->where('buyer_id', $buyerID);
            })->when(empty($buyerID), function ($q) use ($getRelationMobile) {
                $q->where('related_pln_mobile_customer_id', $getRelationMobile);
            })->where('merchant_id', $related_merchant_id)->first();

            $product_stock = ProductStock::where([['product_id', $getProductId], ['status', 1]])->first();
            $minimum_purchase = Product::find($getProductId)->minimum_purchase ?? 1;

            if (empty($product_stock->amount) || (!empty($product_stock->amount) && $product_stock->amount <= 0)) {
                throw new Exception('Stok produk belum tersedia', 400);
            }

            if ($variant_id != null){
                $variant_stock = VariantStock::where([['variant_value_product_id', $variant_id], ['status', 1]])->first();

                if (empty($variant_stock->amount) || (!empty($variant_stock->amount) && $variant_stock->amount <= 0)) {
                    throw new Exception('Stok variant produk belum tersedia', 400);
                }
            }

            if ($cart) {
                if ($variant_id != null || 0){
                    $productExists = $cart->cart_detail->where('product_id', $getProductId)->where('variant_value_product_id', $variant_id)
                        ->first();
                }else{
                    $productExists = $cart->cart_detail->where('product_id', $getProductId)
                        ->first();
                }

                if ($productExists) {
                    if ($productExists->quantity + 1 > $product_stock->amount) {
                        throw new Exception("Stok produk habis. {$product_stock->amount} stok yang tersedia sudah kamu masukkan ke keranjangmu.");
                    }
                    if ($variant_id != null || 0){
                        $cartDetail = $productExists->update([
                            'quantity' => $productExists->quantity + 1,
                            'variant_value_product_id' => $variant_id
                        ]);
                    }else{
                        $cartDetail = $productExists->update([
                            'quantity' => $productExists->quantity + 1
                        ]);
                    }
                } else {
                    $cartDetail = CartDetail::create([
                        'cart_id' => $cart->id,
                        'product_id' => $getProductId,
                        'quantity' => $minimum_purchase,
                        'related_merchant_id' => $related_merchant_id,
                        'variant_value_product_id' => $variant_id
                    ]);
                }
            } else {
                $cartCreate = Cart::create([
                    'related_pln_mobile_customer_id' => $getRelationMobile,
                    'buyer_id' => $buyerID,
                    'merchant_id' => $related_merchant_id
                ]);

                $cartDetail = CartDetail::create([
                    'cart_id' => $cartCreate->id,
                    'product_id' => request('product_id'),
                    'quantity' => $minimum_purchase,
                    'related_merchant_id' => $related_merchant_id,
                    'variant_value_product_id' => $variant_id
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
            $data = CartDetail::find($cart_detail_id);

            if ($data) {
                $data->delete();

                $cart = Cart::withCount('cart_detail')->find($cart_id);
                if ($cart->cart_detail_count <= 0) {
                    $cart->delete();
                }

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
        $data = CartDetail::where([['id', $cart_detail_id], ['cart_id', $cart_id]])->first();

        if ($data) {
            $product_stock = ProductStock::where([['product_id', $data->product_id], ['status', 1]])->first();
            $quantity = (int)$data->quantity + (int)request('quantity');
            $minimum_purchase = Product::find($data->product_id)->minimum_purchase ?? 1;

            if (empty($product_stock->amount) || (!empty($product_stock->amount) && $product_stock->amount <= 0)) {
                self::deleteProduct($cart_detail_id, $cart_id);
                throw new Exception("Stok produk belum tersedia", 400);
            }

            if ($quantity > $product_stock->amount) {
                throw new Exception("Stok produk habis. {$product_stock->amount} stok yang tersedia sudah kamu masukkan ke keranjangmu.", 400);
            }

            if ($quantity < $minimum_purchase) {
                throw new Exception("Minimum pembelian untuk produk ini adalah {$minimum_purchase}.", 400);
            }

            $data->quantity = $quantity;
            $data->save();

            if ($data->quantity < 1) {
                self::deleteProduct($cart_detail_id, $cart_id);
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

        return null;
    }

    public function deleteAllCart($related_id, $buyer_id = null)
    {
        if ($buyer_id != null) {
            $carts = Cart::where('buyer_id', $buyer_id)->get();
            foreach ($carts as $cart) {
                if ($cart->delete() < 1) {
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
        foreach ($carts as $cart) {
            if ($cart->delete() < 1) {
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

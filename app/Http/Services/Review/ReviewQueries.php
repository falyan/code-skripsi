<?php

namespace App\Http\Services\Review;

use App\Models\Review;

class ReviewQueries{
    public function getListReviewByMerchant($merchant_id){
        $review = Review::with(['merchant', 'customer', 'product' => function ($product){
            $product->with(['product_photo']);
        }, 'order' => function ($order){
            $order->with(['detail']);
        }])->where('merchant_id', $merchant_id)->where('is_valid', true)->get();

        $response['success'] = true;
        $response['message'] = 'Data review berhasil didapatkan!';
        $response['data'] = $review;

        return $response;
    }
}

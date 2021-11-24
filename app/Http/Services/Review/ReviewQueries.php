<?php

namespace App\Http\Services\Review;

use App\Models\Review;

class ReviewQueries{
    public function getListReview($type, $related_id){
        $review = [];
        if ($type == 'seller'){
            $review = Review::with(['merchant', 'customer', 'product' => function ($product){
                $product->with(['product_photo']);
            }, 'order' => function ($order){
                $order->with(['detail']);
            }])->where('merchant_id', $related_id)->where('is_valid', true)->orderBy('updated_at', 'DESC')->paginate(10);
        } elseif ($type == 'buyer'){
            $review = Review::with(['merchant', 'customer', 'product' => function ($product){
                $product->with(['product_photo']);
            }, 'order' => function ($order){
                $order->with(['detail']);
            }])->where('customer_id', $related_id)->where('is_valid', true)->orderBy('updated_at', 'DESC')->paginate(10);
        }

        $response['success'] = true;
        $response['message'] = 'Data review berhasil didapatkan!';
        $response['data'] = $review;

        return $response;
    }

    public function getDetailReview($review_id){
        $review = Review::with(['review_photo', 'merchant', 'customer', 'product' => function ($product){
            $product->with(['product_photo']);
        }, 'order' => function ($order){
            $order->with(['detail']);
        }])->findOrFail($review_id);

        $response['success'] = true;
        $response['message'] = 'Detail review berhasil didapatkan!';
        $response['data'] = $review;

        return $response;
    }
}

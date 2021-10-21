<?php

namespace App\Http\Services\Review;

use App\Models\Review;

class ReviewCommands{
    public function addReview($data){
        $review = new Review();
        $review->merchant_id = $data['merchant_id'];
        $review->customer_id = $data['customer_id'] ?? null;
        $review->order_id = $data['order_id'] ?? null;
        $review->product_id = $data['product_id'];
        $review->rate = $data['rate'];
        $review->related_pln_mobile_customer_id = $data['related_pln_mobile_customer_id'] ?? null;
        $review->is_valid = true;
        $review->message = $data['message'] ?? null;

        if (!$review->save()){
            $response['success'] = false;
            $response['message'] = 'Gagal memberikan review';
            return $response;
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil memberikan review';
        return $response;
    }
}

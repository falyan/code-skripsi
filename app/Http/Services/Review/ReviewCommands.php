<?php

namespace App\Http\Services\Review;

use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewPhoto;
use Exception;
use Illuminate\Support\Facades\DB;

class ReviewCommands{
    public function addReview($data){
        try {
            $check_review = Review::where('order_id', $data['order_id'])->where('customer_id', $data['customer_id'])
                ->where('merchant_id', $data['merchant_id'])->where('product_id', $data['product_id'])->first();

            if ($check_review != null){
                $response['success'] = false;
                $response['message'] = 'Sudah melakukan review sebelumnya!';
                return $response;
            }

            DB::beginTransaction();
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

            $review_photo = '';
            $photos = [];
            if ($data['url'] != []){
                foreach ($data['url'] as $url_photo) {
                    $review_photo = new ReviewPhoto();
                    $review_photo->order_id = $data['order_id'] ?? null;
                    $review_photo->review_id = $review->id;
                    $review_photo->url = $url_photo;
                    $review_photo->created_by = $data['full_name'];
                    $review_photo->updated_by = $data['full_name'];
                    $review_photo->save();

                    array_push($photos, $review_photo);
                }

                if ($photos == []) {
                    $response['success'] = false;
                    $response['message'] = 'Gagal menambahkan foto review!';
                    return $response;
                }
            }

            //trigger for count review and rating
            $product = Product::where('id' ,$data['product_id']);
            $product =  $product->with('reviews')->withCount('reviews')->first();

            $rate = 0;
            foreach ($product->reviews as $key => $value) {
                $rate += $value->rate;
            }

            $product->update([
                'review_count' => $product->reviews_count,
                'avg_rating' => $rate/count($product->reviews)
            ]);

            DB::commit();
            $review_data = [$review, $photos];
            $response['success'] = true;
            $response['message'] = 'Berhasil memberikan review';
            $response['data'] = $review_data;
            return $response;

        }catch (Exception $e){
            DB::rollBack();
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public function replyReview($review_id, $data){
        try {
            DB::beginTransaction();
            $review = Review::findOrFail($review_id);
            if ($review->reply_message != null){
                $response['success'] = false;
                $response['message'] = 'Sudah melakukan reply review!';
                return $response;
            }

            $review->reply_message = $data['reply_message'];

            if (!$review->save()){
                $response['success'] = false;
                $response['message'] = 'Gagal memberikan tanggapan review';
                return $response;
            }

            DB::commit();
            $response['success'] = true;
            $response['message'] = 'Berhasil memberikan tanggapan review';
            $response['data'] = $review;
            return $response;

        }catch (Exception $e){
            DB::rollBack();
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }
}

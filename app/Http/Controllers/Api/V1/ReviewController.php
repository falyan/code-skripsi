<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Review\ReviewCommands;
use App\Http\Services\Review\ReviewQueries;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $reviewCommands, $reviewQueries;
    public function __construct()
    {
        $this->reviewCommands = new ReviewCommands();
        $this->reviewQueries = new ReviewQueries();
    }

    //Add Review Produk
    public function addReview(Request $request)
    {
        $request['customer_id'] = Auth::id();
        $request['full_name'] = Auth::user()->full_name;
        try {
            $rules = [
                'merchant_id' => 'required',
                'product_id' => 'required',
                'rate' => 'required|numeric',
                'order_id' => 'required',
                'url.*' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules, [
                'required' => ':attribute diperlukan.'
            ]);

            if ($validator->fails()) {
                $errors = collect();
                foreach ($validator->errors()->getMessages() as $key => $value) {
                    foreach ($value as $error) {
                        $errors->push($error);
                    }
                }

                return $this->respondValidationError($errors, 'Validation Error!');
            }

            return $this->reviewCommands->addReview($request);
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    public function getListReviewByMerchant(){
        try {
            $merchant_id = Auth::user()->merchant_id;
            return $this->reviewQueries->getListReview('merchant_id' ,$merchant_id);
        }catch (Exception $e){
            return $this->respondWithData($e, 'Error', 400);
        }
    }

    public function getListReviewByBuyer(){
        try {
            $buyer_id = Auth::id();
            return $this->reviewQueries->getListReview('buyer_id' ,$buyer_id);
        }catch (Exception $e){
            return $this->respondWithData($e, 'Error', 400);
        }
    }

    public function getDetailReview($review_id){
        try {
            return $this->reviewQueries->getDetailReview($review_id);
        }catch (Exception $e){
            return $this->respondWithData($e, 'Error', 400);
        }
    }

    public function replyReview($review_id, Request $request){
        try {
            $rules = [
                'reply_message' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules, [
                'required' => ':attribute diperlukan.'
            ]);

            if ($validator->fails()) {
                $errors = collect();
                foreach ($validator->errors()->getMessages() as $key => $value) {
                    foreach ($value as $error) {
                        $errors->push($error);
                    }
                }

                return $this->respondValidationError($errors, 'Validation Error!');
            }

            return $this->reviewCommands->replyReview($review_id, $request);
        }catch (Exception $e){
            return $this->respondWithData($e, 'Error', 400);
        }
    }

    public function getListReviewDoneByBuyer(){
        try {
            $buyer_id = Auth::id();
            return $this->reviewQueries->getListReviewDone('buyer_id' ,$buyer_id);
        }catch (Exception $e){
            return $this->respondWithData($e, 'Error', 400);
        }
    }

    public function getListReviewDoneByMerchant(){
        try {
            $merchant_id = Auth::user()->merchant_id;
            return $this->reviewQueries->getListReviewDone('merchant_id' ,$merchant_id);
        }catch (Exception $e){
            return $this->respondWithData($e, 'Error', 400);
        }
    }

    public function getListReviewDoneReplyByMerchant(){
        try {
            $merchant_id = Auth::user()->merchant_id;
            return $this->reviewQueries->getListReviewDoneReply('merchant_id' ,$merchant_id);
        }catch (Exception $e){
            return $this->respondWithData($e, 'Error', 400);
        }
    }

    public function getListReviewDoneUnreplyByMerchant(){
        try {
            $merchant_id = Auth::user()->merchant_id;
            return $this->reviewQueries->getListReviewDoneUnreply('merchant_id' ,$merchant_id);
        }catch (Exception $e){
            return $this->respondWithData($e, 'Error', 400);
        }
    }
}

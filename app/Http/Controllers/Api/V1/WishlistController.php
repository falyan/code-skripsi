<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Wishlist\WishlistCommands;
use App\Http\Services\Wishlist\WishlistQueries;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WishlistController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $wishlistQueries, $wishlistCommand;
    public function __construct()
    {
        $this->wishlistCommand = new WishlistCommands();
        $this->wishlistQueries = new WishlistQueries();
    }

    public function addOrRemoveWishlist(Request $request){
        $request['customer_id'] = Auth::id();
        try {
            $rules = [
                'merchant_id' => 'required',
                'product_id' => 'required'
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

            return $this->wishlistCommand->addOrRemoveWishlist($request);
        }catch (Exception $e){
            return $this->respondErrorException($e, $request);
        }
    }

    public function getListWishlistByCustomer(){
        try {
            return $this->wishlistQueries->getListWishlistByCustomer(Auth::id());
        }catch (Exception $e){
            return $this->respondWithData($e, 'Error', 400);
        }
    }

    public function searchListWishlistByName(Request $request){
        $request['customer_id'] = Auth::id();
        try {
            $rules = [
                'keyword' => 'required|min:3'
            ];

            $validator = Validator::make($request->all(), $rules, [
                'required' => ':attribute diperlukan.',
                'min' => 'panjang :attribute minimum :min karakter.',
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

            return $this->wishlistQueries->searchListWishlistByName($request);
        }catch (Exception $e){
            return $this->respondErrorException($e, $request);
        }
    }
}

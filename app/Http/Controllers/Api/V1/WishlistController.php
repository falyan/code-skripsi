<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Wishlist\WishlistCommands;
use App\Http\Services\Wishlist\WishlistQueries;
use Exception;
use Illuminate\Http\Request;
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

    public function addOrRemoveWishlist(Request $request)
    {
        $request['customer_id'] = Auth::id();
        try {
            $rules = [
                'merchant_id' => 'required',
                'product_id' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules, [
                'required' => ':attribute diperlukan.',
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
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    public function getListWishlistByCustomer(Request $request)
    {
        try {
            $limit = $request->limit ?? 10;
            $page = $request->page ?? 1;
            $sortby = $request->sortby ?? null;

            return $this->wishlistQueries->getListWishlistByCustomer(Auth::id(), $limit, $page, $sortby);
        } catch (Exception $e) {
            return $this->respondWithData($e, 'Error', 400);
        }
    }

    public function searchListWishlistByName(Request $request)
    {
        $customerId = Auth::id();

        try {
            $rules = [
                'keyword' => 'required|min:3',
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

            $limit = $request->limit ?? 10;
            $page = $request->page ?? 1;
            $sortby = $request->sortby ?? null;
            $keyword = $request->keyword;

            return $this->wishlistQueries->searchListWishlistByName($customerId, $limit, $page, $sortby, $keyword);
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Cart\CartCommands;
use App\Http\Services\Cart\CartQueries;
use App\Models\Cart;
use App\Models\CartDetail;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    /**
     * @OA\Get(
     *     path="/v1/buyer/query/cart",
     *     summary="Get Total Amount Cart",
     *     operationId="get_total_cart",
     *     tags={"Cart"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response="200",
     *         description="Returns total amount of customer cart",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Error: Unauthorized. When Bearer token is invalid or null.",
     *     ),
     * )
     */
    public function index($rlc_id, $buyer_id = null)
    {
        try {
            return $this->respondWithData(CartQueries::getTotalCart($rlc_id, $buyer_id), 'Sukses ambil data keranjang');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function add()
    {
        try {
            $validator = Validator::make(request()->all(), [
                'product_id' => 'required|exists:product,id',
                'buyer_id' => 'nullable|exists:customer,id',
                'related_merchant_id' => 'required|exists:merchant,id',
                'related_pln_mobile_customer_id' => 'required'
            ]);
     
            if ($validator->fails()) {
                return $this->respondValidationError($validator->messages()->get('*'), 'Validation Error!');
            }

            $data = CartCommands::addCart();

            return response()->json([
                'status' => 'success',
                'message' => $data
            ], 200);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function qtyUpdate(Request $request, $cart_detail_id, $cart_id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required'
        ]);

        try {
            if ($validator->fails()) {
                throw new Exception($validator->errors(), 400);
            }

            return CartCommands::QuantityUpdate($cart_detail_id, $cart_id);
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    public function destroy($cart_detail_id, $cart_id)
    {
        try {
            return CartCommands::deleteProduct($cart_detail_id, $cart_id);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function showDetail($buyer_id = null, $related_id){
        try {
            return CartQueries::getDetailCart($buyer_id, $related_id);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }
}

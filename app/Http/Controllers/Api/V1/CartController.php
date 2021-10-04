<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Cart\CartQueries;
use Illuminate\Http\Request;

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
    public function index()
    {
        try {
            return $this->respondWithData(CartQueries::getTotalCart(), 'Sukses ambil data keranjang');
        } catch (\Throwable $th) {
            if (in_array($th->getCode(), $this->error_codes)) {
                return $this->respondWithResult(false, $th->getMessage(), $th->getCode());
            }
            return $this->respondWithResult(false, $th->getMessage(), 500);
        }
    }

    public function add()
    {
        return 'seep!';
    }
}

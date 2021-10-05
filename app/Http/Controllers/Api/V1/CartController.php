<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Cart\CartCommands;
use App\Http\Services\Cart\CartQueries;
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
    public function index()
    {
        if (!$rlc_id = request()->header('Related-Customer-Id')) {
            return $this->respondWithResult(false, 'Kolom related_customer_id kosong', 400);
        }

        try {
            return $this->respondWithData(CartQueries::getTotalCart($rlc_id), 'Sukses ambil data keranjang');
        } catch (Exception $th) {
            if (in_array($th->getCode(), $this->error_codes)) {
                return $this->respondWithResult(false, $th->getMessage(), $th->getCode());
            }
            return $this->respondWithResult(false, $th->getMessage(), 500);
        }
    }

    public function add()
    {
        $validator = Validator::make(request()->all(), [
            'product_id' => 'required|exists:product,id'
        ]);

        try {
            if (!$rlc_id = request()->header('related_customer_id')) {
                throw new Exception('Kolom related_customer_id kosong', 400);
            }

            if ($validator->fails()) {
                throw new Exception($validator->errors(), 400);
            }

            $data = CartCommands::addCart($rlc_id);

            return $this->respondWithData($data, 'Keranjang berhasil disimpan');
        } catch (\Throwable $th) {
            if (in_array($th->getCode(), $this->error_codes)) {
                return response()->json(['error' => ['code' => 'ERROR', 'http_code' => $th->getCode(), 'message' => $th->getMessage()]], $th->getCode());
            }
            return response()->json(['error' => ['code' => 'ERROR', 'http_code' => $th->getCode(), 'message' => $th->getMessage()]], 404);
        }
    }

    public function qtyUpdate($id)
    {
        $validator = Validator::make(request()->all(), [
            'quantity' => 'required'
        ]);

        try {
            if ($validator->fails()) {
                throw new Exception($validator->errors(), 400);
            }

            $existsData = CartDetail::find($id);
            $quantityRequest = request('quantity');

            if ($existsData) {
                if ($quantityRequest == 0 || $quantityRequest < 1) {
                    $data = CartCommands::deleteProduct($id);
                    return $this->respondWithData($data, 'Produk dihapus dari keranjang');
                } else {
                    $data = CartCommands::QuantityUpdate($id);
                    return $this->respondWithData([
                        'cart_detail_id' => $existsData->id,
                        'qty' => $quantityRequest
                    ], 'QTY berhasil di update');
                }
            } else {
                return $this->respondWithData([], 'Error: ID tidak ditemukan!', 404);
            }
        } catch (\Throwable $th) {
            if (in_array($th->getCode(), $this->error_codes)) {
                return response()->json(['error' => ['code' => 'ERROR', 'http_code' => $th->getCode(), 'message' => $th->getMessage()]], $th->getCode());
            }
            return response()->json(['error' => ['code' => 'ERROR', 'http_code' => $th->getCode(), 'message' => $th->getMessage()]], 404);
        }
    }

    public function destroy($id)
    {
        try {
            $existsData = CartDetail::find($id);
            if ($existsData) {
                $data = CartCommands::deleteProduct($id);
                return $this->respondWithData($data, 'Produk berhasil dihapus');
            } else {
                return $this->respondWithData([], 'Error: ID tidak ditemukan!', 404);
            }
        } catch (\Throwable $th) {
            if (in_array($th->getCode(), $this->error_codes)) {
                return response()->json(['error' => ['code' => 'ERROR', 'http_code' => $th->getCode(), 'message' => $th->getMessage()]], $th->getCode());
            }
            return response()->json(['error' => ['code' => 'ERROR', 'http_code' => $th->getCode(), 'message' => $th->getMessage()]], 404);
        }
    }

    public function showDetail($buyer_id){
        try {
            return $this->respondWithData(CartQueries::getDetailCart($buyer_id), 'Sukses ambil data keranjang');
        } catch (\Throwable $th) {
            if (in_array($th->getCode(), $this->error_codes)) {
                return $this->respondWithResult(false, $th->getMessage(), $th->getCode());
            }
            return $this->respondWithResult(false, $th->getMessage(), 500);
        }
    }
}

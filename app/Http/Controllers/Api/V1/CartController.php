<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Cart\CartQueries;
use Illuminate\Http\Request;

class CartController extends Controller
{
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
}

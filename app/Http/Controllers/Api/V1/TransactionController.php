<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Transaction\TransactionCommands;
use App\Http\Services\Transaction\TransactionQueries;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Product;
use Exception, Input;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->transactionQueries = new TransactionQueries();
        $this->transactionCommand = new TransactionCommands();
    }

    // Checkout
    public function checkout()
    {
        $validator = Validator::make(request()->all(), [
            'merchants' => 'required|array',
            'merchants.*.merchant_id' => 'required',
            'merchants.*.total_weight' => 'required',
            'merchants.*.delivery_method' => 'required',
            'merchants.*.total_amount' => 'required',
            'merchants.*.total_payment' => 'required',
            'merchants.*.related_pln_mobile_customer_id' => 'required',
            'merchants.*.products' => 'required',
            'merchants.*.products.*.product_id' => 'required',
            'merchants.*.products.*.quantity' => 'required',
            'merchants.*.products.*.price' => 'required',
            'merchants.*.products.*.weight' => 'required',
            'merchants.*.products.*.insurance_cost' => 'required',
            'merchants.*.products.*.discount' => 'required',
            'merchants.*.products.*.total_price' => 'required',
            'merchants.*.products.*.total_weight' => 'required',
            'merchants.*.products.*.total_discount' => 'required',
            'merchants.*.products.*.total_insurance_cost' => 'required',
            'merchants.*.products.*.total_amount' => 'required',
            'merchants.*.products.*.payment_amount' => 'required',
            'merchants.*.products.*.payment_note' => 'sometimes',
        ]);

        if ($validator->fails()) {
            return $this->respondValidationError($validator->errors(), 'Validation Error!');
        }

        try {
            $this->validateProduct(request()->get('merchants'));

            $this->transactionCommand->createOrder(request()->get('merchants'));
        } catch (Exception $th) {
            if (in_array($th->getCode(), $this->error_codes)) {
                return $this->respondWithResult(false, $th->getMessage(), $th->getCode());
            }
            return $this->respondWithResult(false, $th->getMessage(), 500);
        }
    }

    private function validateProduct($merchants)
    {
        try {
            array_map(function($merchant) {
                array_map(function($item) {
                    if (!$product = Product::find(data_get($item, 'product_id'))) {
                        throw new Exception('Produk tidak ditemukan', 404);
                    }
                    if ($product->product_stock->pluck('amount')->first() < data_get($item, 'quantity')) {
                        throw new Exception('Stok produk tidak mencukupi', 400);
                    }
                }, data_get($merchant, 'products'));
            }, $merchants);
        } catch (Exception $th) {
            if (in_array($th->getCode(), $this->error_codes)) {
                return $this->respondWithResult(false, $th->getMessage(), $th->getCode());
            }
            return $this->respondWithResult(false, $th->getMessage(), 500);
        }
    }

    #region Buyer
    public function buyerIndex()
    {
        try {
            if (!$rlc_id = request()->header('Related-Customer-Id')) {
                return $this->respondWithResult(false, 'Kolom related_customer_id kosong', 400);
            }

            if (Auth::check()) {
                $user = Customer::find(Auth::id());
                $data = $this->transactionQueries->getTransaction('buyer_id', $user->id);
            } else {
                $data = $this->transactionQueries->getTransaction('related_pln_mobile_customer_id', $rlc_id);
            }

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            }else {
                return $this->respondWithResult(true, 'belum ada transaksi');
            }
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }

    public function transactionToPay()
    {
        try {
            if (!$rlc_id = request()->header('Related-Customer-Id')) {
                return $this->respondWithResult(false, 'Kolom related_customer_id kosong', 400);
            }

            if (Auth::check()) {
                $user = Customer::find(Auth::id());
                $data = $this->transactionQueries->getTransactionWithStatusCode('buyer_id', $user->id, [0]);
            } else {
                $data = $this->transactionQueries->getTransactionWithStatusCode('related_pln_mobile_customer_id', $rlc_id, [0]);
            }

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            }else {
                return $this->respondWithResult(true, 'tidak ada transaksi yang belum dibayar');
            }
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }

    public function transactionOnApprove()
    {
        try {
            if (!$rlc_id = request()->header('Related-Customer-Id')) {
                return $this->respondWithResult(false, 'Kolom related_customer_id kosong', 400);
            }

            if (Auth::check()) {
                $user = Customer::find(Auth::id());
                $data = $this->transactionQueries->getTransactionWithStatusCode('buyer_id', $user->id, [1]);
            } else {
                $data = $this->transactionQueries->getTransactionWithStatusCode('related_pln_mobile_customer_id', $rlc_id, [1]);
            }

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            }else {
                return $this->respondWithResult(true, 'tidak ada transaksi yang menunggu persetujuan');
            }
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }

    public function transactionOnDelivery()
    {
        try {
            if (!$rlc_id = request()->header('Related-Customer-Id')) {
                return $this->respondWithResult(false, 'Kolom related_customer_id kosong', 400);
            }

            if (Auth::check()) {
                $user = Customer::find(Auth::id());
                $data = $this->transactionQueries->getTransactionWithStatusCode('buyer_id', $user->id, [3, 8]);
            } else {
                $data = $this->transactionQueries->getTransactionWithStatusCode('related_pln_mobile_customer_id', $rlc_id, [3]);
            }

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            }else {
                return $this->respondWithResult(true, 'tidak ada transaksi yang sedang dikirim');
            }
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }

    public function buyerTransactionDone()
    {
        try {
            if (!$rlc_id = request()->header('Related-Customer-Id')) {
                return $this->respondWithResult(false, 'Kolom related_customer_id kosong', 400);
            }

            if (Auth::check()) {
                $user = Customer::find(Auth::id());
                $data = $this->transactionQueries->getTransactionWithStatusCode('buyer_id', $user->id, [88]);
            } else {
                $data = $this->transactionQueries->getTransactionWithStatusCode('related_pln_mobile_customer_id', $rlc_id, [88]);
            }

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            }else {
                return $this->respondWithResult(true, 'belum ada transaksi yang selesai');
            }
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }

    public function buyerTransactionCanceled()
    {
        try {
            if (!$rlc_id = request()->header('Related-Customer-Id')) {
                return $this->respondWithResult(false, 'Kolom related_customer_id kosong', 400);
            }

            if (Auth::check()) {
                $user = Customer::find(Auth::id());
                $data = $this->transactionQueries->getTransactionWithStatusCode('buyer_id', $user->id, [99]);
            } else {
                $data = $this->transactionQueries->getTransactionWithStatusCode('related_pln_mobile_customer_id', $rlc_id, [99]);
            }

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            }else {
                return $this->respondWithResult(true, 'tidak ada transaksi yang dibatalkan');
            }
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }

    public function buyerSearchTransaction($keyword)
    {
        try {
            if (!$rlc_id = request()->header('Related-Customer-Id')) {
                return $this->respondWithResult(false, 'Kolom related_customer_id kosong', 400);
            }

            if (strlen(trim($keyword)) < 3) {
                return $this->respondWithResult(false, 'Kata kunci minimal 3 karakter', 400);
            }

            if (Auth::check()) {
                $user = Customer::find(Auth::id());
                $data = $this->transactionQueries->searchTransaction('buyer_id', $user->id, $keyword);
            } else {
                $data = $this->transactionQueries->searchTransaction('related_pln_mobile_customer_id', $rlc_id, $keyword);
            }

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            }else {
                return $this->respondWithResult(false, 'transaksi untuk kata kunci ' . $keyword . ' tidak ditemukan');
            }
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }
    #End Region Buyer
    
    #Region Seller
    public function sellerIndex()
    {
        try {
            $data = $this->transactionQueries->getTransaction('merchant_id', Auth::user()->merchant_id);

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            }else {
                return $this->respondWithResult(true, 'belum ada transaksi');
            }
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }

    
    public function newOrder()
    {
        try {
            $data = $this->transactionQueries->getTransactionWithStatusCode('merchant_id', Auth::user()->merchant_id, [1]);

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            }else {
                return $this->respondWithResult(true, 'belum ada pesanan baru');
            }
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }

    public function orderToDeliver()
    {
        try {
            $data = $this->transactionQueries->getTransactionWithStatusCode('merchant_id', Auth::user()->merchant_id, [2]);

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');;
            }else {
                return $this->respondWithResult(true, 'belum ada pesanan yang siap dikirim');
            }
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }

    public function orderInDelivery()
    {
        try {
            $data = $this->transactionQueries->getTransactionWithStatusCode('merchant_id', Auth::user()->merchant_id, [3,8]);

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');;
            }else {
                return $this->respondWithResult(true, 'tidak ada pesanan yang sedang dikirim');
            }
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }

    public function orderDone()
    {
        try {
            $data = $this->transactionQueries->getTransactionWithStatusCode('merchant_id', Auth::user()->merchant_id, [88]);

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');;
            }else {
                return $this->respondWithResult(true, 'belum ada pesanan yang berhasil');
            }
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }

    public function sellerTransactionCanceled()
    {
        try {
            $data = $this->transactionQueries->getTransactionWithStatusCode('merchant_id', Auth::user()->merchant_id, [99]);

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');;
            }else {
                return $this->respondWithResult(true, 'belum ada pesanan yang dibatalkan');
            }
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }

    public function sellerSearchTransaction($keyword)
    {
        try {
            
            if (strlen(trim($keyword)) < 3) {
                return $this->respondWithResult(false, 'Kata kunci minimal 3 karakter', 400);
            }

            $data = $this->transactionQueries->searchTransaction('merchant_id', Auth::user()->merchant_id, $keyword);

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            }else {
                return $this->respondWithResult(false, 'transaksi untuk kata kunci ' . $keyword . ' tidak ditemukan');
            }
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }
    #End Region

    public function detailTransaction($id)
    {
        try {
            $data = $this->transactionQueries->getDetailTransaction($id);

            if (!empty($data)) {
                return $this->respondWithData($data, 'sukses get data transaksi');;
            }else {
                return $this->respondWithResult(false, 'ID transaksi salah', 400);
            }
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }
}

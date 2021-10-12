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
use stdClass;

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
    public function checkout($related_pln_mobile_customer_id)
    {
        $validator = Validator::make(request()->all(), [
            'merchants' => 'required|array',
            'merchants.*.merchant_id' => 'required',
            'merchants.*.total_weight' => 'required',
            'merchants.*.delivery_method' => 'required',
            'merchants.*.total_amount' => 'required',
            'merchants.*.total_payment' => 'required',
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
            'merchants.*.products.*.payment_note' => 'sometimes',
        ]);

        if ($validator->fails()) {
            return $this->respondValidationError($validator->errors(), 'Validation Error!');
        }

        
        try {
            if (!Customer::where('related_pln_mobile_customer_id', $related_pln_mobile_customer_id)->exists()) {
                throw new Exception('Customer tidak ditemukan', 404);
            }

            array_map(function($merchant) {
                array_map(function($item) {
                    if (!$product = Product::find(data_get($item, 'product_id'))) {
                        throw new Exception('Produk dengan id ' . data_get($item, 'product_id') . ' tidak ditemukan', 404);
                    }
                    if ($product->product_stock->pluck('amount')->first() < data_get($item, 'quantity')) {
                        throw new Exception('Stok produk dengan id ' . $product->id . ' tidak mencukupi', 400);
                    }
                }, data_get($merchant, 'products'));
            }, request()->get('merchants'));
            return $this->transactionCommand->createOrder(request()->all(), $related_pln_mobile_customer_id);
        } catch (Exception $th) {
            if (in_array($th->getCode(), $this->error_codes)) {
                return $this->respondWithResult(false, $th->getMessage(), $th->getCode());
            }
            return $this->respondWithResult(false, $th->getMessage(), 500);
        }
    }

    #region Buyer
    public function buyerIndex($related_id)
    {
        try {
            if (empty($related_id)) {
                return $this->respondWithResult(false, 'Kolom related_customer_id kosong', 400);
            }

            if (Auth::check()) {
                $user = Customer::find(Auth::id());
                $data = $this->transactionQueries->getTransaction('buyer_id', $user->id);
            } else {
                $data = $this->transactionQueries->getTransaction('related_pln_mobile_customer_id', $related_id);
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

    public function transactionToPay($related_id)
    {
        try {
            if (empty($related_id)) {
                return $this->respondWithResult(false, 'Kolom related_customer_id kosong', 400);
            }

            if (Auth::check()) {
                $user = Customer::find(Auth::id());
                $data = $this->transactionQueries->getTransactionWithStatusCode('buyer_id', $user->id, ['00']);
            } else {
                $data = $this->transactionQueries->getTransactionWithStatusCode('related_pln_mobile_customer_id', $related_id, ['00']);
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

    public function transactionOnApprove($related_id)
    {
        try {
            if (empty($related_id)) {
                return $this->respondWithResult(false, 'Kolom related_customer_id kosong', 400);
            }

            if (Auth::check()) {
                $user = Customer::find(Auth::id());
                $data = $this->transactionQueries->getTransactionWithStatusCode('buyer_id', $user->id, [1]);
            } else {
                $data = $this->transactionQueries->getTransactionWithStatusCode('related_pln_mobile_customer_id', $related_id, ['01']);
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

    public function transactionOnDelivery($related_id)
    {
        try {
            if (empty($related_id)) {
                return $this->respondWithResult(false, 'Kolom related_customer_id kosong', 400);
            }

            if (Auth::check()) {
                $user = Customer::find(Auth::id());
                $data = $this->transactionQueries->getTransactionWithStatusCode('buyer_id', $user->id, ['03', '08']);
            } else {
                $data = $this->transactionQueries->getTransactionWithStatusCode('related_pln_mobile_customer_id', $related_id, ['03', '08']);
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

    public function buyerTransactionDone($related_id)
    {
        try {
            if (empty($related_id)) {
                return $this->respondWithResult(false, 'Kolom related_customer_id kosong', 400);
            }

            if (Auth::check()) {
                $user = Customer::find(Auth::id());
                $data = $this->transactionQueries->getTransactionWithStatusCode('buyer_id', $user->id, ['88']);
            } else {
                $data = $this->transactionQueries->getTransactionWithStatusCode('related_pln_mobile_customer_id', $related_id, ['88']);
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

    public function buyerTransactionCanceled($related_id)
    {
        try {
            if (empty($related_id)) {
                return $this->respondWithResult(false, 'Kolom related_customer_id kosong', 400);
            }

            if (Auth::check()) {
                $user = Customer::find(Auth::id());
                $data = $this->transactionQueries->getTransactionWithStatusCode('buyer_id', $user->id, ['99']);
            } else {
                $data = $this->transactionQueries->getTransactionWithStatusCode('related_pln_mobile_customer_id', $related_id, ['99']);
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

    public function buyerSearchTransaction($related_id, $keyword)
    {
        try {
            if (empty($related_id)) {
                return $this->respondWithResult(false, 'Kolom related_customer_id kosong', 400);
            }

            if (strlen(trim($keyword)) < 3) {
                return $this->respondWithResult(false, 'Kata kunci minimal 3 karakter', 400);
            }

            if (Auth::check()) {
                $user = Customer::find(Auth::id());
                $data = $this->transactionQueries->searchTransaction('buyer_id', $user->id, $keyword);
            } else {
                $data = $this->transactionQueries->searchTransaction('related_pln_mobile_customer_id', $related_id, $keyword);
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
            $data = $this->transactionQueries->getTransactionWithStatusCode('merchant_id', Auth::user()->merchant_id, ['01']);

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
            $data = $this->transactionQueries->getTransactionWithStatusCode('merchant_id', Auth::user()->merchant_id, ['02']);

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
            $data = $this->transactionQueries->getTransactionWithStatusCode('merchant_id', Auth::user()->merchant_id, ['03', '08']);

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
            $data = $this->transactionQueries->getTransactionWithStatusCode('merchant_id', Auth::user()->merchant_id, ['88']);

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
            $data = $this->transactionQueries->getTransactionWithStatusCode('merchant_id', Auth::user()->merchant_id, ['99']);

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

    private function validateProduct($merchants)
    {
        $error = new stdClass();
        array_map(function($merchant) use ($error) {
            array_map(function($item) use ($error) {
                if (!$product = Product::find(data_get($item, 'product_id'))) {
                    if (isset($error->message) && isset($error->code)) {
                        return [
                            $error->message => 'Produk tidak ditemukan',
                            $error->code => 404
                        ];
                    }
                }
                if ($product->product_stock->pluck('amount')->first() < data_get($item, 'quantity')) {
                    if (isset($error->message) && isset($error->code)) {
                        return [
                            $error->message => 'Stok produk tidak mencukupi',
                            $error->code => 400
                        ];
                    }
                }
            }, data_get($merchant, 'products'));
        }, $merchants);
        $error;
    }
}

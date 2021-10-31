<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Notification\NotificationCommands;
use App\Http\Services\Transaction\TransactionCommands;
use App\Http\Services\Transaction\TransactionQueries;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Product;
use Exception, Input;
use Illuminate\Http\Request;
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
    protected $transactionQueries, $transactionCommand;
    public function __construct()
    {
        $this->transactionQueries = new TransactionQueries();
        $this->transactionCommand = new TransactionCommands();
        $this->notificationCommand = new NotificationCommands();
    }

    // Checkout
    public function checkout()
    {
        $validator = Validator::make(request()->all(), [
            'destination_info.receiver_name' => 'required',
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
        ], [
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

        try {
            $customer_id = Auth::id();
            array_map(function ($merchant) {
                array_map(function ($item) {
                    if (!$product = Product::find(data_get($item, 'product_id'))) {
                        throw new Exception('Produk dengan id ' . data_get($item, 'product_id') . ' tidak ditemukan', 404);
                    }
                    if ($product->product_stock->pluck('amount')->first() < data_get($item, 'quantity')) {
                        throw new Exception('Stok produk dengan id ' . $product->id . ' tidak mencukupi', 400);
                    }
                    if (data_get($item, 'quantity') < $product->minimum_purchase) {
                        throw new Exception('Pembelian minimum untuk produk ' . $product->name . ' adalah ' . $product->minimum_purchase, 400);
                    }
                }, data_get($merchant, 'products'));
            }, request()->get('merchants'));
            return $this->transactionCommand->createOrder(request()->all(), $customer_id);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    #region Buyer
    public function buyerIndex($related_id, Request $request)
    {
        try {
            if (empty($related_id)) {
                return $this->respondWithResult(false, 'Kolom related_customer_id kosong', 400);
            }

            $filter = $request->filter ?? [];
            $limit = $request->limit ?? 10;
            $page = $request->page ?? 1;

            if (Auth::check()) {
                $data = $this->transactionQueries->getTransaction('buyer_id', Auth::id(), $limit, $filter, $page);
            } else {
                $data = $this->transactionQueries->getTransaction('related_pln_mobile_customer_id', $related_id, $limit, $filter, $page);
            }

            if ($data['total'] > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            } else {
                return $this->respondWithResult(true, 'belum ada transaksi');
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function transactionToPay($related_id, Request $request)
    {
        try {
            if (empty($related_id)) {
                return $this->respondWithResult(false, 'Kolom related_customer_id kosong', 400);
            }

            $filter = $request->filter ?? [];
            $limit = $request->limit ?? 10;
            $page = $request->page ?? 1;

            if (Auth::check()) {
                $data = $this->transactionQueries->getTransactionWithStatusCode('buyer_id', Auth::id(), ['00'], $limit, $filter, $page);
            } else {
                $data = $this->transactionQueries->getTransactionWithStatusCode('related_pln_mobile_customer_id', $related_id, ['00'], $limit, $filter, $page);
            }

            if ($data['total'] > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            } else {
                return $this->respondWithResult(true, 'tidak ada transaksi yang belum dibayar');
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function transactionOnApprove($related_id, Request $request)
    {
        try {
            if (empty($related_id)) {
                return $this->respondWithResult(false, 'Kolom related_customer_id kosong', 400);
            }

            $filter = $request->filter ?? [];
            $limit = $request->limit ?? 10;
            $page = $request->page ?? 1;

            if (Auth::check()) {
                $data = $this->transactionQueries->getTransactionWithStatusCode('buyer_id', Auth::id(), ['01'], $limit, $filter, $page);
            } else {
                $data = $this->transactionQueries->getTransactionWithStatusCode('related_pln_mobile_customer_id', $related_id, ['01'], $limit, $filter, $page);
            }

            if ($data['total'] > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            } else {
                return $this->respondWithResult(true, 'tidak ada transaksi yang menunggu persetujuan');
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function transactionOnDelivery($related_id, Request $request)
    {
        try {
            if (empty($related_id)) {
                return $this->respondWithResult(false, 'Kolom related_customer_id kosong', 400);
            }

            $filter = $request->filter ?? [];
            $limit = $request->limit ?? 10;
            $page = $request->page ?? 1;

            if (Auth::check()) {
                $data = $this->transactionQueries->getTransactionWithStatusCode('buyer_id', Auth::id(), ['03', '08'], $limit, $filter, $page);
            } else {
                $data = $this->transactionQueries->getTransactionWithStatusCode('related_pln_mobile_customer_id', $related_id, ['03', '08'], $limit, $filter, $page);
            }

            if ($data['total'] > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            } else {
                return $this->respondWithResult(true, 'tidak ada transaksi yang sedang dikirim');
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function buyerTransactionDone($related_id, Request $request)
    {
        try {
            if (empty($related_id)) {
                return $this->respondWithResult(false, 'Kolom related_customer_id kosong', 400);
            }

            $filter = $request->filter ?? [];
            $limit = $request->limit ?? 10;
            $page = $request->page ?? 1;

            if (Auth::check()) {
                $data = $this->transactionQueries->getTransactionWithStatusCode('buyer_id', Auth::id(), ['88'], $limit, $filter, $page);
            } else {
                $data = $this->transactionQueries->getTransactionWithStatusCode('related_pln_mobile_customer_id', $related_id, ['88'], $limit, $filter, $page);
            }

            if ($data['total'] > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            } else {
                return $this->respondWithResult(true, 'belum ada transaksi yang selesai');
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function buyerTransactionCanceled($related_id, Request $request)
    {
        try {
            if (empty($related_id)) {
                return $this->respondWithResult(false, 'Kolom related_customer_id kosong', 400);
            }

            $filter = $request->filter ?? [];
            $limit = $request->limit ?? 10;
            $page = $request->page ?? 1;

            if (Auth::check()) {
                $data = $this->transactionQueries->getTransactionWithStatusCode('buyer_id', Auth::id(), ['99'], $limit, $filter, $page);
            } else {
                $data = $this->transactionQueries->getTransactionWithStatusCode('related_pln_mobile_customer_id', $related_id, ['99'], $limit, $filter, $page);
            }

            if ($data['total'] > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            } else {
                return $this->respondWithResult(true, 'tidak ada transaksi yang dibatalkan');
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function buyerSearchTransaction($related_id, Request $request)
    {
        try {
            $validator = Validator::make(request()->all(), [
                'keyword' => 'required|min:3',
                'limit' => 'nullable'
            ], [
                'exists' => 'ID :attribute tidak ditemukan.',
                'required' => ':attribute diperlukan.',
                'max' => 'panjang :attribute maksimum :max karakter.',
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

            $keyword = $request->keyword;
            $limit = $request->limit ?? 10;
            $filter = $request->filter ?? [];
            $page = $request->page ?? 1;
            
            if (Auth::check()) {
                $data = $this->transactionQueries->searchTransaction('buyer_id', Auth::id(), $keyword, $limit, $filter, $page);
            } else {
                $data = $this->transactionQueries->searchTransaction('related_pln_mobile_customer_id', $related_id, $keyword);
            }

            if ($data['total'] > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            } else {
                return $this->respondWithResult(false, 'transaksi untuk kata kunci ' . $keyword . ' tidak ditemukan');
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }
    #End Region Buyer

    #Region Seller
    public function sellerIndex(Request $request)
    {
        try {
            $filter = $request->filter ?? [];
            $limit = $request->limit ?? 10;
            $page = $request->page ?? 1;

            $data = $this->transactionQueries->getTransaction('merchant_id', Auth::user()->merchant_id, $limit, $filter, $page);

            if ($data['total'] > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            } else {
                return $this->respondWithResult(true, 'belum ada transaksi');
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }


    public function newOrder(Request $request)
    {
        try {
            $filter = $request->filter ?? [];
            $limit = $request->limit ?? 10;
            $page = $request->page ?? 1;

            $data = $this->transactionQueries->getTransactionWithStatusCode('merchant_id', Auth::user()->merchant_id, ['01'], $limit, $filter);

            if ($data['total'] > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            } else {
                return $this->respondWithResult(true, 'belum ada pesanan baru');
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function orderToDeliver(Request $request)
    {
        try {
            $filter = $request->filter ?? [];
            $limit = $request->limit ?? 10;
            $page = $request->page ?? 1;

            $data = $this->transactionQueries->getTransactionWithStatusCode('merchant_id', Auth::user()->merchant_id, ['02'], $limit, $filter, $page);

            if ($data['total'] > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');;
            } else {
                return $this->respondWithResult(true, 'belum ada pesanan yang siap dikirim');
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function orderInDelivery(Request $request)
    {
        try {
            $filter = $request->filter ?? [];
            $limit = $request->limit ?? 10;
            $page = $request->page ?? 1;

            $data = $this->transactionQueries->getTransactionWithStatusCode('merchant_id', Auth::user()->merchant_id, ['03', '08'], $limit, $filter, $page);

            if ($data['total'] > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');;
            } else {
                return $this->respondWithResult(true, 'tidak ada pesanan yang sedang dikirim');
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function orderDone(Request $request)
    {
        try {
            $filter = $request->filter ?? [];
            $limit = $request->limit ?? 10;
            $page = $request->page ?? 1;

            $data = $this->transactionQueries->getTransactionWithStatusCode('merchant_id', Auth::user()->merchant_id, ['88'], $limit, $filter, $page);

            if ($data['total'] > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');;
            } else {
                return $this->respondWithResult(true, 'belum ada pesanan yang berhasil');
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function sellerTransactionCanceled(Request $request)
    {
        try {
            $filter = $request->filter ?? [];
            $limit = $request->limit ?? 10;
            $page = $request->page ?? 1;

            $data = $this->transactionQueries->getTransactionWithStatusCode('merchant_id', Auth::user()->merchant_id, ['99'], $limit, $filter, $page);

            if ($data['total'] > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');;
            } else {
                return $this->respondWithResult(true, 'belum ada pesanan yang dibatalkan');
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function sellerSearchTransaction(Request $request)
    {
        try {
            $validator = Validator::make(request()->all(), [
                'keyword' => 'required|min:3',
                'limit' => 'nullable'
            ], [
                'exists' => 'ID :attribute tidak ditemukan.',
                'required' => ':attribute diperlukan.',
                'max' => 'panjang :attribute maksimum :max karakter.',
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

            $keyword = $request->keyword;
            $limit = $request->limit ?? 10;
            $filter = $request->filter ?? [];
            $page = $request->page ?? 1;

            $data = $this->transactionQueries->searchTransaction('merchant_id', Auth::user()->merchant_id, $keyword, $limit, $filter, $page);

            if ($data['total'] > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            } else {
                return $this->respondWithResult(false, 'transaksi untuk kata kunci ' . $keyword . ' tidak ditemukan');
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }
    #End Region

    public function detailTransaction($id)
    {
        try {
            $data = $this->transactionQueries->getDetailTransaction($id);

            if (!empty($data)) {
                return $this->respondWithData($data, 'sukses get detail transaksi');;
            } else {
                return $this->respondWithResult(false, 'No reference salah', 400);
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    private function validateProduct($merchants)
    {
        $error = new stdClass();
        array_map(function ($merchant) use ($error) {
            array_map(function ($item) use ($error) {
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

    public function acceptOrder(Request $request)
    {
        try {
            $rules = [
                'id.*' => 'required',
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

            foreach ($request->id as $order_id) {
                $response = $this->transactionCommand->updateOrderStatus($order_id, '02');
                if ($response['success'] == false) {
                    return $response;
                }
            }

            return $response;
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function rejectOrder($order_id)
    {
        try {
            $notes = request()->input('notes');
            return $this->transactionCommand->updateOrderStatus($order_id, '09', $notes);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function addAwbNumberOrder($order_id, $awb)
    {
        try {
            $response = $this->transactionCommand->addAwbNumber($order_id, $awb);
            if ($response['success'] == false) {
                return $response;
            }
            $status = $this->transactionCommand->updateOrderStatus($order_id, '03');
            if ($status['success'] == false) {
                return $status;
            }

            return $response;
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getInvoice($id)
    {
        try {
            $data = $this->transactionQueries->getDetailTransaction($id);
            if (!empty($data)) {
                return $this->respondWithData($data, 'sukses get detail Invoice');
            } else {
                return $this->respondWithResult(false, 'ID transaksi salah', 400);
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function finishOrder($id)
    {
        try {
            $status_order = $this->transactionQueries->getStatusOrder($id);
            if (in_array($status_order->status_code, ['03','08'])) {
                $this->transactionCommand->updateOrderStatus($id, '88');

                return $this->respondWithResult(true, 'Selamat! Pesanan anda telah selesai', 200);
            }else {
                return $this->respondWithResult(false, 'Pesanan anda belum dikirimkan oleh Penjual!', 400);
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function cancelOrder($id, Request $request)
    {
        try {
            $rules = [
                'reason' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules, [
                'required' => 'sertakan alasan pembatalan pesanan anda.',
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
            
            $status_order = $this->transactionQueries->getStatusOrder($id);
            if ($status_order->status_code == '00') {
                $this->transactionCommand->updateOrderStatus($id, '99', $request->reason);

                return $this->respondWithResult(true, 'Pesanan anda berhasil dibatalkan.', 200);
            }else {
                return $this->respondWithResult(false, 'Pesanan anda tidak dapat dibatalkan!', 400);
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }
}

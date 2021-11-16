<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Manager\IconcashManager;
use App\Http\Services\Notification\NotificationCommands;
use App\Http\Services\Product\ProductCommands;
use App\Http\Services\Transaction\TransactionCommands;
use App\Http\Services\Transaction\TransactionQueries;
use App\Models\Customer;
use App\Models\IconcashInquiry;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\ProductStock;
use Exception, Input;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use stdClass;
use App\Http\Services\Manager\MailSenderManager;
use App\Models\User;

class TransactionController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $transactionQueries, $transactionCommand, $mailSenderManager;
    public function __construct()
    {
        $this->transactionQueries = new TransactionQueries();
        $this->transactionCommand = new TransactionCommands();
        $this->notificationCommand = new NotificationCommands();
        $this->mailSenderManager = new MailSenderManager();
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
            $response = $this->transactionCommand->createOrder(request()->all(), $customer_id);

            if ($response['success'] == true) {
                array_map(function ($merchant) {
                    array_map(function ($item) use ($merchant) {
                        $stock = ProductStock::where('product_id', data_get($item, 'product_id'))
                            ->where('merchant_id', data_get($merchant, 'merchant_id'))->where('status', 1)->first();

                        $data['amount'] = $stock->amount - data_get($item, 'quantity');
                        $data['uom'] = $stock->uom;
                        $data['full_name'] = Auth::user()->full_name;

                        $productCommand = new ProductCommands();
                        $productCommand->updateStockProduct(data_get($item, 'product_id'), data_get($merchant, 'merchant_id'), $data);
                    }, data_get($merchant, 'products'));
                }, request()->get('merchants'));
            }

            return $response;
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
                $data = $this->transactionQueries->getTransactionWithStatusCode('buyer_id', Auth::id(), ['99', '09'], $limit, $filter, $page);
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

            $data = $this->transactionQueries->getTransactionWithStatusCode('merchant_id', Auth::user()->merchant_id, ['09'], $limit, $filter, $page);

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
                return $this->respondWithData($data, 'sukses get detail transaksi');
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
            $response = $this->transactionCommand->updateOrderStatus($order_id, '09', $notes);
            if ($response['success'] == true) {
                $mailSender = new MailSenderManager();
                $mailSender->mailorderRejected($order_id, $notes);
            }

            return $response;
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

            $mailSender = new MailSenderManager();
            $mailSender->mailOrderOnDelivery($order_id);

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
            $data = $this->transactionQueries->getStatusOrder($id);
            if (in_array($data->progress_active->status_code, ['08'])) {
                $this->transactionCommand->updateOrderStatus($id, '88');

                $column_name = 'merchant_id';
                $column_value = $data->merchant_id;
                $type = 2;
                $title = 'Transaksi selesai';
                $message = 'Transaksi sudah selesai, silahkan memeriksa saldo ICONCASH anda.';
                $url_path = 'v1/seller/query/transaction/detail/' . $id;

                $order = Order::find($id);
                $iconcash = Customer::where('merchant_id', $order->merchant_id)->first()->iconcash;
                $account_type_id = null;

                if (env('APP_ENV') == 'staging'){
                    $account_type_id = 13;
                } elseif (env('APP_ENV') == 'production'){
                    $account_type_id = 50;
                } else {
                    $account_type_id = 13;
                }

                $amount = $order->total_amount;
                $client_ref = $this->unique_code($iconcash->token);
                $corporate_id = 10;

                $topup_inquiry = IconcashInquiry::createTopupInquiry($iconcash, $account_type_id, $amount, $client_ref, $corporate_id);

                IconcashManager::topupConfirm($topup_inquiry->orderId, $topup_inquiry->amount);

                $notificationCommand = new NotificationCommands();
                $notificationCommand->create($column_name, $column_value, $type, $title, $message, $url_path);

                $customer = Customer::where('merchant_id', $data->merchant_id)->first();
                $notificationCommand->sendPushNotification($customer->id, $title, $message, 'active');

                $mailSender = new MailSenderManager();
                $mailSender->mailOrderDone($id);

                return $this->respondWithResult(true, 'Selamat! Pesanan anda telah selesai', 200);
            } else {
                return $this->respondWithResult(false, 'Pesanan anda belum dikirimkan oleh Penjual!', 400);
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function cancelOrder($id)
    {
        try {
            $rules = [
                'reason' => 'required',
            ];

            $validator = Validator::make(request()->all(), $rules, [
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

            if ($status_order->progress_active->status_code == '00') {
                $this->transactionCommand->updateOrderStatus($id, '99', request()->get('reason'));

                $order = Order::with('detail')->find($id);

                foreach ($order->detail as $detail){
                    $stock = ProductStock::where('product_id', $detail->product_id)
                        ->where('merchant_id', $order->merchant_id)->where('status', 1)->first();

                    $data['amount'] = $stock->amount + $detail->quantity;
                    $data['uom'] = $stock->uom;
                    $data['full_name'] = 'system';

                    $productCommand = new ProductCommands();
                    $productCommand->updateStockProduct($detail->product_id, $order->merchant_id, $data);
                }

                $mailSender = new MailSenderManager();
                $mailSender->mailorderCanceled($id);

                return $this->respondWithResult(true, 'Pesanan anda berhasil dibatalkan.', 200);
            } else {
                return $this->respondWithResult(false, 'Pesanan anda tidak dapat dibatalkan!', 400);
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function updatePaymentStatus()
    {
        if (request()->hasHeader('client-id')) {
            $client_id = request()->header('client-id');
            if ($client_id != config('credentials.iconpay.client_id')) {
                return response()->json([
                    'status' => 11,
                    'success' => false,
                    'message' => 'Invalid client id',
                    'data' => "Must be " . config('credentials.iconpay.client_id')
                ]);
            }
        } else {
            return response()->json([
                'status' => 15,
                'success' => false,
                'message' => 'Bad request data',
                'data' => [
                    'client-id' => request()->header('client-id') ?? null,
                    'timestamp' => request()->header('timestamp') ?? null,
                    'signature' => request()->header('signature') ?? null
                ]
            ]);
        }

        $ba_timestamp = null;
        if (request()->hasHeader('timestamp')) {
            $timestamp_plus = Carbon::now('Asia/Jakarta')->addMinutes(10)->toIso8601String();
            $timestamp_min = Carbon::now('Asia/Jakarta')->subMinutes(10)->toIso8601String();
            $ba_timestamp = request()->header('timestamp');

            if (strtotime($ba_timestamp) < strtotime($timestamp_min) || strtotime($ba_timestamp) > strtotime($timestamp_plus)) {
                return response()->json([
                    'status' => 12,
                    'success' => false,
                    'message' => 'Invalid timestamp',
                    'data' => "Must be between " . $timestamp_min . " and " . $timestamp_plus
                ]);
            }
        } else {
            return response()->json([
                'status' => 15,
                'success' => false,
                'message' => 'Bad request data',
                'data' => [
                    'client-id' => request()->header('client-id') ?? null,
                    'timestamp' => request()->header('timestamp') ?? null,
                    'signature' => request()->header('signature') ?? null
                ]
            ]);
        }

        if (request()->hasHeader('signature')) {
            $ba_signature = request()->header('signature');
            $encode_body = json_encode(request()->all(), JSON_UNESCAPED_SLASHES);

            $signature = hash_hmac('sha256', $encode_body . config('credentials.iconpay.client_id') . $ba_timestamp, sha1(config('credentials.iconpay.app_key')));
            if (!hash_equals($signature, $ba_signature)) {
                return response()->json([
                    'status' => 13,
                    'success' => false,
                    'message' => 'Invalid signature',
                    'data' => "Must be " . $signature
                ]);
            }
        } else {
            return response()->json([
                'status' => 15,
                'success' => false,
                'message' => 'Bad request data',
                'data' => [
                    'client-id' => request()->header('client-id') ?? null,
                    'timestamp' => request()->header('timestamp') ?? null,
                    'signature' => request()->header('signature') ?? null
                ]
            ]);
        }

        $validator = Validator::make(request()->all(), [
            'transaction_id' => 'required',
            'customer_payment_code' => 'required',
            'payment_date' => 'required',
            'payment_channel' => 'required',
            'transaction_amount' => 'required|integer',
            'fee_amount' => 'required|integer',
            'item_details' => 'required|array',
            'item_details.*.partner_reference' => 'required',
            'item_details.*.customer_id' => 'required',
            'item_details.*.no_reference' => 'required',
            'item_details.*.amount' => 'required'
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
            $payment_method = request()->payment_channel;

            $no_reference = null;
            foreach (request()->item_details as $detail) {
                $no_reference = $detail['no_reference'];
            }

            $updated_payment = $this->transactionCommand->updatePaymentDetail($no_reference, $payment_method);

            if ($updated_payment == false) {
                return $this->respondWithResult(false, 'Gagal merubah detail pembayaran.', 400);
            }

            $customer = null;
            $orders = Order::where('no_reference', $no_reference)->get();
            foreach ($orders as $order) {
                $response = $this->transactionCommand->updateOrderStatus($order->id, '01');
                if ($response['success'] == false) {
                    return $response;
                }

                $column_name = 'customer_id';
                $column_value = $order->buyer_id;
                $type = 2;
                $title = 'Pembayaran transaksi berhasil';
                $message = 'Pembayaran berhasil, menunggu konfirmasi pesananmu dari penjual';
                $url_path = 'v1/buyer/query/transaction/' . $order->buyer_id . '/detail/' . $order->id;

                $notificationCommand = new NotificationCommands();
                $notificationCommand->create($column_name, $column_value, $type, $title, $message, $url_path);

                $column_name_merchant = 'merchant_id';
                $column_value_merchant = $order->merchant_id;
                $title_merchant = 'Pesanan masuk';
                $message_merchant = 'Ada pesanan masuk, silahkan konfirmasi pesanan.';
                $url_path_merchant = 'v1/seller/query/transaction/detail/' . $order->id;

                $notificationCommand = new NotificationCommands();
                $notificationCommand->create($column_name_merchant, $column_value_merchant, $type, $title_merchant, $message_merchant, $url_path_merchant);
            }

            foreach ($orders as $order){
                $notificationCommand = new NotificationCommands();
                $customer = Customer::where('merchant_id', $order->merchant_id)->first();
                $notificationCommand->sendPushNotification($customer->id, $title_merchant, $message_merchant, 'active');

                $customer = Customer::find($order->buyer_id);
                $this->mailSenderManager->mailNewOrder($order->id);
            }

            $this->mailSenderManager->mailPaymentSuccess($order->id);
            return $response;
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getDeliveryDiscount(){
        try {
            $data = $this->transactionQueries->getDeliveryDiscount();

            if (!empty($data)) {
                return $this->respondWithData($data, 'berhail get delivery discount');
            } else {
                return $this->respondWithResult(false, 'data delivery discount yang aktif tidak ditemukan', 400);
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getCustomerDiscount(){
        try {
            $discount = $this->transactionQueries->getCustomerDiscount(Auth::user()->id, Auth::user()->email);
            return $this->respondWithData($discount, 'Data diskon customer berhasil didapatkan');
        }catch (Exception $e){
            return $this->respondErrorException($e, request());
        }
    }

    public function unique_code($value)
    {
        return substr(base_convert(sha1(uniqid($value)), 16, 36), 0, 25);
    }
}

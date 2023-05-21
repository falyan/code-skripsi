<?php

namespace App\Http\Controllers\Api\V1;

use App\Exports\TransactionExport;
use App\Http\Controllers\Controller;
use App\Http\Services\Manager\IconcashManager;
use App\Http\Services\Manager\MailSenderManager;
use App\Http\Services\Manager\RajaOngkirManager;
use App\Http\Services\Notification\NotificationCommands;
use App\Http\Services\Product\ProductCommands;
use App\Http\Services\Transaction\TransactionCommands;
use App\Http\Services\Transaction\TransactionQueries;
use App\Http\Services\Voucher\VoucherCommands;
use App\Models\Customer;
use App\Models\CustomerEVSubsidy;
use App\Models\IconcashInquiry;
use App\Models\MasterData;
use App\Models\Order;
use App\Models\OrderDelivery;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\VariantStock;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Input;
use Maatwebsite\Excel\Facades\Excel;
use stdClass;

class TransactionController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $transactionQueries, $transactionCommand, $mailSenderManager, $voucherCommand, $notificationCommand, $rajaongkirManager;
    public function __construct()
    {
        $this->transactionQueries = new TransactionQueries();
        $this->transactionCommand = new TransactionCommands();
        $this->notificationCommand = new NotificationCommands();
        $this->mailSenderManager = new MailSenderManager();
        $this->voucherCommand = new VoucherCommands();
        $this->rajaongkirManager = new RajaOngkirManager();
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
            "npwp" => 'nullable|string',
            'save_npwp' => 'nullable|boolean|required_with:npwp',
        ], [
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

        try {
            $customer = Auth::user();
            $request = request()->all();
            $request['merchants'] = array_map(function ($merchant) {
                if (data_get($merchant, 'delivery_method') == 'custom') {
                    if (data_get($merchant, 'has_custom_logistic') == false || null) {
                        throw new Exception('Merchant ' . data_get($merchant, 'name') . ' tidak mendukung pengiriman oleh seller', 404);
                    }
                    data_set($merchant, 'delivery_method', 'Pengiriman oleh Seller');
                }
                array_map(function ($item) {
                    if (!$product = Product::find(data_get($item, 'product_id'))) {
                        throw new Exception('Produk dengan id ' . data_get($item, 'product_id') . ' tidak ditemukan', 404);
                    }
                    if ($product->stock_active->amount < data_get($item, 'quantity')) {
                        throw new Exception('Stok produk dengan id ' . $product->id . ' tidak mencukupi', 400);
                    }
                    if (data_get($item, 'quantity') < $product->minimum_purchase) {
                        throw new Exception('Pembelian minimum untuk produk ' . $product->name . ' adalah ' . $product->minimum_purchase, 400);
                    }
                    if (data_get($item, 'variant_value_product_id') != null) {
                        if (
                            VariantStock::where('variant_value_product_id', data_get($item, 'variant_value_product_id'))
                            ->where('status', 1)->pluck('amount')->first() < data_get($item, 'quantity')
                        ) {
                            throw new Exception('Stok variant produk dengan id ' . data_get($item, 'variant_value_product_id') . ' tidak mencukupi', 400);
                        }
                    }
                }, data_get($merchant, 'products'));
                return $merchant;
            }, request()->get('merchants'));
            $response = $this->transactionCommand->createOrder($request, $customer);

            if ($response['success'] == true) {
                array_map(function ($merchant) {
                    array_map(function ($item) use ($merchant) {
                        $productCommand = new ProductCommands();

                        if (data_get($item, 'variant_value_product_id') != null) {
                            $variant_stock = VariantStock::where('variant_value_product_id', data_get($item, 'variant_value_product_id'))
                                ->where('status', 1)->first();

                            $data['amount'] = $variant_stock['amount'] - data_get($item, 'quantity');
                            $data['full_name'] = Auth::user()->full_name;

                            $productCommand->updateStockVariantProduct(data_get($item, 'variant_value_product_id'), $data);
                        }

                        $stock = ProductStock::where('product_id', data_get($item, 'product_id'))
                            ->where('merchant_id', data_get($merchant, 'merchant_id'))->where('status', 1)->first();

                        $data['amount'] = $stock['amount'] - data_get($item, 'quantity');
                        $data['uom'] = $stock['uom'];
                        $data['full_name'] = Auth::user()->full_name;

                        $productCommand->updateStockProduct(data_get($item, 'product_id'), data_get($merchant, 'merchant_id'), $data);
                    }, data_get($merchant, 'products'));
                }, $request['merchants']);
            }

            return $response;
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    // Checkout V2
    public function checkoutV2()
    {
        $rules = [
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
            // "npwp" => 'nullable|string',
            // 'save_npwp' => 'nullable|boolean|required_with:npwp',
        ];

        if (isset(request()->all()['customer'])) {
            $rules['customer.nik'] = 'required';
        }

        $validator = Validator::make(request()->all(), $rules, [
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

        try {
            $customer = Auth::user();
            $request = request()->all();
            $request['merchants'] = array_map(function ($merchant) {
                if (data_get($merchant, 'delivery_method') == 'custom') {
                    if (data_get($merchant, 'has_custom_logistic') == false || null) {
                        throw new Exception('Merchant ' . data_get($merchant, 'name') . ' tidak mendukung pengiriman oleh seller', 404);
                    }
                    data_set($merchant, 'delivery_method', 'Pengiriman oleh Seller');
                }
                array_map(function ($item) {
                    if (!$product = Product::find(data_get($item, 'product_id'))) {
                        throw new Exception('Produk dengan id ' . data_get($item, 'product_id') . ' tidak ditemukan', 404);
                    }
                    if ($product->stock_active->amount < data_get($item, 'quantity')) {
                        throw new Exception('Stok produk dengan id ' . $product->id . ' tidak mencukupi', 400);
                    }
                    if (data_get($item, 'quantity') < $product->minimum_purchase) {
                        throw new Exception('Pembelian minimum untuk produk ' . $product->name . ' adalah ' . $product->minimum_purchase, 400);
                    }
                    if (data_get($item, 'variant_value_product_id') != null) {
                        if (
                            VariantStock::where('variant_value_product_id', data_get($item, 'variant_value_product_id'))
                            ->where('status', 1)->pluck('amount')->first() < data_get($item, 'quantity')
                        ) {
                            throw new Exception('Stok variant produk dengan id ' . data_get($item, 'variant_value_product_id') . ' tidak mencukupi', 400);
                        }
                    }
                }, data_get($merchant, 'products'));
                return $merchant;
            }, request()->get('merchants'));
            $response = $this->transactionCommand->createOrderV2($request, $customer);

            if ($response['success'] == true) {
                array_map(function ($merchant) {
                    array_map(function ($item) use ($merchant) {
                        $productCommand = new ProductCommands();

                        if (data_get($item, 'variant_value_product_id') != null) {
                            $variant_stock = VariantStock::where('variant_value_product_id', data_get($item, 'variant_value_product_id'))
                                ->where('status', 1)->first();

                            $data['amount'] = $variant_stock['amount'] - data_get($item, 'quantity');
                            $data['full_name'] = Auth::user()->full_name;

                            $productCommand->updateStockVariantProduct(data_get($item, 'variant_value_product_id'), $data);
                        }

                        $stock = ProductStock::where('product_id', data_get($item, 'product_id'))
                            ->where('merchant_id', data_get($merchant, 'merchant_id'))->where('status', 1)->first();

                        $data['amount'] = $stock['amount'] - data_get($item, 'quantity');
                        $data['uom'] = $stock['uom'];
                        $data['full_name'] = Auth::user()->full_name;

                        $productCommand->updateStockProduct(data_get($item, 'product_id'), data_get($merchant, 'merchant_id'), $data);
                    }, data_get($merchant, 'products'));
                }, $request['merchants']);
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
    public function transactionByCategoryKey($related_id, $category_key, Request $request)
    {
        try {
            if (empty($related_id)) {
                return $this->respondWithResult(false, 'Kolom related_customer_id kosong', 400);
            }

            $filter = $request->filter ?? [];
            $limit = $request->limit ?? 10;
            $page = $request->page ?? 1;

            if (Auth::check()) {
                $data = $this->transactionQueries->getTransactionByCategoryKey('buyer_id', Auth::id(), $category_key, $limit, $filter, $page);
            } else {
                $data = $this->transactionQueries->getTransactionByCategoryKey('related_pln_mobile_customer_id', $related_id, $category_key, $limit, $filter, $page);
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

    public function transactionOnProccess($related_id, Request $request)
    {
        try {
            if (empty($related_id)) {
                return $this->respondWithResult(false, 'Kolom related_customer_id kosong', 400);
            }

            $filter = $request->filter ?? [];
            $limit = $request->limit ?? 10;
            $page = $request->page ?? 1;

            if (Auth::check()) {
                $data = $this->transactionQueries->getTransactionWithStatusCode('buyer_id', Auth::id(), ['01', '02', '03', '08'], $limit, $filter, $page);
            } else {
                $data = $this->transactionQueries->getTransactionWithStatusCode('related_pln_mobile_customer_id', $related_id, ['01', '02', '03', '08'], $limit, $filter, $page);
            }

            if ($data['total'] > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            } else {
                return $this->respondWithResult(true, 'tidak ada transaksi dalam proses');
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
                $data = $this->transactionQueries->getTransactionDone('buyer_id', Auth::id(), ['88'], $limit, $filter, $page);
            } else {
                $data = $this->transactionQueries->getTransactionDone('related_pln_mobile_customer_id', $related_id, ['88'], $limit, $filter, $page);
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
                $data = $this->transactionQueries->getTransactionWithStatusCode('related_pln_mobile_customer_id', $related_id, ['99', '09'], $limit, $filter, $page);
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
                'limit' => 'nullable',
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

            $data = $this->transactionQueries->getTransactionWithStatusCode('merchant_id', Auth::user()->merchant_id, ['01'], $limit, $filter, $page);

            if ($data['total'] > 0) {
                $respon = $this->respondWithData($data, 'sukses get data transaksi');
            } else {
                return $this->respondWithResult(true, 'belum ada pesanan baru');
            }

            return $respon;
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
                return $this->respondWithData($data, 'sukses get data transaksi');
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
                return $this->respondWithData($data, 'sukses get data transaksi');
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
                return $this->respondWithData($data, 'sukses get data transaksi');
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
                return $this->respondWithData($data, 'sukses get data transaksi');
            } else {
                return $this->respondWithResult(true, 'belum ada pesanan yang dibatalkan');
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    //Export order to excel
    public function exportExcel(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'status_code' => 'string',
            'start_date' => 'date',
            'end_date' => 'date',
        ], [
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

        $data = [];
        try {

            $transactions = $this->transactionQueries->getTransactionToExport('merchant_id', Auth::user()->merchant_id, $request->all());
            // return $transactions;
            foreach ($transactions as $transaction) {
                $data[] = [
                    'trx_no' => $transaction->trx_no,
                    'buyer_name' => $transaction->buyer->full_name ?? '',
                    'order_date' => $transaction->order_date,
                    'total_amount' => $transaction->total_amount,
                    'total_weight' => $transaction->total_weight,
                    'payment_method' => $transaction->payment->payment_method ?? '',
                    'status_name' => $transaction->progress_active->status_name,
                    'related_pln_mobile_customer_id' => $transaction->related_pln_mobile_customer_id,
                    'delivery_method' => $transaction->delivery->delivery_method ?? '',
                    'delivery_fee' => $transaction->delivery->delivery_fee ?? '',
                    'created_by' => $transaction->created_by,
                    'updated_by' => $transaction->updated_by,
                ];
            }

            // return $data;
            $response = Excel::download(new TransactionExport($data), 'MKP-' . date('YmdHis') . '.xlsx');

            return $response->deleteFileAfterSend(false);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function sellerSearchTransaction(Request $request)
    {
        try {
            $validator = Validator::make(request()->all(), [
                'keyword' => 'min:3',
                'limit' => 'nullable',
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

            $validator = Validator::make($filter, [
                'start_date' => 'date|before_or_equal:end_date',
                'end_date' => 'date|after_or_equal:start_date',
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

            $data = $this->transactionQueries->searchTransaction('merchant_id', Auth::user()->merchant_id, $keyword, $limit, $filter, $page);

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            } else {
                return $this->respondWithResult(false, 'transaksi untuk kata kunci ' . $keyword . ' tidak ditemukan', 404);
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function sellerCountSearchTransaction(Request $request)
    {
        try {
            $validator = Validator::make(request()->all(), [
                'keyword' => 'min:3',
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
            $filter = $request->filter ?? [];

            $validator = Validator::make($filter, [
                'start_date' => 'date|before_or_equal:end_date',
                'end_date' => 'date|after_or_equal:start_date',
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

            $total = $this->transactionQueries->countSearchTransaction('merchant_id', Auth::user()->merchant_id, $keyword, $filter);

            if ($total > 0) {
                return $this->respondWithData($total, 'sukses get total transaksi');
            } else {
                return $this->respondWithResult(false, 'transaksi untuk kata kunci ' . $keyword . ' tidak ditemukan', 404);
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function sellerSubsidyEv(Request $request)
    {
        try {
            $filter = $request->filter ?? [];
            $limit = $request->limit ?? 10;
            $page = $request->page ?? 1;

            $data = $this->transactionQueries->sellerSubsidyEv(Auth::user()->merchant_id, $limit, $filter, $page);

            if ($data['total'] > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi subsidi');
            } else {
                return $this->respondWithData($data, 'belum ada transaksi subsidi');
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
                            $error->code => 404,
                        ];
                    }
                }
                if ($product->product_stock->pluck('amount')->first() < data_get($item, 'quantity')) {
                    if (isset($error->message) && isset($error->code)) {
                        return [
                            $error->message => 'Stok produk tidak mencukupi',
                            $error->code => 400,
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

            $response = null;
            DB::beginTransaction();
            foreach ($request->id as $order_id) {
                $data = $this->transactionQueries->getStatusOrder($order_id, true);

                $status_codes = [];
                foreach ($data->progress as $item) {
                    if (in_array($item->status_code, ['01'])) {
                        $status_codes[] = $item;
                    }
                }

                $status_code = collect($status_codes)->where('status_code', '01')->first();

                if (count($status_codes) == 1 && $status_code['status'] == 1) {
                    if ($data->merchant->official_store_tiket) {
                        $tiket = $this->transactionCommand->generateTicket($order_id);
                        if ($tiket['success'] == false) {
                            return $tiket;
                        }

                        $response = $this->transactionCommand->updateOrderStatusTiket($order_id);
                        if ($response['success'] == false) {
                            return $response;
                        }
                    } else {
                        $response = $this->transactionCommand->updateOrderStatus($order_id, '02');
                        if ($response['success'] == false) {
                            return $response;
                        }
                    }

                    $title = 'Pesanan Dikonfirmasi';
                    $message = 'Pesanan anda sedang diproses oleh penjual.';
                    $order = Order::with(['buyer', 'detail', 'progress_active', 'payment'])->find($order_id);
                    if (empty($order)) {
                        $response['success'] = false;
                        $response['message'] = 'Gagal mendapatkan data pesanan';
                        return $response;
                    }
                    // $this->notificationCommand->sendPushNotification($order->buyer->id, $title, $message, 'active');
                    $this->notificationCommand->sendPushNotificationCustomerPlnMobile($order->buyer->id, $title, $message);

                    $orders = Order::with(['delivery'])->where('no_reference', $order->no_reference)->get();
                    $total_amount_trx = $total_delivery_fee_trx = 0;

                    foreach ($orders as $o) {
                        $total_amount_trx += $o->total_amount;
                        $total_delivery_fee_trx += $o->delivery->delivery_fee;
                    }

                    $master_data = MasterData::whereIn('key', ['ubah_daya_min_transaction', 'ubah_daya_implementation_period'])->get();
                    $min_ubah_daya = collect($master_data)->where('key', 'ubah_daya_min_transaction')->first();
                    $period = collect($master_data)->where('key', 'ubah_daya_implementation_period')->first();

                    if ($order->voucher_ubah_daya_code == null && ($total_amount_trx - $total_delivery_fee_trx) >= $min_ubah_daya->value) {
                        if (Carbon::parse(explode('/', $period->value)[0]) >= Carbon::now() || Carbon::parse(explode('/', $period->value)[1]) <= Carbon::now()) {
                            $res_generate = $this->voucherCommand->generateVoucher($order);

                            if ($res_generate['success'] == false) {
                                Log::info("E00004", [
                                    'path_url' => "voucher.claim.ubahdaya.error",
                                    'query' => [],
                                    'response' => $res_generate['message'],
                                ]);
                            }
                        }
                    }

                    DB::commit();

                    $mailSender = new MailSenderManager();
                    if ($data->merchant->official_store_tiket) {
                        // dispatch(new SendEmailTiketJob($order_id, $tiket['data']));
                        $mailSender->mailSendTicket($order_id, $tiket['data']);
                    } else {
                        $mailSender->mailAcceptOrder($order_id);
                    }

                    return [
                        'success' => true,
                        'message' => 'Pesanan ' . $order_id . ' berhasil dikonfirmasi',
                    ];
                } else {
                    return $this->respondWithResult(false, 'Pesanan ' . $order_id . ' tidak dalam status menunggu konfirmasi!', 400);
                }
            }

            return $response;
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respondErrorException($e, request());
        }
    }

    public function rejectOrder($order_id)
    {
        try {
            $notes = request()->input('notes');
            $response = $this->transactionCommand->updateOrderStatus($order_id, '09', $notes);
            $order = Order::with('detail')->find($order_id);

            $evCustomer = CustomerEVSubsidy::where([
                'order_id' => $order_id,
            ])->first();

            if ($evCustomer) {
                $evCustomer->status_approval = 0;
                $evCustomer->save();
            }

            foreach ($order->detail as $detail) {
                $stock = ProductStock::where('product_id', $detail->product_id)
                    ->where('merchant_id', $order->merchant_id)->where('status', 1)->first();

                $data['amount'] = $stock->amount + $detail->quantity;
                $data['uom'] = $stock->uom;
                $data['full_name'] = 'system';

                $productCommand = new ProductCommands();
                $productCommand->updateStockProduct($detail->product_id, $order->merchant_id, $data);
            }
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

            if (!is_numeric($order_id)) {
                $response = [
                    'success' => false,
                    'message' => 'order id harus berupa angka',
                ];
                return $response;
            }

            // $awb_number = $request->input('awb_number');
            $check_awb = $this->transactionQueries->checkAwb($awb);

            if (!empty($check_awb)) {
                $response = [
                    'success' => false,
                    'message' => 'awb sudah terdaftar',
                ];
                return $response;
            }

            // $courir = OrderDelivery::where('order_id', $order_id)->first()->delivery_method;
            // if ($courir == 'J&T') {
            //     $courir = 'jnt';
            // }
            // $cek_resi = $this->rajaongkirManager->cekResi($awb, $courir);
            // if ($cek_resi == false) {
            //     $response = [
            //         'success' => false,
            //         'message' => 'Nomor resi yang anda masukkan tidak ditemukan',
            //     ];

            //     return $response;
            // }

            DB::beginTransaction();
            $data = $this->transactionQueries->getStatusOrder($order_id, true);

            $status_codes = [];
            foreach ($data->progress as $item) {
                if (in_array($item->status_code, ['01', '02'])) {
                    $status_codes[] = $item;
                }
            }

            $status_code = collect($status_codes)->where('status_code', '02')->first();
            if (count($status_codes) == 2 && $status_code['status'] == 1) {
                $response = $this->transactionCommand->addAwbNumber($order_id, $awb);
                if ($response['success'] == false) {
                    return $response;
                }
                $status = $this->transactionCommand->updateOrderStatus($order_id, '03');
                if ($status['success'] == false) {
                    return $status;
                }

                $title = 'Pesanan Dikirim';
                $message = 'Pesanan anda sedang dalam pengiriman.';
                $order = Order::with(['buyer', 'detail', 'progress_active', 'payment'])->find($order_id);
                // $this->notificationCommand->sendPushNotification($order->buyer->id, $title, $message, 'active');
                $this->notificationCommand->sendPushNotificationCustomerPlnMobile($order->buyer->id, $title, $message);

                // $orders = Order::with(['delivery'])->where('no_reference', $order->no_reference)->get();
                // $total_amount_trx = $total_delivery_fee_trx = 0;

                // foreach($orders as $o){
                //     $total_amount_trx += $o->total_amount;
                //     $total_delivery_fee_trx += $o->delivery->delivery_fee;
                // }

                // if ($order->voucher_ubah_daya_code == null && ($total_amount_trx - $total_delivery_fee_trx) >= 100000){
                //     $this->voucherCommand->generateVoucher($order);
                // }
                DB::commit();

                $mailSender = new MailSenderManager();
                $mailSender->mailOrderOnDelivery($order_id);

                return $response;
            } else {
                return $this->respondWithResult(false, 'Pesanan ' . $order_id . ' tidak dalam status siap dikirim!', 400);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respondErrorException($e, request());
        }
    }

    public function addAwbNumberAutoOrder($order_id)
    {
        try {
            if (!is_numeric($order_id)) {
                $response = [
                    'success' => false,
                    'message' => 'order id harus berupa angka',
                ];
                return $response;
            }
            DB::beginTransaction();

            $data = $this->transactionQueries->getStatusOrder($order_id, true)->load('merchant');

            $status_codes = [];
            foreach ($data->progress as $item) {
                if (in_array($item->status_code, ['01', '02'])) {
                    $status_codes[] = $item;
                }
            }

            $status_code = collect($status_codes)->where('status_code', '02')->first();
            if (count($status_codes) == 2 && $status_code['status'] == 1) {
                $response = $this->transactionCommand->addAwbNumberAuto($order_id);
                if ($response['success'] == false) {
                    return $response;
                }

                $status = $this->transactionCommand->updateOrderStatus($order_id, '03');
                if ($status['success'] == false) {
                    return $status;
                }

                $title = 'Pesanan Dikirim';
                $message = 'Pesanan anda sedang dalam pengiriman.';
                $order = Order::with(['buyer', 'detail', 'progress_active', 'payment'])->find($order_id);
                // $this->notificationCommand->sendPushNotification($order->buyer->id, $title, $message, 'active');
                $this->notificationCommand->sendPushNotificationCustomerPlnMobile($order->buyer->id, $title, $message);

                // $orders = Order::with(['delivery'])->where('no_reference', $order->no_reference)->get();
                // $total_amount_trx = $total_delivery_fee_trx = 0;

                // foreach($orders as $o){
                //     $total_amount_trx += $o->total_amount;
                //     $total_delivery_fee_trx += $o->delivery->delivery_fee;
                // }

                // if ($order->voucher_ubah_daya_code == null && ($total_amount_trx - $total_delivery_fee_trx) >= 100000){
                //     $this->voucherCommand->generateVoucher($order);
                // }

                DB::commit();

                $mailSender = new MailSenderManager();
                $mailSender->mailOrderOnDelivery($order_id);

                return $response;
            } else {
                return $this->respondWithResult(false, 'Pesanan ' . $order_id . ' tidak dalam status siap dikirim!', 400);
            }
        } catch (Exception $e) {
            DB::rollBack();
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

    public function triggerRatingProductSold(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:items_sold,review_avg',
        ]);

        if ($validator->fails()) {
            return $this->respondValidationError($validator->errors(), 'Validation Error!');
        }

        try {
            return $this->transactionCommand->triggerRatingProductSold($request->type);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function finishOrder($id)
    {
        $check_status = ['08'];
        $data = $this->transactionQueries->getStatusOrder($id);
        $status_code = $data->progress_active->status_code;

        if (!Auth::check() || Auth::user()->id == $data->buyer_id) {
            $check_status[] = '03';
        }

        try {
            if (in_array($status_code, $check_status)) {
                if ($status_code == '03') {
                    $notes = 'finish on delivery';
                    $this->transactionCommand->updateOrderStatus($id, '08', $notes);
                }

                $this->transactionCommand->triggerItemSold($id);
                $this->transactionCommand->updateOrderStatus($id, '88');

                $column_name = 'merchant_id';
                $column_value = $data->merchant_id;
                $type = 2;
                $title = 'Transaksi selesai';
                $message = 'Transaksi sudah selesai, silakan memeriksa saldo ICONCASH anda.';
                $url_path = 'v1/seller/query/transaction/detail/' . $id;

                $order = Order::find($id);
                $iconcash = Customer::where('merchant_id', $order->merchant_id)->first()->iconcash;

                $total_insentif = 0;
                foreach ($order->detail as $item) {
                    $total_insentif += $item->total_insentif;
                }

                $account_type_id = null;
                if (env('APP_ENV') == 'staging') {
                    $account_type_id = 13;
                } elseif (env('APP_ENV') == 'production') {
                    $account_type_id = 50;
                } else {
                    $account_type_id = 13;
                }

                $amount = $order->total_amount - $total_insentif;
                $client_ref = $this->unique_code($iconcash->token);
                $corporate_id = 10;

                $topup_inquiry = IconcashInquiry::createTopupInquiry($iconcash, $account_type_id, $amount, $client_ref, $corporate_id, $order);

                $res = IconcashManager::topupConfirm($topup_inquiry->orderId, $topup_inquiry->amount);

                IconcashInquiry::find($topup_inquiry->id)->update([
                    'confirm_res_json' => json_decode($res),
                ]);

                $notificationCommand = new NotificationCommands();
                $notificationCommand->create($column_name, $column_value, $type, $title, $message, $url_path);

                $customer = Customer::where('merchant_id', $data->merchant_id)->first();
                $notificationCommand->sendPushNotification($customer->id, $title, $message, 'active');

                $mailSender = new MailSenderManager();
                $mailSender->mailOrderDone($id);

                return $this->respondWithResult(true, 'Selamat! Pesanan anda telah selesai', 200);
            } else {
                if ($status_code == '03') {
                    return $this->respondWithResult(false, 'Pesanan sedang dalam pengiriman!', 400);
                }

                if ($status_code == '88') {
                    return $this->respondWithResult(false, 'Pesanan anda sudah selesai!', 400);
                }

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

                $evCustomer = CustomerEVSubsidy::where([
                    'order_id' => $id,
                ])->first();

                if ($evCustomer) {
                    $evCustomer->status_approval = 0;
                    $evCustomer->save();
                }

                foreach ($order->detail as $detail) {
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

    public function orderConfirmHasArrived($order_id)
    {
        try {
            $order = $this->transactionQueries->getStatusOrder($order_id, true);

            $status_codes = [];
            foreach ($order->progress as $item) {
                if (in_array($item->status_code, ['01', '02', '03'])) {
                    $status_codes[] = $item;
                }
            }

            $status_code = collect($status_codes)->where('status_code', '03')->first();
            if (count($status_codes) == 3 && $status_code['status'] == 1) {
                return DB::transaction(function () use ($order) {
                    return $this->transactionCommand->orderConfirmHasArrived($order->trx_no);
                });
            } else {
                return $this->respondWithResult(false, 'Pesanan selain status Sedang Dikirim tidak bisa dikonfirmasi lagi!', 400);
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
                    'data' => "Must be " . config('credentials.iconpay.client_id'),
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
                    'signature' => request()->header('signature') ?? null,
                ],
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
                    'data' => "Must be between " . $timestamp_min . " and " . $timestamp_plus,
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
                    'signature' => request()->header('signature') ?? null,
                ],
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
                    'data' => "Must be " . $signature,
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
                    'signature' => request()->header('signature') ?? null,
                ],
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
            'item_details.*.amount' => 'required',
        ], [
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
            $orders = Order::where('no_reference', $no_reference)->with(['progress_active'])->get();
            foreach ($orders as $order) {
                if (in_array($order->progress_active->status_code, ['00'])) {
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
                    $message_merchant = 'Ada pesanan masuk, silakan konfirmasi pesanan.';
                    $url_path_merchant = 'v1/seller/query/transaction/detail/' . $order->id;

                    $notificationCommand = new NotificationCommands();
                    $notificationCommand->create($column_name_merchant, $column_value_merchant, $type, $title_merchant, $message_merchant, $url_path_merchant);

                    $notificationCommand = new NotificationCommands();
                    $customer = Customer::where('merchant_id', $order->merchant_id)->first();
                    $notificationCommand->sendPushNotification($customer->id, $title_merchant, $message_merchant, 'active');

                    $customer = Customer::find($order->buyer_id);
                    $this->mailSenderManager->mailNewOrder($order->id);
                } else {
                    $response['response_code'] = '00';
                    $response['response_message'] = 'Sukses.';
                    $response['data'] = null;
                    return $response;
                }
            }

            $this->mailSenderManager->mailPaymentSuccess($order->id);

            //Request custom response BA ICP
            $response_ba['response_code'] = '00';
            $response_ba['response_message'] = 'Sukses.';
            $response_ba['data'] = null;
            return $response_ba;
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function updatePaymentStatusForBOT()
    {
        $validator = Validator::make(request()->all(), [
            'no_reference' => 'required',
        ], [
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

        try {
            $no_reference = request()->no_reference;

            $customer = null;
            $orders = Order::where('no_reference', $no_reference)->with(['progress_active'])->get();
            foreach ($orders as $order) {
                if (in_array($order->progress_active->status_code, ['00'])) {
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
                    $message_merchant = 'Ada pesanan masuk, silakan konfirmasi pesanan.';
                    $url_path_merchant = 'v1/seller/query/transaction/detail/' . $order->id;

                    $notificationCommand = new NotificationCommands();
                    $notificationCommand->create($column_name_merchant, $column_value_merchant, $type, $title_merchant, $message_merchant, $url_path_merchant);

                    $notificationCommand = new NotificationCommands();
                    $customer = Customer::where('merchant_id', $order->merchant_id)->first();
                    $notificationCommand->sendPushNotification($customer->id, $title_merchant, $message_merchant, 'active');

                    $customer = Customer::find($order->buyer_id);
                    $this->mailSenderManager->mailNewOrder($order->id);
                } else {
                    $response['response_code'] = '00';
                    $response['response_message'] = 'Sukses.';
                    $response['data'] = null;
                    return $response;
                }
            }

            $this->mailSenderManager->mailPaymentSuccess($order->id);

            //Request custom response BA ICP
            $response_ba['response_code'] = '00';
            $response_ba['response_message'] = 'Sukses.';
            $response_ba['data'] = null;
            return $response_ba;
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getDeliveryDiscount()
    {
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

    public function getCustomerDiscount()
    {
        try {
            $discount = $this->transactionQueries->getCustomerDiscount(Auth::user()->id, Auth::user()->email);
            return $this->respondWithData($discount, 'Data diskon customer berhasil didapatkan');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function countCheckoutPrice()
    {
        $validator = Validator::make(request()->all(), [
            'merchants' => 'required|array',
            'merchants.*.merchant_id' => 'required',
            //            'merchants.*.delivery_method' => 'required',
            'merchants.*.delivery_fee' => 'required',
            'merchants.*.delivery_discount' => 'required',
            'merchants.*.products' => 'required|array',
            'merchants.*.products.*.product_id' => 'required',
            'merchants.*.products.*.quantity' => 'required',
            'merchants.*.products.*.payment_note' => 'sometimes',
        ], [
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

        try {
            $customer = Auth::user();
            return $this->transactionQueries->countCheckoutPrice($customer, request()->all());
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function countCheckoutPriceV2()
    {
        $rules = [
            'merchants' => 'required|array',
            'merchants.*.merchant_id' => 'required',
            // 'merchants.*.delivery_method' => 'required',
            'merchants.*.delivery_fee' => 'required',
            'merchants.*.delivery_discount' => 'required',
            'merchants.*.products' => 'required|array',
            'merchants.*.products.*.product_id' => 'required',
            'merchants.*.products.*.quantity' => 'required',
            'merchants.*.products.*.payment_note' => 'sometimes',
        ];

        if (isset(request()->all()['customer'])) {
            $rules['customer.nik'] = 'required';
        }

        $validator = Validator::make(request()->all(), $rules, [
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

        try {
            $customer = Auth::user();
            $request = request()->all();
            $respond = $this->transactionQueries->countCheckoutPriceV2($customer, $request);

            if (isset($request['customer']) && data_get($request, 'customer') != null){
                $ev_subsidies = [];
                foreach ($respond['merchants'] as $merchant) {
                    foreach ($merchant['products'] as $product) {
                        if ($product['ev_subsidy'] != null) {
                            if ($product['quantity'] > 1) {
                                return array_merge($respond, [
                                    'success' => true,
                                    'status_code' => 400,
                                    'message' => 'Anda tidak dapat melakukan pembelian lebih dari 1 produk kendaraan listrik berinsentif'
                                ]);
                            }

                            $ev_subsidies[] = $product['ev_subsidy'];
                        }
                    }
                }

                if (count($ev_subsidies) > 1) {
                    return array_merge($respond, [
                        'success' => true,
                        'status_code' => 400,
                        'message' => 'Anda tidak dapat melakukan pembelian lebih dari 1 produk kendaraan listrik berinsentif'
                    ]);
                }
            }

            return $respond;
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function countCheckoutPriceV3()
    {
        $validator = Validator::make(request()->all(), [
            'merchants' => 'required|array',
            'merchants.*.merchant_id' => 'required',
            // 'merchants.*.delivery_method' => 'required',
            'merchants.*.delivery_fee' => 'required',
            'merchants.*.delivery_discount' => 'required',
            'merchants.*.products' => 'required|array',
            'merchants.*.products.*.product_id' => 'required',
            'merchants.*.products.*.quantity' => 'required',
            'merchants.*.products.*.payment_note' => 'sometimes',
            // 'destination_info.province_id' => 'required'
        ], [
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

        try {
            $customer = Auth::user();

            // $response['success'] = true;
            // $response['message'] = 'Berhasil resend email';
            // $response['data'] = $this->transactionQueries->countCheckoutPriceV3($customer, request()->all());

            return $this->transactionQueries->countCheckoutPriceV3($customer, request()->all());
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function countCheckoutPriceV4()
    {
        $rules = [
            'merchants' => 'required|array',
            'merchants.*.merchant_id' => 'required',
            // 'merchants.*.delivery_method' => 'required',
            'merchants.*.delivery_fee' => 'required',
            'merchants.*.delivery_discount' => 'required',
            'merchants.*.products' => 'required|array',
            'merchants.*.products.*.product_id' => 'required',
            'merchants.*.products.*.quantity' => 'required',
            'merchants.*.products.*.payment_note' => 'sometimes',
        ];

        if (isset(request()->all()['customer'])) {
            $rules['customer.nik'] = 'required';
        }

        $validator = Validator::make(request()->all(), $rules, [
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

        try {
            $customer = Auth::user();
            $respond = $this->transactionQueries->countCheckoutPriceV2($customer, request()->all());

            $ev_subsidies = [];
            foreach ($respond['merchants'] as $merchant) {
                foreach ($merchant['products'] as $product) {
                    if ($product['ev_subsidy'] != null) {
                        if ($product['quantity'] > 1) {
                            return array_merge($respond, [
                                'success' => true,
                                'status_code' => 400,
                                'message' => 'Anda tidak dapat melakukan pembelian lebih dari 1 produk kendaraan listrik berinsentif'
                            ]);
                        }

                        $ev_subsidies[] = $product['ev_subsidy'];
                    }
                }
            }

            if (count($ev_subsidies) > 1) {
                return array_merge($respond, [
                    'success' => true,
                    'status_code' => 400,
                    'message' => 'Anda tidak dapat melakukan pembelian lebih dari 1 produk kendaraan listrik berinsentif'
                ]);
            }

            return $respond;
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function retryVoucher($order_id)
    {
        try {
            DB::beginTransaction();
            $order = Order::with(['buyer', 'detail', 'progress_active', 'payment'])->find($order_id);
            $orders = Order::with(['delivery'])->where('no_reference', $order->no_reference)->get();
            $total_amount_trx = $total_delivery_fee_trx = 0;

            foreach ($orders as $o) {
                $total_amount_trx += $o->total_amount;
                $total_delivery_fee_trx += $o->delivery->delivery_fee;
            }

            if ($order->voucher_ubah_daya_code == null && ($total_amount_trx - $total_delivery_fee_trx) >= 100000) {
                $this->voucherCommand->generateVoucher($order);
            }

            DB::commit();
            $mailSender = new MailSenderManager();
            $mailSender->mailResendVoucher($order_id);

            $response['success'] = true;
            $response['message'] = 'Berhasil retry voucher';

            return $response;
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respondErrorException($e, request());
        }
    }

    public function resendEmailVoucher($order_id)
    {
        try {
            $mailSender = new MailSenderManager();
            $mailSender->mailResendVoucher($order_id);

            $response['success'] = true;
            $response['message'] = 'Berhasil resend email';

            return $response;
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function refundOngkir($id)
    {
        try {
            DB::beginTransaction();
            $this->transactionCommand->updateOrderStatus($id, '98', 'refund ongkir');

            $order = Order::with('delivery')->find($id);
            $iconcash = Customer::where('merchant_id', $order->merchant_id)->first()->iconcash;
            $account_type_id = null;

            if (env('APP_ENV') == 'staging') {
                $account_type_id = 13;
            } elseif (env('APP_ENV') == 'production') {
                $account_type_id = 50;
            } else {
                $account_type_id = 13;
            }

            $amount = $order->delivery->delivery_fee;
            $client_ref = $this->unique_code($iconcash->token);
            $corporate_id = 10;

            $topup_inquiry = IconcashInquiry::createTopupInquiry($iconcash, $account_type_id, $amount, $client_ref, $corporate_id, $order);

            IconcashManager::topupConfirm($topup_inquiry->orderId, $topup_inquiry->amount);
            DB::commit();
            return $this->respondWithResult(true, 'Topup refund ongkir berhasil!', 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respondErrorException($e, request());
        }
    }

    public function unique_code($value)
    {
        return substr(base_convert(sha1(uniqid($value)), 16, 36), 0, 25);
    }

    public function generateAwbNumber(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'order_ids' => 'required|array',
        ], [
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

        try {
            DB::beginTransaction();
            $results = [];
            foreach ($request->order_ids as $order_id) {
                $data = $this->transactionQueries->getStatusOrder($order_id, true)->load('merchant');
                if ($data != null) {
                    $status_codes = [];
                    foreach ($data->progress as $item) {
                        if (in_array($item->status_code, ['01', '02'])) {
                            $status_codes[] = $item;
                        }
                    }

                    $tiket = null;
                    $status_code = collect($status_codes)->where('status_code', '02')->first();
                    if ($status_code['status_code'] == '02' && $status_code['status'] == 1) {
                        $response = $this->transactionCommand->generateResi($order_id);
                        if (count($request->order_ids) == 1 && $response['success'] == false) {
                            return $response;
                        }

                        $status = $this->transactionCommand->updateOrderStatus($order_id, '03');
                        if (count($request->order_ids) == 1 && $status['success'] == false) {
                            return $status;
                        }

                        // dikomen untuk SIT
                        $title = 'Pesanan Dikirim';
                        $message = 'Pesanan anda sedang dalam pengiriman.';
                        $order = Order::with(['buyer', 'detail', 'progress_active', 'payment'])->find($order_id);
                        // $this->notificationCommand->sendPushNotification($order->buyer->id, $title, $message, 'active');
                        $this->notificationCommand->sendPushNotificationCustomerPlnMobile($order->buyer->id, $title, $message);

                        $mailSender = new MailSenderManager();
                        $mailSender->mailOrderOnDelivery($order_id);

                        $delivery = OrderDelivery::where('order_id', $order_id)->first();
                        $results[] = [
                            'success' => $delivery->awb_number != null ? true : false,
                            'message' => $delivery->awb_number != null ? 'Berhasil menambahkan resi' : 'Gagal menambahkan resi',
                            'trx_no' => $data->trx_no,
                            'awb_number' => $delivery->awb_number,
                            'no_reference' => $delivery->no_reference,
                        ];
                    } else {
                        if (count($request->order_ids) == 1) {
                            return $this->respondWithResult(false, 'Pesanan ' . $order_id . ' tidak dalam status siap dikirim!', 400);
                        } else {
                            $order = Order::with(['buyer', 'detail', 'progress_active', 'payment'])->find($order_id);
                            $delivery = OrderDelivery::where('order_id', $order_id)->first();
                            $results[] = [
                                'success' => false,
                                'message' => 'Pesanan tidak dalam status siap dikirim',
                                'trx_no' => $order->trx_no,
                                'awb_number' => null,
                                'no_reference' => null,
                            ];
                        }
                    }
                }
            }

            $message = 'Berhasil menambahkan resi';
            foreach ($results as $result) {
                if ($result['awb_number'] == null) {
                    $message = 'Berhasil menambahkan resi dan ada beberapa order yg gagal saat menambahkan resi';
                }
            }

            DB::commit();

            return $this->respondWithData($results, $message);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respondErrorException($e, request());
        }
    }
}

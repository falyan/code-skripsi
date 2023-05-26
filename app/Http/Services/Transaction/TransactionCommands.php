<?php

namespace App\Http\Services\Transaction;

use App\Http\Services\Manager\MailSenderManager;
use App\Http\Services\Notification\NotificationCommands;
use App\Http\Services\Service;
use App\Models\Customer;
use App\Models\CustomerDiscount;
use App\Models\CustomerEVSubsidy;
use App\Models\CustomerTiket;
use App\Models\MasterTiket;
use App\Models\Order;
use App\Models\OrderDelivery;
use App\Models\OrderDetail;
use App\Models\OrderPayment;
use App\Models\OrderProgress;
use App\Models\Product;
use App\Models\PromoLog;
use App\Models\PromoMaster;
use App\Models\PromoMerchant;
use App\Models\UserTiket;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LogService;

class TransactionCommands extends Service
{
    static $apiendpoint;
    static $appkey;
    static $curl;
    static $clientid;
    static $productid;
    static $appsource;
    static $header;
    protected $order_id = null;

    public static function init()
    {
        self::$curl = new Client();
        self::$apiendpoint = config('credentials.iconpay.endpoint');
        self::$appkey = config('credentials.iconpay.app_key');
        self::$clientid = config('credentials.iconpay.client_id');
        self::$productid = config('credentials.iconpay.product_id');
        self::$appsource = config('credentials.iconpay.app_source');
        self::$header = [
            'client-id' => static::$clientid,
            'appsource' => static::$appsource,
        ];
    }

    public function createOrder($datas, $customer)
    {
        DB::beginTransaction();
        try {
            $customer_id = $customer->id;
            $no_reference = (int) (Carbon::now('Asia/Jakarta')->timestamp . random_int(10000, 99999));

            while (static::checkReferenceExist($no_reference) == false) {
                $no_reference = (int) (Carbon::now('Asia/Jakarta')->timestamp . random_int(10000, 99999));
            }

            $timestamp = Carbon::now('Asia/Jakarta')->toIso8601String();
            $trx_date = date('Y/m/d H:i:s', Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now('Asia/Jakarta'))->timestamp);
            $exp_date = date('Y/m/d H:i:s', Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now('Asia/Jakarta')->addDays(7))->timestamp);

            foreach ($datas['merchants'] as $m) {
                if (isset($m['is_npwp_required']) && $m['is_npwp_required'] === true) {
                    if (!isset($datas['npwp']) || empty($datas['npwp'])) {
                        return [
                            'success' => false,
                            'status' => "Bad request",
                            'status_code' => 400,
                            'message' => 'Validation Error!',
                            'data' => [
                                'npwp diperlukan',
                            ],
                        ];
                    }
                }
            }

            array_map(function ($data) use ($datas, $customer_id, $no_reference, $trx_date, $exp_date, $district) {
                $order = new Order();
                $order->merchant_id = data_get($data, 'merchant_id');
                $order->buyer_id = $customer_id;
                $order->trx_no = static::invoice_num(static::nextOrderId(), 9, "INVO/" . Carbon::now()->year . Carbon::now()->month . Carbon::now()->day . "/MKP/");
                $order->order_date = date('Y/m/d H:i:s', Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now())->setTimezone('Asia/Jakarta')->timestamp);
                $order->total_amount = data_get($data, 'total_amount');
                $order->total_weight = data_get($data, 'total_weight');
                $order->related_pln_mobile_customer_id = null;
                $order->no_reference = $no_reference;
                $order->discount = data_get($data, 'product_discount');
                $order->npwp = data_get($data, 'npwp');
                $order->created_by = 'user';
                $order->updated_by = 'user';
                $order->npwp = data_get($datas, 'npwp');
                $order->save();

                if (isset($datas['save_npwp'])) {
                    $datas['save_npwp'] = in_array($datas['save_npwp'], [1, true]) ? true : false;
                }
                if (isset($datas['save_npwp']) && $datas['save_npwp'] === true) {
                    Customer::where('id', $customer_id)->update(['npwp' => $datas['npwp']]);
                }

                $this->order_id = $order->id;
                $order->trx_no = static::invoice_num($order->id, 9, "INVO/" . Carbon::now()->year . Carbon::now()->month . Carbon::now()->day . "/MKP/");
                $order->save();

                array_map(function ($product) use ($order) {
                    $order_detail = new OrderDetail();
                    $order_detail->order_id = $order->id;
                    $order_detail->detail_type = 1;
                    $order_detail->product_id = data_get($product, 'product_id');
                    $order_detail->quantity = data_get($product, 'quantity');
                    $order_detail->price = data_get($product, 'price');
                    $order_detail->weight = data_get($product, 'weight');
                    $order_detail->insurance_cost = data_get($product, 'insurance_cost');
                    $order_detail->discount = data_get($product, 'discount');
                    $order_detail->total_price = data_get($product, 'total_price');
                    $order_detail->total_weight = data_get($product, 'total_weight');
                    $order_detail->total_discount = data_get($product, 'total_discount');
                    $order_detail->total_insurance_cost = data_get($product, 'total_insurance_cost');
                    $order_detail->total_amount = data_get($product, 'total_amount');
                    $order_detail->notes = data_get($product, 'note');
                    $order_detail->variant_value_product_id = data_get($product, 'variant_value_product_id');
                    $order_detail->save();
                }, data_get($data, 'products'));

                $order_progress = new OrderProgress();
                $order_progress->order_id = $order->id;
                $order_progress->status_code = '00';
                $order_progress->status_name = 'Pesanan Belum Dibayar';
                $order_progress->note = null;
                $order_progress->status = 1;
                $order_progress->created_by = 'user';
                $order_progress->updated_by = 'user';
                $order_progress->save();

                $order_delivery = new OrderDelivery();
                $order_delivery->order_id = $order->id;
                $order_delivery->receiver_name = data_get($datas, 'destination_info.receiver_name');
                $order_delivery->receiver_phone = data_get($datas, 'destination_info.receiver_phone');
                $order_delivery->address = data_get($datas, 'destination_info.address');
                $order_delivery->city_id = data_get($datas, 'destination_info.city_id');
                $order_delivery->district_id = data_get($datas, 'destination_info.district_id');
                $order_delivery->postal_code = data_get($datas, 'destination_info.postal_code');
                $order_delivery->latitude = data_get($datas, 'destination_info.latitude');
                $order_delivery->longitude = data_get($datas, 'destination_info.longitude');
                $order_delivery->shipping_type = data_get($data, 'delivery_service');
                $order_delivery->awb_number = null;

                //J&T Courier Validation
                if (data_get($data, 'delivery_method') == 'J&T') {
                    data_set($data, 'delivery_method', 'jnt');
                }

                $order_delivery->delivery_method = data_get($data, 'delivery_method');
                $order_delivery->delivery_fee = data_get($data, 'delivery_fee');
                $order_delivery->delivery_discount = data_get($data, 'delivery_discount');
                $order_delivery->save();

                $order_payment = new OrderPayment();
                $order_payment->customer_id = $customer_id;
                $order_payment->payment_amount = data_get($data, 'total_payment');
                $order_payment->date_created = $trx_date;
                $order_payment->date_expired = $exp_date;
                $order_payment->payment_method = null;
                $order_payment->no_reference = $no_reference;
                $order_payment->booking_code = null;
                $order_payment->payment_note = data_get($data, 'payment_note') ?? null;
                $order_payment->status = 0;
                $order_payment->save();

                $order->payment_id = $order_payment->id;
                if ($order->save()) {
                    $column_name = 'customer_id';
                    $column_value = $customer_id;
                    $type = 2;
                    $title = 'Transaksi berhasil dibuat';
                    $message = 'Transaksimu berhasil dibuat, silakan melanjutkan pembayaran.';
                    $url_path = 'v1/buyer/query/transaction/' . $customer_id . '/detail/' . $order->id;

                    $notificationCommand = new NotificationCommands();
                    $notificationCommand->create($column_name, $column_value, $type, $title, $message, $url_path);
                }
            }, data_get($datas, 'merchants'));

            if ($datas['total_payment'] < 1) {
                throw new Exception('Total pembayaran harus lebih dari 0 rupiah');
            }

            $mailSender = new MailSenderManager();
            $mailSender->mailCheckout($this->order_id);

            if ($datas['total_discount'] > 0) {
                $update_discount = $this->updateCustomerDiscount($customer_id, $customer->email, $datas['total_discount'], $no_reference);
                if ($update_discount == false) {
                    throw new Exception('Gagal mengupdate customer discount');
                }
            }

            $url = sprintf('%s/%s', static::$apiendpoint, 'booking');
            $body = [
                'no_reference' => $no_reference,
                'transaction_date' => $trx_date,
                'transaction_code' => '00',
                'partner_reference' => $no_reference,
                'product_id' => static::$productid,
                'amount' => $datas['total_payment'],
                'customer_id' => $no_reference,
                'customer_name' => $customer->full_name,
                'email' => $customer->email,
                'phone_number' => $customer->phone,
                'expired_invoice' => $exp_date,
            ];

            $encode_body = json_encode($body, JSON_UNESCAPED_SLASHES);

            static::$header['timestamp'] = $timestamp;
            static::$header['signature'] = hash_hmac('sha256', $encode_body . static::$clientid . $timestamp, sha1(static::$appkey));
            static::$header['content-type'] = 'application/json';

            $response = static::$curl->request('POST', $url, [
                'headers' => static::$header,
                'http_errors' => false,
                'body' => $encode_body,
            ]);

            $response = json_decode($response->getBody());

            LogService::setUrl($url)->setRequest($body)->setResponse($response)->setServiceCode('iconpay')->setCategory('out')->log();

            throw_if(!$response, Exception::class, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh', 500));

            if ($response->response_details[0]->response_code != 00) {
                throw new Exception($response->response_details[0]->response_message);
            }

            $response->response_details[0]->amount = $datas['total_payment'];
            $response->response_details[0]->customer_id = (int) $response->response_details[0]->customer_id;
            $response->response_details[0]->partner_reference = (int) $response->response_details[0]->partner_reference;

            DB::commit();

            $product_name = OrderDetail::with('product')->where('order_id', $this->order_id)->first()->product->name;
            //add order id to response
            $response->order_id = $this->order_id;
            //get product name from order detail
            $response->product_name = $product_name;

            return [
                'success' => true,
                'message' => 'Berhasil create order',
                'data' => $response,
            ];
        } catch (Exception $th) {
            DB::rollBack();
            if (in_array($th->getCode(), self::$error_codes)) {
                throw new Exception($th->getMessage(), $th->getCode());
            }
            throw new Exception($th->getMessage(), 500);
        }
    }

    // Create Order V2
    public function createOrderV2($datas, $customer)
    {
        DB::beginTransaction();
        try {
            $customer_id = $customer->id;
            $no_reference = (int) (Carbon::now('Asia/Jakarta')->timestamp . random_int(10000, 99999));

            $city_id = data_get($datas, 'destination_info.city_id');
            $province_id = City::where('id', $city_id)->first()->province_id;
            $district = District::where('id', data_get($datas, 'destination_info.district_id'))->first();

            while (static::checkReferenceExist($no_reference) == false) {
                $no_reference = (int) (Carbon::now('Asia/Jakarta')->timestamp . random_int(10000, 99999));
            }

            $timestamp = Carbon::now('Asia/Jakarta')->toIso8601String();
            $trx_date = date('Y/m/d H:i:s', Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now('Asia/Jakarta'))->timestamp);
            $exp_date = date('Y/m/d H:i:s', Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now('Asia/Jakarta')->addDays(1))->timestamp);

            foreach ($datas['merchants'] as $m) {
                if (isset($m['is_npwp_required']) && $m['is_npwp_required'] === true) {
                    if (!isset($datas['npwp']) || empty($datas['npwp'])) {
                        return [
                            'success' => false,
                            'status' => "Bad request",
                            'status_code' => 400,
                            'message' => 'Validation Error!',
                            'data' => [
                                'npwp diperlukan',
                            ],
                        ];
                    }
                }
            }

            $ev_subsidies = [];
            if (isset($datas['customer']) && data_get($datas, 'customer') != null) {
                foreach ($datas['merchants'] as $merchant) {
                    foreach ($merchant['products'] as $product) {
                        $ev_subsidy = Product::with('ev_subsidy')->where('id', $product['product_id'])->first()->ev_subsidy;

                        if ($ev_subsidy) {
                            if ($product['quantity'] > 1) {
                                return [
                                    'success' => false,
                                    'status' => "Bad request",
                                    'status_code' => 400,
                                    'message' => 'Anda tidak dapat melakukan pembelian lebih dari 1 produk kendaraan listrik berinsentif',
                                ];
                            }

                            $ev_subsidies[] = $ev_subsidy;
                        }
                    }
                }

                if (count($ev_subsidies) > 1) {
                    return [
                        'success' => false,
                        'status' => "Bad request",
                        'status_code' => 400,
                        'message' => 'Anda tidak dapat melakukan pembelian lebih dari 1 produk kendaraan listrik berinsentif',
                    ];
                }
            }

            // validasi tiket
            $master_tikets = MasterTiket::with(['master_data'])->where('status', 1)->get();
            $customer_tiket = Order::where('buyer_id', $customer->id)
                ->whereHas('progress_active', function($q) {
                    $q->whereIn('status_code', ['00', '01', '02', '03','08', '88']);
                })
                ->whereHas('detail', function($q) use ($master_tikets) {
                    $q->whereHas('product', function($q) use ($master_tikets) {
                        $q->whereIn('category_id', collect($master_tikets)->pluck('master_data.id')->toArray());
                    });
                })->get();

            $count_tiket = collect($customer_tiket)->count();
            $new_product = collect($datas['merchants'])->pluck('products')->flatten(1)->toArray();
            foreach ($new_product as $product) {
                $product = Product::where('id', $product['product_id'])->first();
                $tiket = collect($master_tikets)->where('master_data.id', $product['category_id'])->first();

                if($tiket) {
                    $count_tiket += $product['quantity'];
                }
            }

            if ($count_tiket > 4) {
                return [
                    'success' => false,
                    'status' => "Bad request",
                    'status_code' => 400,
                    'message' => 'Anda tidak dapat melakukan pembelian lebih dari 4 tiket',
                ];
            }

            foreach (data_get($datas, 'merchants') as $data) {
                $order = new Order();
                $order->merchant_id = data_get($data, 'merchant_id');
                $order->buyer_id = $customer_id;
                $order->trx_no = static::invoice_num(static::nextOrderId(), 9, "INVO/" . Carbon::now()->year . Carbon::now()->month . Carbon::now()->day . "/MKP/");
                $order->order_date = date('Y/m/d H:i:s', Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now())->setTimezone('Asia/Jakarta')->timestamp);
                $order->total_amount = data_get($data, 'total_amount');
                $order->total_weight = data_get($data, 'total_weight');
                $order->related_pln_mobile_customer_id = null;
                $order->no_reference = $no_reference;
                $order->discount = data_get($data, 'product_discount');
                $order->npwp = data_get($data, 'npwp');
                $order->created_by = 'user';
                $order->updated_by = 'user';
                $order->npwp = data_get($datas, 'npwp');
                $order->save();

                if (isset($datas['save_npwp'])) {
                    $datas['save_npwp'] = in_array($datas['save_npwp'], [1, true]) ? true : false;
                }
                if (isset($datas['save_npwp']) && $datas['save_npwp'] === true) {
                    Customer::where('id', $customer_id)->update(['npwp' => $datas['npwp']]);
                }

                $this->order_id = $order->id;
                $order->trx_no = static::invoice_num($order->id, 9, "INVO/" . Carbon::now()->year . Carbon::now()->month . Carbon::now()->day . "/MKP/");
                $order->save();

                $order_details = [];
                foreach (data_get($data, 'products') as $product) {
                    $order_details[] = [
                        'order_id' => $order->id,
                        'detail_type' => 1,
                        'product_id' => data_get($product, 'product_id'),
                        'quantity' => data_get($product, 'quantity'),
                        'price' => data_get($product, 'price'),
                        'weight' => data_get($product, 'weight'),
                        'insurance_cost' => data_get($product, 'insurance_cost'),
                        'discount' => data_get($product, 'discount'),
                        'total_price' => data_get($product, 'total_price'),
                        'total_weight' => data_get($product, 'total_weight'),
                        'total_discount' => data_get($product, 'total_discount'),
                        'total_insentif' => data_get($product, 'total_insentif'),
                        'total_insurance_cost' => data_get($product, 'total_insurance_cost'),
                        'total_amount' => data_get($product, 'total_amount'),
                        'notes' => data_get($product, 'note'),
                        'variant_value_product_id' => data_get($product, 'variant_value_product_id'),
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ];
                }

                // sementara ketika flash sale nempel merchant
                // OrderDetail::insert($order_details);

                $order_progress = new OrderProgress();
                $order_progress->order_id = $order->id;
                $order_progress->status_code = '00';
                $order_progress->status_name = 'Pesanan Belum Dibayar';
                $order_progress->note = null;
                $order_progress->status = 1;
                $order_progress->created_by = 'user';
                $order_progress->updated_by = 'user';
                $order_progress->save();

                $promo_merchant = PromoMerchant::with(['promo_master', 'promo_master.promo_regions', 'promo_master.promo_values'])
                    ->where([
                        'merchant_id' => data_get($data, 'merchant_id'),
                        'status' => 1,
                    ])
                    ->where('start_date', '<=', date('Y-m-d H:i:s'))
                    ->where('end_date', '>=', date('Y-m-d H:i:s'))
                    ->whereHas('promo_master', function ($query) {
                        $query->where('status', 1);
                    })
                    ->get();

            $count_tiket = 0;
            foreach ($customer_tiket as $order) {
                foreach ($order->detail as $detail) {
                    $tiket = collect($master_tikets)->where('master_data.id', $detail->product->category_id)->first();

                    if ($tiket) {
                        $count_tiket += $detail->quantity;
                    }
                }
            }

            $new_products = [];
            foreach ($datas['merchants'] as $merchant) {
                foreach ($merchant['products'] as $product) {
                    $new_product = Product::where('id', data_get($product, 'product_id'))->first();
                    $new_product['quantity'] = data_get($product, 'quantity');
                    $new_products[] = $new_product;
                }
            }

            $buying_tiket = false;
            foreach ($new_products as $product) {
                $tiket = collect($master_tikets)->where('master_data.id', $product['category_id'])->first();

                if ($tiket) {
                    $count_tiket += $product['quantity'];

                    $buying_tiket = true;
                }

                $promo_merchant_flash_sale = null;
                $promo_flash_sale_value = null;
                $value_flash_sale = 0;
                if (data_get($data, 'can_flash_sale_discount') == true) {
                    foreach ($promo_merchant as $promo) {
                        if ($promo['promo_master']['event_type'] == 'flash_sale') {
                            $promo_merchant_flash_sale = $promo;
                            $value_flash_sale_m = 0;

                            $value_flash_sale_m = $promo['promo_master']['value_1'];
                            if ($promo['promo_master']['promo_value_type'] == 'percentage') {
                                $value_flash_sale_m = $order->total_amount * ($promo_merchant_flash_sale->promo_master->value_1 / 100);
                                if ($value_flash_sale_m >= $promo['promo_master']['max_discount_value']) {
                                    $value_flash_sale_m = $promo['promo_master']['max_discount_value'];
                                }
                            }

                            foreach ($promo['promo_master']['promo_values'] as $promo_value) {
                                $value_flash_sale_m = $promo['promo_master']['value_1'];
                                if ($promo['promo_master']['promo_value_type'] == 'percentage') {
                                    $value_flash_sale_m = $order->total_amount * ($promo_merchant_flash_sale->promo_master->value_1 / 100);
                                    if ($value_flash_sale_m >= $promo['promo_master']['max_discount_value']) {
                                        $value_flash_sale_m = $promo['promo_master']['max_discount_value'];
                                    }
                                }

                                if ($order->total_amount >= $promo_value['min_value'] && $order->total_amount <= $promo_value['max_value'] && $promo_value['status'] == 1) {
                                    $promo_flash_sale_value = $promo_value;

                                    if ($value_flash_sale_m >= $promo_value['max_discount_value']) {
                                        $value_flash_sale_m = $promo_value['max_discount_value'];
                                    }

                                    break;
                                }
                            }

                            $max_merchant = ($promo['usage_value'] + $value_flash_sale_m) > $promo['max_value'];
                            $max_master = ($promo['promo_master']['usage_value'] + $value_flash_sale_m) > $promo['promo_master']['max_value'];

                            if ($max_merchant && !$max_master) {
                                $value_flash_sale = $value_flash_sale_m;
                                break;
                            }

                            if (!$max_merchant && $max_master) {
                                $value_flash_sale = $value_flash_sale_m;
                                break;
                            }

                            if (!$max_merchant && !$max_master) {
                                $value_flash_sale = $value_flash_sale_m;
                                break;
                            }
                        }
                    }
                }

                $min_condition = false;
                if ($promo_flash_sale_value != null) {
                    $min_condition = true;
                } else {
                    if ($promo_merchant_flash_sale != null) {
                        $min_condition = $promo_merchant_flash_sale['promo_master']['min_order_value'] <= $order->total_amount;
                    }
                }

                if ($promo_merchant_flash_sale != null && $min_condition) {
                    if ($value_flash_sale > $order->total_amount) {
                        $value_flash_sale = $order->total_amount;
                    }

                    $promo_merchant_flash_sale = PromoMerchant::find($promo_merchant_flash_sale['id']);
                    $promo_merchant_flash_sale->usage_value = $promo_merchant_flash_sale->usage_value + $value_flash_sale;
                    $promo_merchant_flash_sale->save();

                    $promo_master = PromoMaster::find($promo_merchant_flash_sale['promo_master']['id']);
                    $promo_master->usage_value = $promo_master->usage_value + $value_flash_sale;
                    $promo_master->save();

                    $promo_log = new PromoLog();
                    $promo_log->order_id = $order->id;
                    $promo_log->promo_master_id = $promo_master->id;
                    $promo_log->promo_merchant_id = $promo_merchant_flash_sale->id;
                    $promo_log->type = 'sub';
                    $promo_log->value = $value_flash_sale;
                    $promo_log->created_by = 'System';
                    $promo_log->save();

                    // sementara ketika flash sale nempel merchant
                    $order_details[0]['discount'] = $order_details[0]['discount'] + $value_flash_sale;
                    $order_details[0]['total_discount'] = $order_details[0]['total_discount'] + $value_flash_sale;
                    $order_details[0]['total_amount'] = $order_details[0]['total_amount'] - $value_flash_sale;
                }

                // sementara ketika flash sale nempel merchant
                OrderDetail::insert($order_details);

                $order_delivery = new OrderDelivery();
                $order_delivery->order_id = $order->id;
                $order_delivery->receiver_name = data_get($datas, 'destination_info.receiver_name');
                $order_delivery->receiver_phone = data_get($datas, 'destination_info.receiver_phone');
                $order_delivery->address = data_get($datas, 'destination_info.address');
                $order_delivery->city_id = data_get($datas, 'destination_info.city_id');
                $order_delivery->district_id = data_get($datas, 'destination_info.district_id');
                $order_delivery->postal_code = data_get($datas, 'destination_info.postal_code');
                $order_delivery->latitude = data_get($datas, 'destination_info.latitude');
                $order_delivery->longitude = data_get($datas, 'destination_info.longitude');
                $order_delivery->shipping_type = data_get($data, 'delivery_service');
                $order_delivery->awb_number = null;
                $order_delivery->district_code = $district != null ? $district->district_code : null;

                //J&T Courier Validation
                if (data_get($data, 'delivery_method') == 'J&T') {
                    data_set($data, 'delivery_method', 'jnt');
                }

            if ($count_tiket > 4 && $buying_tiket) {
                return [
                    'success' => false,
                    'status' => "Bad request",
                    'status_code' => 400,
                    'message' => 'Anda telah mencapai batas pembelian tiket',
                ];
            }

            foreach (data_get($datas, 'merchants') as $data) {
                $order = new Order();
                $order->merchant_id = data_get($data, 'merchant_id');
                $order->buyer_id = $customer_id;
                $order->trx_no = static::invoice_num(static::nextOrderId(), 9, "INVO/" . Carbon::now()->year . Carbon::now()->month . Carbon::now()->day . "/MKP/");
                $order->order_date = date('Y/m/d H:i:s', Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now())->setTimezone('Asia/Jakarta')->timestamp);
                $order->total_amount = data_get($data, 'total_amount');
                $order->total_weight = data_get($data, 'total_weight');
                $order->related_pln_mobile_customer_id = null;
                $order->no_reference = $no_reference;
                $order->discount = data_get($data, 'product_discount');
                $order->npwp = data_get($data, 'npwp');
                $order->created_by = 'user';
                $order->updated_by = 'user';
                $order->npwp = data_get($datas, 'npwp');
                $order->save();

                if (isset($datas['save_npwp'])) {
                    $datas['save_npwp'] = in_array($datas['save_npwp'], [1, true]) ? true : false;
                }
                if (isset($datas['save_npwp']) && $datas['save_npwp'] === true) {
                    Customer::where('id', $customer_id)->update(['npwp' => $datas['npwp']]);
                }

                $this->order_id = $order->id;
                $order->trx_no = static::invoice_num($order->id, 9, "INVO/" . Carbon::now()->year . Carbon::now()->month . Carbon::now()->day . "/MKP/");
                $order->save();

                $order_details = [];
                foreach (data_get($data, 'products') as $product) {
                    $order_details[] = [
                        'order_id' => $order->id,
                        'detail_type' => 1,
                        'product_id' => data_get($product, 'product_id'),
                        'quantity' => data_get($product, 'quantity'),
                        'price' => data_get($product, 'price'),
                        'weight' => data_get($product, 'weight'),
                        'insurance_cost' => data_get($product, 'insurance_cost'),
                        'discount' => data_get($product, 'discount'),
                        'total_price' => data_get($product, 'total_price'),
                        'total_weight' => data_get($product, 'total_weight'),
                        'total_discount' => data_get($product, 'total_discount'),
                        'total_insentif' => data_get($product, 'total_insentif'),
                        'total_insurance_cost' => data_get($product, 'total_insurance_cost'),
                        'total_amount' => data_get($product, 'total_amount'),
                        'notes' => data_get($product, 'note'),
                        'variant_value_product_id' => data_get($product, 'variant_value_product_id'),
                        'created_at' => Carbon::now(),
                    ];
                }

                // sementara ketika flash sale nempel merchant
                // OrderDetail::insert($order_details);

                $order_progress = new OrderProgress();
                $order_progress->order_id = $order->id;
                $order_progress->status_code = '00';
                $order_progress->status_name = 'Pesanan Belum Dibayar';
                $order_progress->note = null;
                $order_progress->status = 1;
                $order_progress->created_by = 'user';
                $order_progress->updated_by = 'user';
                $order_progress->save();

                $promo_merchant = PromoMerchant::with(['promo_master', 'promo_master.promo_regions', 'promo_master.promo_values'])
                    ->where([
                        'merchant_id' => data_get($data, 'merchant_id'),
                        'status' => 1,
                    ])
                    ->where('start_date', '<=', date('Y-m-d H:i:s'))
                    ->where('end_date', '>=', date('Y-m-d H:i:s'))
                    ->whereHas('promo_master', function ($query) {
                        $query->where('status', 1);
                    })
                    ->get();

                $promo_merchant_ongkir = null;
                $value_ongkir = 0;

                if (data_get($data, 'can_shipping_discount') == true && data_get($data, 'delivery_discount') > 0) {
                    foreach ($promo_merchant as $promo) {
                        if ($promo['promo_master']['event_type'] == 'ongkir') {
                            foreach ($promo['promo_master']['promo_regions'] as $region) {
                                $region_ids = collect($region['province_ids'])->toArray();
                                if (in_array($province_id, $region_ids)) {
                                    $promo_merchant_ongkir = $promo;
                                    $value_ongkir_m = 0;

                                    if ($region['value_type'] == 'value_2') {
                                        $value_ongkir_m = $promo['promo_master']['value_2'];
                                    } else {
                                        $value_ongkir_m = $promo['promo_master']['value_1'];
                                    }

                                    $max_merchant = ($promo['usage_value'] + $value_ongkir_m) > $promo['max_value'];
                                    $max_master = ($promo['promo_master']['usage_value'] + $value_ongkir_m) > $promo['promo_master']['max_value'];

                                    if ($max_merchant && !$max_master) {
                                        $value_ongkir = $value_ongkir_m;
                                        break;
                                    }

                                    if (!$max_merchant && $max_master) {
                                        $value_ongkir = $value_ongkir_m;
                                        break;
                                    }

                                    if (!$max_merchant && !$max_master) {
                                        $value_ongkir = $value_ongkir_m;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }

                if ($value_ongkir > data_get($data, 'delivery_fee')) {
                    $value_ongkir = data_get($data, 'delivery_fee');
                }

                if ($promo_merchant_ongkir != null && $promo_merchant_ongkir['promo_master']['min_order_value'] <= $order->total_amount) {
                    $promo_merchant_ongkir = PromoMerchant::find($promo_merchant_ongkir['id']);
                    $promo_merchant_ongkir->usage_value = $promo_merchant_ongkir->usage_value + $value_ongkir;
                    $promo_merchant_ongkir->save();

                    $promo_master = PromoMaster::find($promo_merchant_ongkir['promo_master']['id']);
                    $promo_master->usage_value = $promo_master->usage_value + $value_ongkir;
                    $promo_master->save();

                    $promo_log = new PromoLog();
                    $promo_log->order_id = $order->id;
                    $promo_log->promo_master_id = $promo_master->id;
                    $promo_log->promo_merchant_id = $promo_merchant_ongkir->id;
                    $promo_log->type = 'sub';
                    $promo_log->value = $value_ongkir;
                    $promo_log->created_by = 'System';
                    $promo_log->save();
                }

                // sementara ketika flash sale nempel merchant
                OrderDetail::insert($order_details);

                $order_delivery = new OrderDelivery();
                $order_delivery->order_id = $order->id;
                $order_delivery->receiver_name = data_get($datas, 'destination_info.receiver_name');
                $order_delivery->receiver_phone = data_get($datas, 'destination_info.receiver_phone');
                $order_delivery->address = data_get($datas, 'destination_info.address');
                $order_delivery->city_id = data_get($datas, 'destination_info.city_id');
                $order_delivery->district_id = data_get($datas, 'destination_info.district_id');
                $order_delivery->postal_code = data_get($datas, 'destination_info.postal_code');
                $order_delivery->latitude = data_get($datas, 'destination_info.latitude');
                $order_delivery->longitude = data_get($datas, 'destination_info.longitude');
                $order_delivery->shipping_type = data_get($data, 'delivery_service');
                $order_delivery->awb_number = null;

                //J&T Courier Validation
                if (data_get($data, 'delivery_method') == 'J&T') {
                    data_set($data, 'delivery_method', 'jnt');
                }

                $order_delivery->delivery_method = data_get($data, 'delivery_method');
                $order_delivery->delivery_fee = data_get($data, 'delivery_fee');
                $order_delivery->delivery_discount = data_get($data, 'delivery_discount');
                $order_delivery->save();

                $order_payment = new OrderPayment();
                $order_payment->customer_id = $customer_id;
                $order_payment->payment_amount = data_get($data, 'total_payment');
                $order_payment->date_created = $trx_date;
                $order_payment->date_expired = $exp_date;
                $order_payment->payment_method = null;
                $order_payment->no_reference = $no_reference;
                $order_payment->booking_code = null;
                $order_payment->payment_note = data_get($data, 'payment_note') ?? null;
                $order_payment->status = 0;
                $order_payment->save();

                $order->payment_id = $order_payment->id;
                if ($order->save()) {
                    $column_name = 'customer_id';
                    $column_value = $customer_id;
                    $type = 2;
                    $title = 'Transaksi berhasil dibuat';
                    $message = 'Transaksimu berhasil dibuat, silakan melanjutkan pembayaran.';
                    $url_path = 'v1/buyer/query/transaction/' . $customer_id . '/detail/' . $order->id;

                    $notificationCommand = new NotificationCommands();
                    $notificationCommand->create($column_name, $column_value, $type, $title, $message, $url_path);
                }
            }

            if ($datas['total_payment'] < 1) {
                throw new Exception('Total pembayaran harus lebih dari 0 rupiah');
            }

            if (isset($datas['customer']) && data_get($datas, 'customer') != null) {
                $ev_subsidy = $ev_subsidies[0];
                foreach (data_get($data, 'products') as $product) {
                    if ($ev_subsidy->product_id == data_get($product, 'product_id')) {
                        $customerEv = new CustomerEVSubsidy();
                        $customerEv->customer_id = $customer_id;
                        $customerEv->order_id = $order->id;
                        $customerEv->product_id = $ev_subsidy->product_id;
                        $customerEv->status_approval = null;
                        $customerEv->customer_id_pel = $customer->pln_mobile_customer_id;
                        $customerEv->customer_nik = data_get($datas, 'customer.nik');
                        $customerEv->ktp_url = data_get($datas, 'customer.ktp_url');
                        $customerEv->kk_url = data_get($datas, 'customer.kk_url');
                        $customerEv->file_url = data_get($datas, 'customer.file_url');
                        $customerEv->created_by = auth()->user()->full_name;
                        $customerEv->save();
                    }
                }
            }

            $mailSender = new MailSenderManager();
            $mailSender->mailCheckout($this->order_id);

            if ($datas['total_discount'] > 0) {
                $update_discount = $this->updateCustomerDiscount($customer_id, $customer->email, $datas['total_discount'], $no_reference);
                if ($update_discount == false) {
                    throw new Exception('Gagal mengupdate customer discount');
                }
            }

            $url = sprintf('%s/%s', static::$apiendpoint, 'booking');
            $body = [
                'no_reference' => $no_reference,
                'transaction_date' => $trx_date,
                'transaction_code' => '00',
                'partner_reference' => $no_reference,
                'product_id' => static::$productid,
                'amount' => $datas['total_payment'],
                'customer_id' => $no_reference,
                'customer_name' => $customer->full_name,
                'email' => $customer->email,
                'phone_number' => $customer->phone,
                'expired_invoice' => $exp_date,
            ];

            $encode_body = json_encode($body, JSON_UNESCAPED_SLASHES);

            static::$header['timestamp'] = $timestamp;
            static::$header['signature'] = hash_hmac('sha256', $encode_body . static::$clientid . $timestamp, sha1(static::$appkey));
            static::$header['content-type'] = 'application/json';

            $response = static::$curl->request('POST', $url, [
                'headers' => static::$header,
                'http_errors' => false,
                'body' => $encode_body,
            ]);

            $response = json_decode($response->getBody());

            throw_if(!$response, Exception::class, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh', 500));

            if ($response->response_details[0]->response_code != 00) {
                throw new Exception($response->response_details[0]->response_message);
            }

            $response->response_details[0]->amount = $datas['total_payment'];
            $response->response_details[0]->customer_id = (int) $response->response_details[0]->customer_id;
            $response->response_details[0]->partner_reference = (int) $response->response_details[0]->partner_reference;

            DB::commit();

            $product_name = OrderDetail::with('product')->where('order_id', $this->order_id)->first()->product->name;
            //add order id to response
            $response->order_id = $this->order_id;
            //get product name from order detail
            $response->product_name = $product_name;

            return [
                'success' => true,
                'message' => 'Berhasil create order',
                'data' => $response,
            ];
        } catch (Exception $th) {
            DB::rollBack();
            if (in_array($th->getCode(), self::$error_codes)) {
                throw new Exception($th->getMessage(), $th->getCode());
            }
            throw new Exception($th->getMessage(), 500);
        }
    }

    public static function nextOrderId()
    {
        if (Order::count() < 1) {
            return 1;
        } else {
            $last_order_id = Order::orderBy('id', 'DESC')->first()->id;
            return ++$last_order_id;
        }
    }

    public function updateOrderStatus($order_id, $status_code, $note = null)
    {
        $old_order_progress = OrderProgress::where('order_id', $order_id)->get();
        $total = count($old_order_progress) ?? 0;
        if ($total >= 0) {
            for ($i = 0; $i < $total; $i++) {
                $old_order_progress[$i]->status = 0;
                $old_order_progress[$i]->save();
            }
        }

        $new_order_progress = new OrderProgress();
        $new_order_progress->order_id = $order_id;
        $new_order_progress->status_code = $status_code;
        $new_order_progress->status_name = parent::$status_order[$status_code];
        $new_order_progress->note = $note;
        $new_order_progress->status = 1;
        $new_order_progress->created_by = 'system';
        $new_order_progress->updated_by = 'system';

        if (!$new_order_progress->save()) {
            $response['success'] = false;
            $response['message'] = 'Gagal merubah status pesanan';
            return $response;
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil merubah status pesanan';
        $response['status_code'] = $status_code;
        return $response;
    }

    public function updateOrderStatusTiket($order_id, $status_codes = ['02', '03', '08'], $note = null)
    {
        $promo_merchant = PromoMerchant::find($promo_log_order->promo_merchant_id);
        $promo_merchant->usage_value = $promo_merchant->usage_value - (int) $promo_log_order->value;
        $promo_merchant->save();

        $promo_master = PromoMaster::find($promo_log_order->promo_master_id);
        $promo_master->usage_value = $promo_master->usage_value - (int) $promo_log_order->value;
        $promo_master->save();

        $create_order_progress = [];
        foreach ($status_codes as $status_code) {
            $create_order_progress[] = [
                'order_id' => $order_id,
                'status_code' => $status_code,
                'status_name' => parent::$status_order[$status_code],
                'note' => $note,
                'status' => $status_code == '08' ? 1 : 0,
                'created_by' => 'system',
                'updated_by' => 'system',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }

        if (!$new_order_progress->insert($create_order_progress)) {
            $response['success'] = false;
            $response['message'] = 'Gagal merubah status pesanan';
            return $response;
        }

        $awb = $this->addAwbNumberAuto($order_id);
        if (!$awb['success']) {
            $response['success'] = false;
            $response['message'] = $awb['message'];
            return $response;
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil merubah status pesanan';
        $response['status_code'] = $status_code;
        return $response;
    }

    public function triggerItemSold($order_id)
    {
        $order = Order::where('id', $order_id)->with('detail', function ($query) {
            $query->whereHas('progress_order', function ($query) {
                $query->where('status', 1)
                    ->where('status_code', '08');
            });
        })->first();

        $total_items = [];
        foreach ($order->detail as $detail) {
            if (!isset($total_items[$detail->product_id])) {
                $total_items[$detail->product_id] = $detail->quantity;
            } else {
                $total_items[$detail->product_id] += $detail->quantity;
            }
        }

        // $data = [];
        foreach ($total_items as $key => $value) {
            $product = Product::withTrashed()->where('id', $key)->first();

            if (!$product) {
                continue;
            }

            $product->items_sold += $value;
            $product->save();

            // $data[] = $product;
        }
    }

    public function triggerRatingProductSold($type)
    {
        if ($type == 'items_sold') {
            $orders = Order::with('detail')->whereHas('detail', function ($query) {
                $query->whereHas('progress_order', function ($query) {
                    $query->where('status', 1)
                        ->where('status_code', '88');
                });
            })->get();

            $total_items = [];
            foreach ($orders as $order) {
                foreach ($order->detail as $key => $detail) {
                    if (!isset($total_items[$detail->product_id])) {
                        $total_items[$detail->product_id] = $detail->quantity;
                    } else {
                        $total_items[$detail->product_id] += $detail->quantity;
                    }
                }
            }

            // return $total_items;

            foreach ($total_items as $key => $value) {
                $product = Product::find($key);
                if ($product) {
                    $product->items_sold = $value;
                    $product->save();
                }
            }
        } else if ($type == 'review_avg') {
            $products = Product::with('reviews')->get();

            $items = [];
            foreach ($products as $product) {
                $rate = 0;
                $review_count = 0;
                foreach ($product->reviews as $key => $value) {
                    $rate += $value->rate;
                    $review_count++;
                }
                if ($review_count > 0 || $rate > 0) {
                    $items[$product->id] = [
                        'avg_rating' => (int) ($rate / $review_count),
                        'review_count' => $review_count,
                    ];
                }
            }

            foreach ($items as $key => $value) {
                $product = Product::find($key);
                if ($product) {
                    $product->update($value);
                }
            }
        }

        return [
            'success' => true,
            'message' => 'Berhasil trigger rating product',
        ];
    }

    public function addAwbNumber($order_id, $awb)
    {
        $delivery = OrderDelivery::where('order_id', $order_id)->first();
        $delivery->awb_number = $awb;

        if (!$delivery->save()) {
            $response['success'] = false;
            $response['message'] = 'Gagal menambahkan nomor resi';
            return $response;
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil menambahkan nomor resi';
        return $response;
    }

    public function addAwbNumberAuto($order_id)
    {
        $delivery = OrderDelivery::where('order_id', $order_id)->first();

        Carbon::setLocale('id');
        $date = Carbon::now('Asia/Jakarta')->isoFormat('YMMDD');
        $id = str_pad($order_id, 4, '0', STR_PAD_LEFT);
        $resi = "CLG/{$date}/{$id}";

        $delivery->awb_number = $resi;

        if (!$delivery->save()) {
            $response['success'] = false;
            $response['message'] = 'Gagal menambahkan nomor resi';
            return $response;
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil menambahkan nomor resi';
        return $response;
    }

    public function updatePaymentDetail($no_reference, $payment_method)
    {
        $payments = OrderPayment::where('no_reference', $no_reference)->get();

        foreach ($payments as $payment) {
            $payment['payment_method'] = $payment_method;
            $payment['status'] = 1;
            if (!$payment->save()) {
                return false;
            }
        }
        return true;
    }

    public function updateCustomerDiscount($user_id, $email, $discount, $no_reference)
    {
        $now = Carbon::now('Asia/Jakarta');
        $data = CustomerDiscount::where('customer_reference_id', $user_id)->orWhere('customer_reference_id', $email)
            ->where('is_used', false)->where('expired_date', '>=', $now)->first();

        if ($data == null) {
            return true;
        }
        $data->is_used = true;
        $data->status = 1;
        $data->used_amount = $discount;
        $data->no_reference = $no_reference;

        if ($data->save()) {
            return true;
        }
        return false;
    }

    public function orderConfirmHasArrived($trx_no)
    {
        $order = Order::with(['delivery'])->where('trx_no', $trx_no)->first();
        if (!$order) {
            throw new Exception("Nomor invoice tidak ditemukan", 404);
        }

        //Update status order
        $order_progress = OrderProgress::where('order_id', $order['id'])->where('status', 1)->first();

        if ($order_progress['status_code'] == '03') {
            $trx_command = new TransactionCommands();
            $trx_command->updateOrderStatus($order['id'], '08');

            //Notification buyer
            $notif_command = new NotificationCommands();
            $title = 'Pesanan anda telah sampai';
            $message = 'Pesanan anda telah sampai, silakan cek kelengkapan pesanan anda sebelum menyelesaikan pesanan.';
            $url_path = 'v1/buyer/query/transaction/' . $order['buyer_id'] . '/detail/' . $order['id'];
            $notif_command->create('customer_id', $order['buyer_id'], '2', $title, $message, $url_path);
            $notif_command->sendPushNotification($order['buyer_id'], $title, $message, 'active');

            //Notification seller
            $title_seller = 'Pesanan Sampai';
            $message_seller = 'Pesanan telah sampai, menunggu pembeli menyelesaikan pesanan.';
            $url_path_seller = 'v1/seller/query/transaction/detail/' . $order['id'];
            $seller = Customer::where('merchant_id', $order['merchant_id'])->first();
            $notif_command->create('merchant_id', $order['merchant_id'], '2', $title_seller, $message_seller, $url_path_seller);
            $notif_command->sendPushNotification($seller['id'], $title_seller, $message_seller, 'active');

            $mailSender = new MailSenderManager();
            $mailSender->mailOrderArrived($order['id'], Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s'));
        }

        $response['success'] = true;
        $response['message'] = 'Pesanan telah sampai, menunggu pembeli menyelesaikan pesanan.';
        return $response;
    }

    public static function invoice_num($input, $pad_len = 3, $prefix = null)
    {
        if ($pad_len <= strlen($input)) {
            $pad_len++;
        }

        if (is_string($prefix)) {
            return sprintf("%s%s", $prefix, str_pad($input, $pad_len, "0", STR_PAD_LEFT));
        }

        return str_pad($input, $pad_len, "0", STR_PAD_LEFT);
    }

    public static function checkReferenceExist($no_reference)
    {
        if (OrderPayment::where('no_reference', $no_reference)->count() > 0) {
            return false;
        }
        return true;
    }

    public function generateResi($order_id)
    {
        $delivery = OrderDelivery::where('order_id', $order_id)->first();

        Carbon::setLocale('id');
        $date = Carbon::now('Asia/Jakarta')->isoFormat('YMMDD');
        $id = str_pad($order_id, 4, '0', STR_PAD_LEFT);
        $resi = "CLG/{$date}/{$id}";

        $delivery->awb_number = $resi;
        if (!$delivery->save()) {
            $response['success'] = false;
            $response['message'] = 'Gagal menambahkan nomor resi';
            return $response;
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil menambahkan nomor resi';
        return $response;
    }

    public function generateTicket($order_id)
    {
        $user_tikets = CustomerTiket::where('order_id', $order_id)->get();

        if (collect($user_tikets)->isNotEmpty()) {
            $response['success'] = true;
            $response['message'] = 'Berhasil menambahkan tiket';
            $response['data'] = $user_tikets;

            return $response;
        }

        $active_master_tikets = MasterTiket::with(['master_data'])
            ->where('status', 1)->get();

        $cat_child = collect($active_master_tikets)->pluck('master_data')->toArray();

        $user_tikets = [];
        $order = Order::where('id', $order_id)->first()->load('detail.product');
        foreach ($order->detail as $detail) {
            if (in_array($detail->product->category_id, collect($cat_child)->pluck('id')->toArray())) {
                $master_tiket = collect($active_master_tikets)->where('master_data.id', $detail->product->category_id)->first();
                $master_tiket['quantity'] = $detail->quantity;

                $cat_ticket[] = $ticket;
            }
        }

        $master_tikets = MasterTiket::whereIn('master_data_key', collect($cat_ticket)->pluck('key')->toArray())->get();

        foreach ($master_tikets as $master_tiket) {
            $ticket = collect($cat_ticket)->where('key', $master_tiket->master_data_key)->first();

            for ($i = 0; $i < $ticket['quantity']; $i++) {
                $id = rand(10000, 99999);
                $number_tiket = (string) time() . (string) $id;

                    $user_tikets[] = CustomerTiket::create([
                        'order_id' => $order_id,
                        'master_tiket_id' => $master_tiket['id'],
                        'number_tiket' => $number_tiket,
                        'usage_date' => $master_tiket['usage_date'],
                        'start_time_usage' => $master_tiket['start_time_usage'],
                        'end_time_usage' => $master_tiket['end_time_usage'],
                        'status' => 1,
                    ]);
                }
            }
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil menambahkan tiket';
        $response['data'] = $user_tikets;
        return $response;
    }

    public function generateTicketMudik($order_id)
    {
        $user_tikets = UserTiket::where('order_id', $order_id)->get();

        if (collect($user_tikets)->isNotEmpty()) {
            $response['success'] = true;
            $response['message'] = 'Berhasil menambahkan tiket';
            $response['data'] = $user_tikets;

            return $response;
        }

        $categories = MasterData::with(['child' => function ($j) {
            $j->with('child');
        }])->whereIn('key', ['prodcat_pln_mudik_2023_jabodetabek', 'prodcat_pln_mudik_2023_jawa_barat', 'prodcat_pln_mudik_2023_jawa_timur', 'prodcat_pln_mudik_2023_bali'])->get();

        $cat_child = [];
        foreach ($categories as $category) {
            foreach ($category->child as $child) {
                if (!$child->child->isEmpty()) {
                    foreach ($child->child as $children) {
                        array_push($cat_child, $children);
                    }
                }
            }
        }

        $cat_ticket = [];
        $order = Order::where('id', $order_id)->first()->load('detail.product');
        foreach ($order->detail as $detail) {
            if (in_array($detail->product->category_id, collect($cat_child)->pluck('id')->toArray())) {
                $ticket = collect($cat_child)->where('id', $detail->product->category_id)->first();
                $ticket['quantity'] = $detail->quantity;

                $cat_ticket[] = $ticket;
            }
        }

        $master_tikets = MasterTiket::whereIn('master_data_key', collect($cat_ticket)->pluck('key')->toArray())->get();

        foreach ($master_tikets as $master_tiket) {
            $ticket = collect($cat_ticket)->where('key', $master_tiket->master_data_key)->first();

            for ($i = 0; $i < $ticket['quantity']; $i++) {
                $id = rand(10000, 99999);
                $number_tiket = (string) time() . (string) $id;

                $user_tikets[] = UserTiket::create([
                    'order_id' => $order_id,
                    'master_tiket_id' => $master_tiket->id,
                    'number_tiket' => $number_tiket,
                    'usage_date' => $master_tiket->usage_date,
                    'start_time_usage' => $master_tiket->start_time_usage,
                    'end_time_usage' => $master_tiket->end_time_usage,
                    'status' => 0, // flagging belum isi form = status tiket 0, sudah isi form = status tiket 1
                ]);
            }
        }

        if (count($user_tikets) == 0) {
            $response['success'] = false;
            $response['message'] = 'Gagal menambahkan tiket';
            return $response;
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil menambahkan tiket';
        $response['data'] = $user_tikets;
        return $response;
    }
};

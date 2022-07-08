<?php

namespace App\Http\Services\Transaction;

use App\Http\Services\Notification\NotificationCommands;
use App\Http\Services\Product\ProductCommands;
use App\Models\CustomerDiscount;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderProgress;
use App\Models\Product;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Services\Service;
use App\Models\Customer;
use App\Models\OrderDelivery;
use App\Models\OrderPayment;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\IFTTTHandler;
use Ramsey\Uuid\Uuid;
use App\Http\Services\Manager\MailSenderManager;
use App\Models\User;

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

    static function init()
    {
        self::$curl = new Client();
        self::$apiendpoint = config('credentials.iconpay.endpoint');
        self::$appkey = config('credentials.iconpay.app_key');
        self::$clientid = config('credentials.iconpay.client_id');
        self::$productid = config('credentials.iconpay.product_id');
        self::$appsource = config('credentials.iconpay.app_source');
        self::$header = [
            'client-id' => static::$clientid,
            'appsource' => static::$appsource
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

            array_map(function ($data) use ($datas, $customer_id, $no_reference, $trx_date, $exp_date) {
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
                $order->created_by = 'user';
                $order->updated_by = 'user';
                $order->save();

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
                if (data_get($data, 'delivery_method') == 'J&T'){
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

            if ($datas['total_payment'] < 1){
                throw new Exception('Total pembayaran harus lebih dari 0 rupiah');
            }

            $mailSender = new MailSenderManager();
            $mailSender->mailCheckout($this->order_id);

            if ($datas['total_discount'] > 0){
                $update_discount = $this->updateCustomerDiscount($customer_id, $customer->email, $datas['total_discount'], $no_reference);
                if ($update_discount == false){
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
                'body' => $encode_body
            ]);

            Log::info("E00001", [
                'path_url' => "iconpay.endpoint/booking",
                'body' => $body,
                'query' => [],
                'response' => $response
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
            return [
                'success' => true,
                'message' => 'Berhasil create order',
                'data' => $response
            ];
        } catch (Exception $th) {
            DB::rollBack();
            if (in_array($th->getCode(), self::$error_codes)) {
                throw new Exception($th->getMessage(), $th->getCode());
            }
            throw new Exception($th->getMessage(), 500);
        }
    }

    static function nextOrderId()
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

        foreach ($total_items as $key => $value) {
            $product = Product::where('id', $key)->first();
            $product->items_sold = $value;
            $product->save();
        }
    }

    public function triggerRatingProductSold()
    {
        // $order = Order::with(['detail' => function ($query) {
        //     $query->whereHas('progress_order', function ($query) {
        //         $query->where('status', 1)
        //             ->where('status_code', '88');
        //     });
        // }])->get();

        $order = Order::with('detail')->whereHas('detail', function ($query) {
            $query->whereHas('progress_order', function ($query) {
                $query->where('status', 1)
                    ->where('status_code', '88');
            });
        })->get();

        // return $order;

        $total_items = [];
        foreach ($order->detail as $detail) {
            if (!isset($total_items[$detail->product_id])) {
                $total_items[$detail->product_id] = $detail->quantity;
            } else {
                $total_items[$detail->product_id] += $detail->quantity;
            }
        }

        // return $total_items;

        foreach ($total_items as $key => $value) {
            $product = Product::where('id', $key)->first();
            $product->items_sold = $value;
            $product->save();
        }

        $product =  $product->with('reviews')->withCount('reviews')->first();

        $rate = 0;
        foreach ($product->reviews as $key => $value) {
            $rate += $value->rate;
        }

        $product->update([
            'review_count' => $product->reviews_count,
            'avg_rating' => $rate/count($product->reviews)
        ]);

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

    public function updateCustomerDiscount($user_id, $email, $discount, $no_reference){
        $now = Carbon::now('Asia/Jakarta');
        $data = CustomerDiscount::where('customer_reference_id', $user_id)->orWhere('customer_reference_id', $email)
            ->where('is_used', false)->where('expired_date', '>=', $now)->first();

        if ($data == null){
            return true;
        }
        $data->is_used = true;
        $data->status = 1;
        $data->used_amount = $discount;
        $data->no_reference = $no_reference;

        if ($data->save()){
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

        if ($order_progress['status_code'] == '03'){
            $trx_command = new TransactionCommands();
            $trx_command->updateOrderStatus($order['id'], '08');

            //Notification buyer
            $notif_command = new NotificationCommands();
            $title = 'Pesanan anda telah sampai';
            $message = 'Pesanan anda telah sampai, silakan cek kelengkapan pesanan anda sebelum menyelesaikan pesanan.';
            $url_path = 'v1/buyer/query/transaction/'. $order['buyer_id'] .'/detail/' . $order['id'];
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

    static function invoice_num($input, $pad_len = 3, $prefix = null)
    {
        if ($pad_len <= strlen($input))
            $pad_len++;

        if (is_string($prefix))
            return sprintf("%s%s", $prefix, str_pad($input, $pad_len, "0", STR_PAD_LEFT));

        return str_pad($input, $pad_len, "0", STR_PAD_LEFT);
    }

    static function checkReferenceExist($no_reference)
    {
        if (OrderPayment::where('no_reference', $no_reference)->count() > 0) {
            return false;
        }
        return true;
    }
};

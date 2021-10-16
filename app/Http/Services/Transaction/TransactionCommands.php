<?php

namespace App\Http\Services\Transaction;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderProgress;
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

class TransactionCommands extends Service
{
    static $apiendpoint;
    static $appkey;
    static $curl;
    static $clientid;
    static $productid;
    static $appsource;
    static $header;

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
            'timestamp' => date('Y/m/d H:i:s', Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now())->setTimezone('Asia/Jakarta')->timestamp),
            'appsource' => static::$appsource,
            'signature' => static::$appkey
        ];
    }

    public function createOrder($datas, $related_pln_mobile_customer_id)
    {
        DB::beginTransaction();
        try {
            $no_reference = Uuid::uuid4();
            $trx_date = date('Y/m/d H:i:s', Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now())->setTimezone('Asia/Jakarta')->timestamp);
            $exp_date = date('Y/m/d H:i:s', Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now()->addDay())->setTimezone('Asia/Jakarta')->timestamp);
            $total_price = 0;

            array_map(function ($data) use ($datas, $related_pln_mobile_customer_id, $no_reference, $trx_date, $exp_date, $total_price) {
                $order = new Order();
                $order->merchant_id = data_get($data, 'merchant_id');
                $order->buyer_id = Customer::where('related_pln_mobile_customer_id', $related_pln_mobile_customer_id)->first()->id;
                $order->trx_no = static::invoice_num(static::nextOrderId(), 9, "INVO/" . Carbon::now()->year . Carbon::now()->month . Carbon::now()->day . "/MKP/");
                $order->order_date = date('Y/m/d H:i:s', Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now())->setTimezone('Asia/Jakarta')->timestamp);
                $order->total_amount = data_get($data, 'total_amount');
                $order->payment_amount = data_get($data, 'payment_amount');
                $order->total_weight = data_get($data, 'total_weight');
                $order->payment_method = null;
                $order->delivery_method = data_get($data, 'delivery_method');
                $order->related_pln_mobile_customer_id = $related_pln_mobile_customer_id;
                $order->no_reference = $no_reference;
                $order->save();

                $total_price += $order->payment_amount;

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
                    $order_detail->save();
                }, data_get($data, 'products'));

                $order_progress = new OrderProgress();
                $order_progress->order_id = $order->id;
                $order_progress->status_code = '00';
                $order_progress->status_name = 'Pesanan Belum Dibayar';
                $order_progress->note = null;
                $order_progress->status = 1;
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
                $order_delivery->save();

                $order_payment = new OrderPayment();
                $order_payment->order_id = $order->id;
                $order_payment->customer_id = $related_pln_mobile_customer_id;
                $order_payment->payment_amount = data_get($data, 'payment_amount');
                $order_payment->date_created = $trx_date;
                $order_payment->date_expired = $exp_date;
                $order_payment->payment_method = null;
                $order_payment->booking_code = null;
                $order_payment->payment_note = data_get($data, 'payment_note') ?? null;
            }, data_get($datas, 'merchants'));
            DB::commit();

            $customer = Customer::where('related_pln_mobile_customer_id', $related_pln_mobile_customer_id)->first();

            $url = sprintf('%s/%s', static::$apiendpoint, 'booking');
            $body = [
                'no_reference' => $no_reference,
                'transaction_date' => $trx_date,
                'transaction_code' => '00',
                'partner_reference' => $no_reference,
                'product_id' => static::$productid,
                'amount' => $total_price,
                'customer_id' => $related_pln_mobile_customer_id,
                'customer_name' => $customer['fullname'],
                'email' => $customer['email'],
                'phone_number' => $customer['phone'],
                'expired_invoice' => $exp_date,
            ];

            $response = static::$curl->request('POST', $url, [
                'headers' => static::$header,
                'http_errors' => false,
                'json' => $body
            ]);

            Log::info("E00001", [
                'path_url' => "iconpay.endpoint/booking",
                'body' => $body,
                'query' => [],
                'response' => $response
            ]);
            
            $response = json_decode($response->getBody());

            throw_if(!$response, Exception::class, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh', 500));

            if ($response->response_status->success != 1) {
                throw new Exception($response->response_status);
            }

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

    static function invoice_num($input, $pad_len = 3, $prefix = null)
    {
        if ($pad_len <= strlen($input))
            $pad_len++;

        if (is_string($prefix))
            return sprintf("%s%s", $prefix, str_pad($input, $pad_len, "0", STR_PAD_LEFT));

        return str_pad($input, $pad_len, "0", STR_PAD_LEFT);
    }
};

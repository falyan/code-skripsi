<?php

namespace App\Http\Services\Manager;

use App\Http\Resources\Rajaongkir\RajaongkirResources;
use App\Http\Resources\Rajaongkir\RajaongkirSameLogisticResources;
use App\Http\Services\Notification\NotificationCommands;
use App\Http\Services\Transaction\TransactionCommands;
use App\Http\Services\Transaction\TransactionQueries;
use App\Models\Customer;
use App\Models\Order;
use App\Models\RajaOngkirSetting;
use App\Models\MasterData;
use App\Models\CacheRajaongkirShipping;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Cache;
use Psr\Http\Message\ResponseInterface;

class RajaOngkirManager
{
    protected $notificationCommand;

    static $apiendpoint;
    static $appkey;
    static $curl;
    static $header;
    static $rajaOngkirType;

    static function init()
    {
        self::$curl = new Client();

        date_default_timezone_set('Asia/Jakarta');
        $settings = Cache::remember('rajaongkir_settings', 60 * 60 * 24, function () {
            $type = MasterData::where('key', 'rajaongkir_type')->first();
            return RajaOngkirSetting::where([
                'status' => 1,
                'type_key' => $type->value,
            ])->get();
        });

        foreach ($settings as $setting) {
            if (Carbon::now()->format('H:i') >= $setting->time_start && Carbon::now()->format('H:i') <= $setting->time_end) {
                self::$appkey = $setting->credential_key;
                self::$apiendpoint = $setting->base_url;
                self::$rajaOngkirType = $setting->type_key;
            }
        }

        self::$header = [
            'key' => static::$appkey
        ];
    }

    public function __construct()
    {
        $this->notificationCommand = new NotificationCommands();
    }

    public static function getProvinces($province_id = null)
    {
        $param = static::setParamAPI([
            'id' => $province_id
        ]);

        $url = sprintf('%s/%s', static::$apiendpoint, 'api/province' . $param);

        $response = static::$curl->request('GET', $url, [
            'headers' => [
                'key' => static::$appkey
            ],
            'http_errrors' => false,
            'json_decode' => true
        ]);

        Log::info("E00002", [
            'path_url' => "rajaongkir.endpoint/api/province",
            'query' => [],
            'body' => $param,
            'response' => $response
        ]);

        $response  = json_decode($response->getBody());

        return data_get($response, 'rajaongkir.results');
    }

    public static function getCities($province_id = null, $city_id = null)
    {
        $param = static::setParamAPI([
            'province' => $province_id,
            'id' => $city_id
        ]);

        $url = sprintf('%s/%s', static::$apiendpoint, 'api/city' . $param);

        $response = static::$curl->request('GET', $url, [
            'headers' => [
                'key' => static::$appkey
            ],
            'http_errrors' => false,
            'json_decode' => true
        ]);

        Log::info("E00002", [
            'path_url' => "rajaongkir.endpoint/api/city",
            'query' => [],
            'body' => $param,
            'response' => $response
        ]);

        $response  = json_decode($response->getBody());
        return data_get($response, 'rajaongkir.results');
    }

    public static function getSubdistrict($id = null, $city_id = null)
    {
        $param = static::setParamAPI([
            'id' => $id,
            'city' => $city_id
        ]);

        $url = sprintf('%s/%s', static::$apiendpoint, 'api/subdistrict' . $param);

        $response = static::$curl->request('GET', $url, [
            'headers' => [
                'key' => static::$appkey
            ],
            'http_errrors' => false,
            'json_decode' => true
        ]);

        Log::info("E00002", [
            'path_url' => "rajaongkir.endpoint/api/subdistrict",
            'query' => [],
            'body' => $param,
            'response' => $response
        ]);

        $response  = json_decode($response->getBody());
        return data_get($response, 'rajaongkir.results');
    }

    static function getOngkir($request)
    {
        $url = sprintf('%s/%s', static::$apiendpoint, 'api/cost');

        $body = [
            'origin' => data_get($request, 'origin_district_id'),
            'originType' => 'subdistrict',
            'destination' => data_get($request, 'destination_district_id'),
            'destinationType' => 'subdistrict',
            'weight' => (int) data_get($request, 'weight'),
            'courier' => strtolower(data_get($request, 'courier'))
        ];

        $response = static::$curl->request('POST', $url, [
            'headers' => static::$header,
            'http_errors' => false,
            'json' => $body
        ]);

        Log::info("E00002", [
            'path_url' => "rajaongkir.endpoint/api/cost",
            'query' => [],
            'body' => $body,
            'response' => $response
        ]);

        $response = json_decode($response->getBody());

        throw_if(!$response, Exception::class, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh', 500));

        if ($response->rajaongkir->status->code != 200) {
            throw new Exception($response->rajaongkir->status->description, $response->rajaongkir->status->code);
        }

        $transactionQueries = new TransactionQueries();
        $response->delivery_discount = $transactionQueries->getDeliveryDiscount();

        return new RajaongkirResources($response);
    }

    public static function trackOrder($trx_no)
    {
        $url = sprintf('%s/%s', static::$apiendpoint, 'api/waybill');

        $order = Order::with(['delivery'])->where('trx_no', $trx_no)->first();
        if (!$order) {
            throw new Exception("Nomor invoice tidak ditemukan", 404);
        }
        $body = [
            'waybill' => $order->delivery->awb_number,
            'courier' => $order->delivery->delivery_method,
        ];

        $response = static::$curl->request('POST', $url, [
            'headers' => static::$header,
            'http_errors' => false,
            'json' => $body
        ]);

        Log::info("E00002", [
            'path_url' => "rajaongkir.endpoint/api/waybill",
            'query' => [],
            'body' => $body,
            'response' => $response
        ]);

        $response = json_decode($response->getBody());

        if ($response->rajaongkir->status->code != 200) {
            return response()->json(['success' => false, 'error_code' => $response->rajaongkir->status->code, 'description' => $response->rajaongkir->status->description]);
        }

        throw_if(!$response, Exception::class, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh', 500));

        if ($response->rajaongkir->result->delivered == true) {
            //Update status order
            $order = Order::where('trx_no', $trx_no)->first();

            DB::beginTransaction();

            $transactionQueries = new TransactionQueries();
            $data = $transactionQueries->getStatusOrder($order['id'], true);

            $status_codes = [];
            foreach ($data->progress as $item) {
                if (in_array($item->status_code, ['01', '02', '03'])) {
                    $status_codes[] = $item;
                }
            }

            $status_code = collect($status_codes)->where('status_code', '03')->first();
            if (count($status_codes) == 3 && $status_code['status'] == 1) {
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

                DB::commit();

                $mailSender = new MailSenderManager();
                $mailSender->mailOrderArrived($order['id'], Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s'));
            } else {
                DB::rollBack();
            }
        }

        if ($response->rajaongkir->status->code != 200) {
            $current_url = URL::current();
            if (strpos($current_url, 'track')) {
                throw new Exception($response->rajaongkir->status->description, $response->rajaongkir->status->code);
            }
        }

        return $response;
    }

    static function getOngkirSameLogistic($customer_address, $merchant, $weight, $courirer)
    {
        // take 3 last character from weight
        // $weighting = substr($weight, -3);
        // if ($weighting <= 300) {
        //     $weight = $weight - $weighting;
        // } else {
        //     $weighting = 1000 - $weighting;
        //     $weight = $weight + $weighting;
        // }

        $courirers = [];
        foreach (explode(':', $courirer) as $courier) {
            $courirers[] = $merchant->district_id . '.' . $customer_address->district_id . '.' . $weight . '.' . $courier;
        }

        $cache_rajaongkir = CacheRajaongkirShipping::whereIn('key', $courirers)->where('expired_at', '>', Carbon::now())->get();
        if (count($cache_rajaongkir) == count($courirers)) {
            $cache_response = [];
            foreach ($cache_rajaongkir as $cache) {
                $cache_response[] = json_decode($cache->value);
            }
            return $cache_response;
        }

        $url = sprintf('%s/%s', static::$apiendpoint, 'api/cost');

        $body = [
            'origin' => $merchant->district_id,
            'originType' => 'subdistrict',
            'destination' => $customer_address->district_id,
            'destinationType' => 'subdistrict',
            'weight' => (int) $weight,
            'courier' => strtolower($courirer),
        ];

        $response = static::$curl->request('POST', $url, [
            'headers' => static::$header,
            'http_errors' => false,
            'json' => $body
        ]);

        $response = json_decode($response->getBody());

        Log::info("E00002", [
            'path_url' => "rajaongkir.endpoint/api/cost",
            'query' => [],
            'body' => $body,
            'response' => $response
        ]);

        throw_if(!$response, Exception::class, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh', 500));

        if ($response->rajaongkir->status->code != 200) {
            return [];
        }

        // $transactionQueries = new TransactionQueries();
        // $response->delivery_discount = $transactionQueries->getDeliveryDiscount();

        $resources = collect(new RajaongkirSameLogisticResources($response));

        foreach ($resources as $resource) {
            $cache = [
                'key' =>  $merchant->district_id . '.' . $customer_address->district_id . '.' . $weight . '.' . data_get($resource, 'code'),
                'value' => json_encode($resource),
                'expired_at' => Carbon::now()->addDays(1)->format('Y-m-d H:i:s'),
            ];

            CacheRajaongkirShipping::updateOrCreate(
                ['key' => $cache['key']],
                $cache
            );
        }

        return $resources;
    }

    public static function trackOrderSameLogistic($order)
    {
        $param = static::setParamAPI([]);
        $url = sprintf('%s/%s', static::$apiendpoint, 'api/waybill', $param);

        $body = [
            'waybill' => $order->delivery->awb_number,
            'courier' => $order->delivery->delivery_method,
        ];

        $response = static::$curl->request('POST', $url, [
            'headers' => static::$header,
            'http_errors' => false,
            'json' => $body
        ]);

        Log::info("E00002", [
            'path_url' => "rajaongkir.endpoint/api/waybill",
            'query' => [],
            'body' => $body,
            'response' => $response
        ]);

        $response = json_decode($response->getBody());

        if ($response->rajaongkir->status->code != 200) {
            return response()->json(['success' => false, 'error_code' => $response->rajaongkir->status->code, 'description' => $response->rajaongkir->status->description]);
        }

        throw_if(!$response, Exception::class, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh', 500));

        if ($response->rajaongkir->result->delivered == true) {
            //Update status order
            // $order = Order::where('trx_no', $trx_no)->first();

            DB::beginTransaction();

            $transactionQueries = new TransactionQueries();
            $data = $transactionQueries->getStatusOrder($order['id'], true);

            $status_codes = [];
            foreach ($data->progress as $item) {
                if (in_array($item->status_code, ['01', '02', '03'])) {
                    $status_codes[] = $item;
                }
            }

            $status_code = collect($status_codes)->where('status_code', '03')->first();
            if (count($status_codes) == 3 && $status_code['status'] == 1) {
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

                DB::commit();

                $mailSender = new MailSenderManager();
                $mailSender->mailOrderArrived($order['id'], Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s'));
            } else {
                DB::rollBack();
            }
        }

        if ($response->rajaongkir->status->code != 200) {
            $current_url = URL::current();
            if (strpos($current_url, 'track')) {
                throw new Exception($response->rajaongkir->status->description, $response->rajaongkir->status->code);
            }
        }

        $delivered = false;
        $tracking = [];

        if ($response->rajaongkir->result->delivery_status->status == 'DELIVERED') {
            $delivered = true;
        }

        foreach ($response->rajaongkir->result->manifest as $value) {
            $description = $value->manifest_description;
            if (explode(' - ', $value->city_name)) {
                $description .=  ' di ' . Ucfirst(strtolower(explode(' - ', $value->city_name)[0]));
            }
            if (count(explode(' - ', $value->city_name)) > 1) {
                $description .=  ' - ' . Ucfirst(strtolower(explode(' - ', $value->city_name)[1]));
            }
            $tracking[] = [
                'status' => $value->manifest_code,
                'date' => Carbon::createFromFormat('Y-m-d H:i:s', $value->manifest_date . ' ' . $value->manifest_time, 'Asia/Jakarta')->format('Y-m-d H:i'),
                'description' => $description,
            ];
        }

        return [
            'delivered' => $delivered,
            'awb_number' => $order->delivery->awb_number,
            'no_reference' => $order->delivery->no_reference,
            'shippper_name' => $order->merchant->name,
            'shippper_address' => $order->merchant->address,
            'receiver_name' => $order->delivery->receiver_name,
            'receiver_address' => $order->delivery->receiver_address,
            'status_order' => $response->rajaongkir->result->delivery_status->status,
            'waybill_date' => $response->rajaongkir->result->details->waybill_date,
            'waybill_time' => $response->rajaongkir->result->details->waybill_time,
            'tracking' => $tracking,
        ];
    }

    public static function cekResi($awb, $courir)
    {
        $param = static::setParamAPI([]);
        $url = sprintf('%s/%s', static::$apiendpoint, 'api/waybill');

        $body = [
            'waybill' => $awb,
            'courier' => $courir,
        ];

        $response = static::$curl->request('POST', $url, [
            'headers' => static::$header,
            'http_errors' => false,
            'json' => $body
        ]);

        Log::info("E00002", [
            'path_url' => "rajaongkir.endpoint/api/waybill",
            'query' => [],
            'body' => $body,
            'response' => $response
        ]);

        $response = json_decode($response->getBody());

        if ($response->rajaongkir->status->code != 200) {
            return false;
            // return response()->json(['success' => false, 'error_code' => $response->rajaongkir->status->code, 'description' => $response->rajaongkir->status->description]);
        }

        // throw_if(!$response, Exception::class, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh', 500));

        return true;
    }

    static function setParamAPI($data = [])
    {
        $param = [];
        $index = 0;
        $len = count($data);

        foreach ($data as $key => $value) {
            $value = preg_replace('/\s+/', '+', $value);

            if ($index == 0) {
                $param[] = sprintf('?%s=%s', $key, $value);
            } else {
                $param[] = sprintf('&%s=%s', $key, $value);
            }

            $index++;
        }

        return implode('', $param);
    }
}

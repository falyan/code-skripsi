<?php

namespace App\Http\Services\Manager;

use App\Http\Services\Notification\NotificationCommands;
use App\Http\Services\Transaction\TransactionCommands;
use App\Http\Services\Transaction\TransactionQueries;
use App\Models\Customer;
use App\Models\Order;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LogisticManager
{
    static $endpoint;
    static $key;
    static $curl;
    static $headers;

    public static function init()
    {
        self::$endpoint = config('credentials.hedwig.endpoint');
        self::$key = config('credentials.hedwig.key');
        self::$curl = new Client();
        self::$headers = [
            'Key-Access' => static::$key,
        ];
    }

    public static function searchLocation($request)
    {
        $param = static::setParamAPI([
            'keyword' => $request['keyword'],
            'limit' => $request['limit'],
            'page' => $request['page'],
        ]);
        $url = sprintf('%s/%s', static::$endpoint, 'v1/search/location' . $param);

        $response = static::$curl->request('GET', $url, [
            'headers' => static::$headers,
            'http_errors' => false,
        ]);

        Log::info("E00002", [
            'path_url' => "hedwig.endpoint/v1/service/rates",
            'query' => [],
            'body' => [],
            'response' => $response,
        ]);

        $response = json_decode($response->getBody());

        throw_if(!$response, Exception::class, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh', 500));

        if ($response->status != 200) {
            throw new Exception($response->message, $response->status);
        }

        return $response->data;
    }

    public static function searchLocationByCode($request)
    {
        $param = static::setParamAPI([
            'kode' => $request['kode'],
        ]);
        $url = sprintf('%s/%s', static::$endpoint, 'v1/search/location' . $param);

        $response = static::$curl->request('GET', $url, [
            'headers' => static::$headers,
            'http_errors' => false,
        ]);

        Log::info("E00002", [
            'path_url' => "hedwig.endpoint/v1/search/location",
            'query' => [],
            'body' => [],
            'response' => $response,
        ]);

        $response = json_decode($response->getBody());

        throw_if(!$response, Exception::class, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh', 500));

        if ($response->status != 200) {
            throw new Exception($response->message, $response->status);
        }

        return $response->data;
    }

    public static function getProvince()
    {
        $param = static::setParamAPI([]);
        $url = sprintf('%s/%s', static::$endpoint, 'v2/province' . $param);

        $response = static::$curl->request('GET', $url, [
            'headers' => static::$headers,
            'http_errors' => false,
        ]);

        Log::info("E00002", [
            'path_url' => "hedwig.endpoint/v2/province",
            'query' => [],
            'body' => [],
            'response' => $response,
        ]);

        $response = json_decode($response->getBody());

        throw_if(!$response, Exception::class, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh', 500));

        if ($response->status != 200) {
            throw new Exception($response->message, $response->status);
        }

        return $response->data;
    }

    public static function getCity($id)
    {
        $param = static::setParamAPI([]);
        $url = sprintf('%s/%s', static::$endpoint, "v2/province/{$id}/city" . $param);

        $response = static::$curl->request('GET', $url, [
            'headers' => static::$headers,
            'http_errors' => false,
        ]);

        Log::info("E00002", [
            'path_url' => "hedwig.endpoint/v2/city",
            'query' => [],
            'body' => [],
            'response' => $response,
        ]);

        $response = json_decode($response->getBody());

        throw_if(!$response, Exception::class, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh', 500));

        if ($response->status != 200) {
            throw new Exception($response->message, $response->status);
        }

        return $response->data;
    }

    public static function getDistrict($id)
    {
        $param = static::setParamAPI([]);
        $url = sprintf('%s/%s', static::$endpoint, "v2/city/{$id}/district" . $param);

        $response = static::$curl->request('GET', $url, [
            'headers' => static::$headers,
            'http_errors' => false,
        ]);

        Log::info("E00002", [
            'path_url' => "hedwig.endpoint/v2/district",
            'query' => [],
            'body' => [],
            'response' => $response,
        ]);

        $response = json_decode($response->getBody());

        throw_if(!$response, Exception::class, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh', 500));

        if ($response->status != 200) {
            throw new Exception($response->message, $response->status);
        }

        return $response->data;
    }

    public static function getSubDistrict($id)
    {
        $param = static::setParamAPI([]);
        $url = sprintf('%s/%s', static::$endpoint, "v2/district/{$id}/subdistrict" . $param);

        $response = static::$curl->request('GET', $url, [
            'headers' => static::$headers,
            'http_errors' => false,
        ]);

        Log::info("E00002", [
            'path_url' => "hedwig.endpoint/v2/subdistrict",
            'query' => [],
            'body' => [],
            'response' => $response,
        ]);

        $response = json_decode($response->getBody());

        throw_if(!$response, Exception::class, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh', 500));

        if ($response->status != 200) {
            throw new Exception($response->message, $response->status);
        }

        return $response->data;
    }

    public static function getOngkir($customer_address, $merchant, $weight, $courirer, $price)
    {
        $param = static::setParamAPI([]);
        $url = sprintf('%s/%s', static::$endpoint, 'v1/service/rates' . $param);

        $body = [
            'shipper' => [
                'origin' => (string) $merchant->district_id,
                'latitude' => (string) $merchant->latitude,
                'longitude' => (string) $merchant->longitude,
                'postal_code' => (string) $merchant->postal_code,
            ],
            'receiver' => [
                'destination' => (string) $customer_address->district_id,
                'latitude' => (string) $customer_address->latitude,
                'longitude' => (string) $customer_address->longitude,
                'postal_code' => (string) $customer_address->postal_code,
            ],
            'item_price' => $price,
            'weight' => $weight,
            'courier' => $courirer,
        ];

        $response = static::$curl->request('POST', $url, [
            'headers' => static::$headers,
            'http_errors' => false,
            'json' => $body,
        ]);

        $response = json_decode($response->getBody(), true);

        Log::info("E00002", [
            'path_url' => "hedwig.endpoint/v2/service/rates",
            'query' => [],
            'body' => $body,
            'response' => $response,
        ]);

        throw_if(!$response, Exception::class, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh', 500));

        if ($response['status'] != 200) {
            return [];
        }

        // $transactionQueries = new TransactionQueries();
        // $response['delivery_discount'] = $transactionQueries->getDeliveryDiscount();

        return $response['data'];
    }

    public static function updateAwb($trx_no, $awb_number)
    {
        $param = static::setParamAPI([]);
        $url = sprintf('%s/%s', static::$endpoint, 'v1/tracking' . $param);

        $order = Order::with(['delivery'])->where('trx_no', $trx_no)->first();
        if (!$order) {
            throw new Exception("Nomor invoice tidak ditemukan", 404);
        };
        // return $order;

        $body = [
            'trx_no' => $trx_no,
            'awb_number' => $awb_number,
        ];
        // return $body;

        $response = static::$curl->request('POST', $url, [
            'headers' => static::$headers,
            'http_errors' => false,
            'json' => $body,
        ]);

        Log::info("E00002", [
            'path_url' => "hedwig.endpoint/v1/tracking",
            'query' => [],
            'body' => $body,
            'response' => $response,
        ]);

        $status_code = $response->getStatusCode();
        $response = json_decode($response->getBody());
        // return $response;

        //if status response is 200 then update awb number
        if ($status_code == 200) {
            $order->delivery->awb_number = $awb_number;
            $order->delivery->save();

            //return update awb success with message
            return [
                'status' => 200,
                'message' => 'Update AWB berhasil',
            ];
        }

        //if status response is not 200 then throw exception
        if ($status_code != 200) {

            $body = [
                'trx_no' => $trx_no,
                'awb_number' => $order->delivery->awb_number,
            ];

            static::$curl->request('POST', $url, [
                'headers' => static::$headers,
                'http_errors' => false,
                'json' => $body,
            ]);

            // return awb update failed
            return [
                'status' => 400,
                'message' => 'Update AWB gagal',
            ];
        }
    }

    public static function track($order)
    {
        $param = static::setParamAPI([]);
        $url = sprintf('%s/%s', static::$endpoint, 'v1/tracking' . $param);

        $body = [
            'trx_no' => $order->trx_no,
        ];

        $response = static::$curl->request('POST', $url, [
            'headers' => static::$headers,
            'http_errors' => false,
            'json' => $body,
        ]);

        $response = json_decode($response->getBody(), true);

        Log::info("E00002", [
            'path_url' => "hedwig.endpoint/v2/tracking",
            'query' => [],
            'body' => $body,
            'response' => $response,
        ]);

        throw_if(!$response, Exception::class, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh', 500));

        if (!isset($response['status']) || $response['status'] != 200) {
            throw new Exception('Terjadi kesalahan: Sedang terjadi gangguan.', 500);
        }

        $transactionQueries = new TransactionQueries();
        $data = $transactionQueries->getStatusOrder($order->id, true);

        if (isset($response['data']) && $response['data']['delivered'] == true) {
            DB::beginTransaction();

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

        if (isset($response['data']) && $response['data']['shippper_name'] == null) {
            $delivery = $data->delivery->merchant_data;
            $delivery = json_decode($delivery);

            $response['data']['shippper_name'] = $delivery->merchant_name;
            $response['data']['shippper_address'] = $delivery->merchant_address;
            $response['data']['receiver_name'] = $data->delivery->receiver_name;
            $response['data']['receiver_address'] = $data->delivery->address;
        }

        $response['data']['tracking'] = array_map(function ($item) {
            $item['status'] = static::getStatusRajaOngkir($item['status']);
            return $item;
        }, $response['data']['tracking']);

        return $response;
    }

    public static function preorder($order_id, $pick_up_time)
    {
        $param = static::setParamAPI([]);
        $url = sprintf('%s/%s', static::$endpoint, 'v1/preorder' . $param);

        $order = Order::with(['merchant', 'merchant.corporate', 'buyer', 'delivery', 'detail'])->where('id', $order_id)->first();
        $items = [];
        $total_price = 0;
        foreach ($order->detail as $detail) {
            if (count($order->detail) > 1) {
                $weight = $detail->weight;
            } else {
                if ($detail->weight < 1000) {
                    $weight = 1000;
                } else {
                    $weight = $detail->weight;
                }
            }

            $items[] = [
                'name' => $detail->product->name,
                'quantity' => (int) $detail->quantity,
                'price' => (int) $detail->price,
                'weight' => (int) $weight,
                'length' => (int) $detail->product->length,
                'width' => (int) $detail->product->width,
                'height' => (int) $detail->product->height,
                'category' => $detail->product->category->parent != null || $detail->product->category->parent->parent != null ? $detail->product->category->parent->parent->value : null,
                'uom' => $detail->product->stock_active->uom,
                'note' => $detail->notes,
            ];

            $total_price += $detail->price;
        }

        $body = [
            'trx_no' => $order->trx_no,
            'courier' => $order->delivery->delivery_method,
            'service_code' => $order->delivery->delivery_type,
            'final_shipping_price' => (int) $order->delivery->delivery_fee,
            'origin_shipping_price' => (int) $order->delivery->delivery_fee_origin,
            'shipping_type' => $order->delivery->shipping_type,
            'must_use_insurance' => $order->delivery->must_use_insurance,
            'items_total_price' => $total_price,
            'pick_up_time' => $pick_up_time,
            'shipper' => [
                'merchant_id' => $order->merchant->id,
                'company_name' => $order->merchant->corporate->name,
                'name' => $order->merchant->name,
                'email' => $order->merchant->email,
                'phone' => $order->merchant->phone_office,
                'origin' => (string) $order->merchant->district_id,
                'merchant_name' => $order->merchant->name,
                'address' => $order->merchant->address,
                'latitude' => $order->merchant->latitude,
                'longitude' => $order->merchant->longitude,
                'postal_code' => $order->merchant->postal_code,
            ],
            'receiver' => [
                'name' => $order->delivery->receiver_name,
                'email' => $order->buyer->email,
                'phone' => $order->delivery->receiver_phone,
                'destination' => (string) $order->delivery->district_id,
                'address' => $order->delivery->address,
                'latitude' => $order->delivery->latitude,
                'longitude' => $order->delivery->longitude,
                'postal_code' => $order->delivery->postal_code,
            ],
            'items' => $items,
        ];

        $merchant_delivery = json_decode($order->delivery->merchant_data);
        if ($merchant_delivery != null) {
            $body['shipper'] = [
                'merchant_id' => $order->merchant->id,
                'company_name' => $order->merchant->corporate->name,
                'name' => $merchant_delivery->merchant_name,
                'email' => $order->merchant->email,
                'phone' => $merchant_delivery->merchant_phone_office,
                'origin' => (string) $merchant_delivery->merchant_district_id,
                'merchant_name' => $merchant_delivery->merchant_name,
                'address' => $merchant_delivery->merchant_address,
                'latitude' => $merchant_delivery->merchant_latitude,
                'longitude' => $merchant_delivery->merchant_longitude,
                'postal_code' => $merchant_delivery->merchant_postal_code,
            ];
        }

        // dd($body);
        // return $body;

        $response = static::$curl->request('POST', $url, [
            'headers' => static::$headers,
            'http_errors' => false,
            'json' => $body,
        ]);

        $response = json_decode($response->getBody(), true);
        // return $response;

        Log::info("E00002", [
            'path_url' => "hedwig.endpoint/v1/preorder",
            'query' => [],
            'body' => $body,
            'response' => $response,
        ]);

        throw_if(!$response, Exception::class, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh', 500));

        return $response;
    }

    public static function requestPickup($order_id, $expect_time)
    {
        $param = static::setParamAPI([]);
        $url = sprintf('%s/%s', static::$endpoint, 'v2/request-pickup' . $param);

        $body = [
            'trx_no' => [
                $order_id
            ],
            'expect_time' => $expect_time,
        ];
        // return $body;

        $response = static::$curl->request('POST', $url, [
            'headers' => static::$headers,
            'http_errors' => false,
            'json' => $body,
        ]);

        $response = json_decode($response->getBody(), true);
        // return $response;

        Log::info("E00002", [
            'path_url' => "hedwig.endpoint/v2/request-pickup",
            'query' => [],
            'body' => $body,
            'response' => $response,
        ]);

        throw_if(!$response, Exception::class, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh', 500));

        return $response;
    }

    public static function cancel($trx_no)
    {
        $param = static::setParamAPI([]);
        $url = sprintf('%s/%s', static::$endpoint, 'v2/cancel-order' . $param);

        $body = [
            'trx_no' => $trx_no,
        ];
        // return $body;

        $response = static::$curl->request('POST', $url, [
            'headers' => static::$headers,
            'http_errors' => false,
            'json' => $body,
        ]);

        Log::info("E00002", [
            'path_url' => "hedwig.endpoint/v2/cancel-order",
            'query' => [],
            'body' => $body,
            'response' => $response,
        ]);

        $response = json_decode($response->getBody(), true);
        // return $response;

        throw_if(!$response, Exception::class, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh', 500));

        return $response;
    }

    public static function setParamAPI($data = [])
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

    private static function getStatusRajaOngkir($status)
    {
        $statusCode = '1';
        if (in_array($status, ['00', '01'])) {
            $statusCode = '1';
        } elseif (in_array($status, ['02', '03'])) {
            $statusCode = '2';
        } elseif (in_array($status, ['04'])) {
            $statusCode = '4';
        } elseif (in_array($status, ['88'])) {
            $statusCode = '5';
        } elseif (in_array($status, ['98', '99'])) {
            $statusCode = '1';
        }

        return $statusCode;
    }
}

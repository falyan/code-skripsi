<?php

namespace App\Http\Services\Manager;

use App\Http\Resources\Rajaongkir\RajaongkirResources;
use App\Http\Services\Notification\NotificationCommands;
use App\Http\Services\Transaction\TransactionCommands;
use App\Models\Order;
use App\Models\OrderProgress;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class RajaOngkirManager
{
  static $apiendpoint;
  static $appkey;
  static $curl;
  static $header;

  static function init()
  {
    self::$curl = new Client();
    self::$apiendpoint = config('credentials.rajaongkir.endpoint');
    self::$appkey = config('credentials.rajaongkir.app_key');
    self::$header = [
      'key' => static::$appkey
    ];
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
    $param = static::setParamAPI([]);

    $url = sprintf('%s/%s', static::$apiendpoint, 'api/cost' . $param);

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

    return new RajaongkirResources($response);
  }

  public static function trackOrder($trx_no, $user_id)
  {
    $param = static::setParamAPI([]);

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

    if ($response->rajaongkir->result->delivered == true){
        //Update status order
        $order = Order::where('trx_no', $trx_no)->first();

        $order_progress = OrderProgress::where('order_id', $order['id'])->where('status', 1)->first();
        if ($order_progress['status_code'] != '08'){
            $trx_command = new TransactionCommands();
            $trx_command->updateOrderStatus($order['id'], '08');

            //Notification log
            $notif_command = new NotificationCommands();
            $title = 'Pesanan anda telah sampai';
            $message = 'Pesanan anda telah sampai, silahkan cek kelengkapan pesanan anda sebelum menyelesaikan pesanan.';
            $url_path = 'v1/buyer/query/transaction/'. $user_id .'/detail/' . $order['id'];
            $notif_command->create('customer_id', $user_id, '2', $title, $message, $url_path);
        }
    }

    throw_if(!$response, Exception::class, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh', 500));

    if ($response->rajaongkir->status->code != 200) {
      throw new Exception($response->rajaongkir->status->description, $response->rajaongkir->status->code);
    }

    return $response;
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

<?php

namespace App\Http\Services\Manager;

use App\Http\Resources\Rajaongkir\RajaongkirResources;
use App\Models\Order;
use Exception;
use GuzzleHttp\Client;

class RajaOngkirManager {
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

    $response  = json_decode($response->getBody());
    return data_get($response, 'rajaongkir.results');
  }

  static function getOngkir($request)
  {
    $param = static::setParamAPI([]);

    $url = sprintf('%s/%s', static::$apiendpoint, 'api/cost' . $param);

    $response = static::$curl->request('POST', $url, [
      'headers' => static::$header,
      'http_errors' => false,
      'json' => [
        'origin' => data_get($request, 'origin_district_id'),
        'originType' => 'subdistrict',
        'destination' => data_get($request, 'destination_district_id'),
        'destinationType' => 'subdistrict',
        'weight' => (int) data_get($request, 'weight'),
        'courier' => strtolower(data_get($request, 'courier'))
      ]
    ]);

    $response = json_decode($response->getBody());

    throw_if(!$response, Exception::class, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh', 500));

    if ($response->rajaongkir->status->code != 200) {
      throw new Exception($response->rajaongkir->status->description, $response->rajaongkir->status->code);
    }
    
    return new RajaongkirResources($response);
  }

  public static function trackOrder($trx_no)
  {
      $param = static::setParamAPI([]);

      $url = sprintf('%s/%s', static::$apiendpoint, 'api/waybill');

      $order = Order::where('trx_no', $trx_no)->first();
      $response = static::$curl->request('POST', $url, [
          'headers' => static::$header,
          'http_errors' => false,
          'json' => [
              'waybill' => $order->delivery->awb_number,
              'courier' => $order->delivery_method,
          ]
      ]);

      $response = json_decode($response->getBody());

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

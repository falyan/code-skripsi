<?php 

namespace App\Http\Services\Manager;

use GuzzleHttp\Client;

class RajaOngkirManager {
  static $apiendpoint;
  static $appkey;
  static $curl;

  static function init()
  {
    self::$curl = new Client();
    self::$apiendpoint = config('credentials.rajaongkir.endpoint');
    self::$appkey = config('credentials.rajaongkir.app_key');
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

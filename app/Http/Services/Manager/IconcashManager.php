<?php

namespace App\Http\Services\Manager;

use App\Http\Resources\Rajaongkir\RajaongkirResources;
use App\Models\Order;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class IconcashManager
{
  static $apiendpoint;
  static $appkey;
  static $curl;
  static $header;
  static $appId = 'PLNMOB';

  static function init()
  {
    self::$curl = new Client();
    self::$apiendpoint = config('credentials.iconcash.endpoint');
    self::$appkey = config('credentials.iconcash.api_key');

    $timestamp = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now('Asia/Jakarta'))->timestamp;

    self::$header = [
      'Content-Type'  => 'application/json',
      'appid'         => self::$appId,
      'timestamp'     => $timestamp,
      'token'         => hash_hmac('sha256', self::$appId . $timestamp, self::$appkey)
    ];
  }

  public static function register(string $fullName = "", string $phoneNumber = "", string $pin, int $corporateId = 10, string $email = "")
  {
    $param = static::setParamAPI([]);

    $url = sprintf('%s/%s', self::$apiendpoint, 'api/command/register_customer' . $param);
    
    $response = self::$curl->request('POST', $url, [
      'http_errors' => false,
      'json' => [
        'corporateId' => $corporateId,
        'email'       => $email,
        'fullname'    => $fullName,
        'phoneNumber' => $phoneNumber,
        'pin'         => $pin,
        'sendOtp'     => true
      ]
    ]);

    $response = json_decode($response->getBody());

    throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

    if ($response->success != true) {
      throw new Exception($response->message, $response->code);
    }

    return $response;
  }

  public static function requestOTP($corporateId = 10, $phoneNumber)
  {
    $param = static::setParamAPI([]);

    $url = sprintf('%s/%s', self::$apiendpoint, 'api/command/otp' . $param);
    
    $response = self::$curl->request('POST', $url, [
      'http_errors' => false,
      'json' => [
        'corporateId' => $corporateId,
        'phoneNumber' => $phoneNumber
      ]
    ]);

    $response = json_decode($response->getBody());

    throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

    if ($response->code == 5000) {
      return $response;
    }

    if ($response->success != true) {
      throw new Exception($response->message, $response->code);
    }

    return data_get($response, "data");
  }

  public static function validateOTP(int $corporateId = 10, $phoneNumber = "", $otp = "")
  {
    $param = static::setParamAPI([]);

    $url = sprintf('%s/%s', self::$apiendpoint, 'api/query/otp/validate' . $param);
    
    $response = self::$curl->request('POST', $url, [
      'http_errors' => false,
      'json' => [
        'corporateId' => $corporateId,
        'phoneNumber' => $phoneNumber,
        'otp'         => $otp
      ]
    ]);

    $response = json_decode($response->getBody());

    throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

    if ($response->success != true) {
      throw new Exception($response->message, $response->code);
    }

    return $response;
  }

  public static function login(int $corporateId = 10, $phoneNumber = "", $pin = "")
  {
    $param = static::setParamAPI([]);

    $url = sprintf('%s/%s', self::$apiendpoint, 'api/auth/login' . $param);
    
    $response = self::$curl->request('POST', $url, [
      'http_errors' => false,
      'json' => [
        'corporateId' => $corporateId,
        'phoneNumber' => $phoneNumber,
        'pin'         => $pin
      ]
    ]);

    $response = json_decode($response->getBody());

    throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

    if ($response->success != true) {
      throw new Exception($response->message, $response->code);
    }

    return data_get($response, "data");
  }

  public static function logout($token)
  {
      $param = self::setParamAPI([]);

      $url = sprintf('%s/%s', self::$apiendpoint, 'api/auth/logout' . $param);

      $response = self::$curl->request('POST', $url, [
          'headers' => ['Authorization' => $token],
          'http_errors' => false,
      ]);

      $response = json_decode($response->getBody());

      throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

      if ($response->success != true) {
          throw new Exception($response->message, $response->code);
      }

      return $response;
  }

  public static function getCustomerAllBalance($token = "")
  {
    $param = self::setParamAPI([]);

      $url = sprintf('%s/%s', self::$apiendpoint, 'api/query/balance/customer' . $param);

      $response = self::$curl->request('GET', $url, [
          'headers' => ['Authorization' => $token],
          'http_errors' => false,
      ]);

      $response = json_decode($response->getBody());
      
      throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

      if ($response->success != true) {
          throw new Exception($response->message, $response->code);
      }

      return data_get($response, 'data');
  }

  public static function withdrawalInquiry($token, $bankAccountName, $bankAccountNo, $bankId, $nominal, $sourceAccountId)
  {
    $param = self::setParamAPI([]);

      $url = sprintf('%s/%s', self::$apiendpoint, 'api/command/withdrawal/inquiry' . $param);

      $response = self::$curl->request('POST', $url, [
          'headers' => ['Authorization' => $token],
          'http_errors' => false,
          'json' => [
            'bankAccountName' => $bankAccountName,
            'bankAccountNo' => $bankAccountNo,
            'bankId' => $bankId,
            'nominal' => $nominal,
            'sourceAccountId' => $sourceAccountId
          ]
      ]);

      $response = json_decode($response->getBody());

      throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

      if ($response->success != true) {
          throw new Exception($response->message, $response->code);
      }

      return data_get($response, 'data');
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
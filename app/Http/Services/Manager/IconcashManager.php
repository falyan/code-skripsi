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
  static $headerTopup;
  static $appId = 'PLNMOB';
  static $topupClientId;
  static $topupApiEndpoint;
  static $topupSecretKey;
  static $topupNote = 'Marketplace';

  static function init()
  {
    self::$curl         = new Client();
    self::$apiendpoint  = config('credentials.iconcash.endpoint');
    self::$appkey       = config('credentials.iconcash.api_key');
    
    self::$topupApiEndpoint = config('credentials.iconcash_topup.endpoint');
    self::$topupClientId    = config('credentials.iconcash_topup.client_id');
    self::$topupSecretKey   = config('credentials.iconcash_topup.secret_key');

    $timestamp = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now('Asia/Jakarta'))->timestamp;

    self::$header = [
      'Content-Type'  => 'application/json',
      'appid'         => self::$appId,
      'timestamp'     => $timestamp,
      'token'         => hash_hmac('sha256', self::$appId . $timestamp, self::$appkey)
    ];

    self::$headerTopup = [
      'clientId'  => self::$topupClientId,
      'timestamp' => $timestamp,
      'signature' => hash_hmac('sha256', self::$topupClientId . $timestamp, self::$topupSecretKey)
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

  public static function withdrawal($token, $pin, $orderId)
  {
    $param = self::setParamAPI([]);

    $url = sprintf('%s/%s', self::$apiendpoint, 'api/command/withdrawal' . $param);

    $response = self::$curl->request('POST', $url, [
        'headers' => ['Authorization' => $token, 'Credentials' => $pin],
        'http_errors' => false,
        'json' => [
          'orderId' => $orderId
        ]
    ]);

    $response = json_decode($response->getBody());

    if ($response->code == 5001 || $response->code == 5002) {
      return $response;
    }

    throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

    if ($response->success != true) {
        throw new Exception($response->message, $response->code);
    }

    return data_get($response, 'data');
  }

  /* Topup Services */

  public static function topupInquiry($phone, $account_type_id, $amount, $client_ref, $corporate_id = 10)
  {
    $param = self::setParamAPI([]);

    $url = sprintf('%s/%s', self::$topupApiEndpoint, 'command/topup-inquiry' . $param);

    $response = self::$curl->request('POST', $url, [
        'headers' => self::$headerTopup,
        'http_errors' => false,
        'json' => [
          'accountTypeId' => $account_type_id,
          'amount'        => $amount,
          'clientRef'     => $client_ref,
          'corporateId'   => $corporate_id,
          'phoneNumber'   => $phone
        ]
    ]);

    $response = json_decode($response->getBody());

    throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

    if ($response->success != true) {
        throw new Exception($response->message, $response->code);
    }

    return data_get($response, 'data');
  }

  public static function topupConfirm($order_id, $amount)
  {
    $param = self::setParamAPI([]);

    $url = sprintf('%s/%s', self::$topupApiEndpoint, 'command/topup-confirm' . $param);

    $response = self::$curl->request('POST', $url, [
        'headers' => self::$headerTopup,
        'http_errors' => false,
        'json' => [
          'amount'  => $amount,
          'orderId' => $order_id,
          'note'    => self::$topupNote
        ]
    ]);

    $response = json_decode($response->getBody());
    
    throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

    if ($response->success != true) {
        throw new Exception($response->message, $response->code);
    }

    return data_get($response, 'data');
  }

  public static function getRefBank($token)
  {
    $param = self::setParamAPI([
      'size' => 9999
    ]);

    $url = sprintf('%s/%s', self::$apiendpoint, 'api/query/ref/bank' . $param);

    $response = self::$curl->request('GET', $url, [
        'headers' => ['Authorization' => $token],
        'http_errors' => false
    ]);

    $response = json_decode($response->getBody());
    
    throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

    if ($response->success != true) {
        throw new Exception($response->message, $response->code);
    }

    return data_get($response, 'data');
  }

  public static function addCustomerBank($token, $account_name, $account_number, $bank_id)
  {
    $param = self::setParamAPI([]);

    $url = sprintf('%s/%s', self::$apiendpoint, 'api/command/customerbank' . $param);

    $response = self::$curl->request('POST', $url, [
        'headers' => ['Authorization' => $token],
        'http_errors' => false,
        'json' => [
          'accountName' => $account_name,
          'accountNumber' => $account_number,
          'bankId' => $bank_id,
          'id' => 0
        ]
    ]);

    $response = json_decode($response->getBody());
    
    throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

    if ($response->success != true) {
        throw new Exception($response->message, $response->code);
    }

    return data_get($response, 'data');
  }

  public static function searchCustomerBank($token, $keyword)
  {
    $param = self::setParamAPI([
      'keyword' => $keyword,
      'size' => 9999
    ]);

    $url = sprintf('%s/%s', self::$apiendpoint, 'api/query/customerbank/search' . $param);

    $response = self::$curl->request('GET', $url, [
        'headers' => ['Authorization' => $token],
        'http_errors' => false
    ]);

    $response = json_decode($response->getBody());
    
    throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

    if ($response->success != true) {
        throw new Exception($response->message, $response->code);
    }

    return data_get($response, 'data');
  }

  public static function getCustomerBankById($token, $id)
  {
    $param = self::setParamAPI([
      'id' => $id
    ]);

    $url = sprintf('%s/%s', self::$apiendpoint, 'api/query/customerbank/byid' . $param);

    $response = self::$curl->request('GET', $url, [
        'headers' => ['Authorization' => $token],
        'http_errors' => false
    ]);

    $response = json_decode($response->getBody());
    
    throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

    if ($response->success != true) {
        throw new Exception($response->message, $response->code);
    }

    return data_get($response, 'data');
  }

  public static function deleteCustomerBank($token, $id)
  {
    $param = self::setParamAPI([
      'id' => $id
    ]);

    $url = sprintf('%s/%s', self::$apiendpoint, 'api/command/customerbank' . $param);

    $response = self::$curl->request('DELETE', $url, [
        'headers' => ['Authorization' => $token],
        'http_errors' => false
    ]);

    $response = json_decode($response->getBody());
    
    throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

    if ($response->success != true) {
        throw new Exception($response->message, $response->code);
    }

    return $response;
  }

  public static function updateCustomerBank($token, $customer_bank_id, $account_name, $account_number, $bank_id)
  {
    $param = self::setParamAPI([]);

    $url = sprintf('%s/%s', self::$apiendpoint, 'api/command/customerbank' . $param);

    $response = self::$curl->request('PUT', $url, [
        'headers' => ['Authorization' => $token],
        'http_errors' => false,
        'json' => [
          'id' => $customer_bank_id,
          'accountName' => $account_name,
          'accountNumber' => $account_number,
          'bankId' => $bank_id,
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
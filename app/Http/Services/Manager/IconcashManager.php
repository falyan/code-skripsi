<?php

namespace App\Http\Services\Manager;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class IconcashManager
{
    static $apiendpoint;
    static $apiendpointintegrator;
    static $appkey;
    static $curl;
    static $header;
    static $headerTopup;
    static $appId = 'PLNMOB';
    static $topupClientId;
    static $topupApiEndpoint;
    static $topupSecretKey;
    static $topupDepositClientId;
    static $topupDepositApiEndpoint;
    static $topupDepositSecretKey;
    static $topupNote = 'Marketplace';
    static $appIdTopup = 'marketplace_agent';
    static $headerAgentTopup;
    static $headerTopupDeposit;
    static $keyTopup;

    static $partner_id;
    static $channel_id;
    static $bank_code;

    public static function init()
    {
        self::$curl = new Client();
        self::$apiendpoint = config('credentials.iconcash.endpoint');
        self::$apiendpointintegrator = config('credentials.iconcash.endpoint_integrator');
        self::$appkey = config('credentials.iconcash.api_key');
        self::$keyTopup = config('credentials.iconcash.agent_secret_key');

        self::$topupApiEndpoint = config('credentials.iconcash_topup.endpoint');
        self::$topupClientId = config('credentials.iconcash_topup.client_id');
        self::$topupSecretKey = config('credentials.iconcash_topup.secret_key');

        self::$topupDepositApiEndpoint = config('credentials.iconcash_topup_deposit.endpoint');
        self::$topupDepositClientId = config('credentials.iconcash_topup_deposit.client_id');
        self::$topupDepositSecretKey = config('credentials.iconcash_topup_deposit.secret_key');

        self::$partner_id = config('credentials.agent.v3.partner_id');
        self::$channel_id = config('credentials.agent.v3.channel_id');
        self::$bank_code = config('credentials.agent.v3.bank_code');

        $timestamp = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now('Asia/Jakarta'))->timestamp;
        $timestamp_topup = Carbon::now('Asia/Jakarta')->timestamp;
        $hmacStrTopup = self::$appIdTopup . $timestamp_topup;

        self::$header = [
            'Content-Type' => 'application/json',
            'appid' => self::$appId,
            'timestamp' => $timestamp,
            'token' => hash_hmac('sha256', self::$appId . $timestamp, self::$appkey),
        ];

        self::$headerTopup = [
            'clientId' => self::$topupClientId,
            'timestamp' => $timestamp_topup,
            'signature' => hash_hmac('sha256', self::$topupClientId . $timestamp_topup, self::$topupSecretKey),
            'app_source' => 'marketplace',
        ];

        self::$headerTopup = [
            'clientId' => self::$topupClientId,
            'timestamp' => $timestamp_topup,
            'signature' => hash_hmac('sha256', self::$topupClientId . $timestamp_topup, self::$topupSecretKey),
            'app_source' => 'marketplace',
        ];

        self::$headerTopupDeposit = [
            'clientId' => self::$topupDepositClientId,
            'timestamp' => $timestamp_topup,
            'signature' => hash_hmac('sha256', self::$topupDepositClientId . $timestamp_topup, self::$topupDepositSecretKey),
        ];
    }

    public static function register(string $fullName = "", string $phoneNumber = "", string $pin, int $corporateId = 10, string $email = "")
    {
        $param = static::setParamAPI([]);

        $url = sprintf('%s/%s', self::$apiendpoint, 'api/command/register_customer' . $param);

        $response = self::$curl->request('POST', $url, [
            'headers' => ['app_source' => 'marketplace'],
            'http_errors' => false,
            'json' => [
                'corporateId' => $corporateId,
                'email' => $email,
                'fullname' => $fullName,
                'phoneNumber' => $phoneNumber,
                'pin' => $pin,
                'sendOtp' => true,
            ],
        ]);

        $response = json_decode($response->getBody());

        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

        if ($response->code == 5006) {
            return $response;
        }

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
                'phoneNumber' => $phoneNumber,
            ],
        ]);

        $response = json_decode($response->getBody());

        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

        if ($response->code == 5000 || $response->code == 5006) {
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
                'otp' => $otp,
            ],
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
            'headers' => ['app_source' => 'marketplace'],
            'http_errors' => false,
            'json' => [
                'corporateId' => $corporateId,
                'phoneNumber' => $phoneNumber,
                'pin' => $pin,
            ],
        ]);

        $response = json_decode($response->getBody());

        if ($response->code == 5001 || $response->code == 5002 || $response->code == 5003 || $response->code == 5004 || $response->code == 5006) {
            return $response;
        }

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
            'headers' => [
                'Authorization' => $token,
                'app_source' => 'marketplace',
            ],
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
            'headers' => [
                'Authorization' => $token,
                'app_source' => 'marketplace',
            ],
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
            'headers' => [
                'Authorization' => $token,
                'app_source' => 'marketplace',
            ],
            'http_errors' => false,
            'json' => [
                'bankAccountName' => $bankAccountName,
                'bankAccountNo' => $bankAccountNo,
                'bankId' => $bankId,
                'nominal' => $nominal,
                'sourceAccountId' => $sourceAccountId,
            ],
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
            'headers' => [
                'Authorization' => $token,
                'Credentials' => $pin,
                'app_source' => 'marketplace',
            ],
            'http_errors' => false,
            'json' => [
                'orderId' => $orderId,
            ],
        ]);

        $response = json_decode($response->getBody());

        if ($response->code == 5001 || $response->code == 5002 || $response->code == 5003 || $response->code == 5004 || $response->code == 5006) {
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
                'amount' => $amount,
                'clientRef' => $client_ref,
                'corporateId' => $corporate_id,
                'phoneNumber' => $phone,
            ],
        ]);

        $response = json_decode($response->getBody());

        Log::info('topupInquiry', [
            'response' => $response,
        ]);

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
                'amount' => $amount,
                'orderId' => $order_id,
                'note' => self::$topupNote,
            ],
        ]);

        $response = json_decode($response->getBody());

        Log::info('topupConfirm', [
            'response' => $response,
        ]);

        // throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

        // if ($response->success != true) {
        //     throw new Exception($response->message, $response->code);
        // }

        return $response;
    }

    public static function getRefBank($token)
    {
        $param = self::setParamAPI([
            'size' => 9999,
        ]);

        $url = sprintf('%s/%s', self::$apiendpoint, 'api/query/ref/bank' . $param);

        $response = self::$curl->request('GET', $url, [
            'headers' => [
                'Authorization' => $token,
                'app_source' => 'marketplace',
            ],
            'http_errors' => false,
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
            'headers' => [
                'Authorization' => $token,
                'app_source' => 'marketplace',
            ],
            'http_errors' => false,
            'json' => [
                'accountName' => $account_name,
                'accountNumber' => $account_number,
                'bankId' => $bank_id,
                'id' => 0,
            ],
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
            'size' => 9999,
        ]);

        $url = sprintf('%s/%s', self::$apiendpoint, 'api/query/customerbank/search' . $param);

        $response = self::$curl->request('GET', $url, [
            'headers' => [
                'Authorization' => $token,
                'app_source' => 'marketplace',
            ],
            'http_errors' => false,
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
            'id' => $id,
        ]);

        $url = sprintf('%s/%s', self::$apiendpoint, 'api/query/customerbank/byid' . $param);

        $response = self::$curl->request('GET', $url, [
            'headers' => [
                'Authorization' => $token,
                'app_source' => 'marketplace',
            ],
            'http_errors' => false,
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
            'id' => $id,
        ]);

        $url = sprintf('%s/%s', self::$apiendpoint, 'api/command/customerbank' . $param);

        $response = self::$curl->request('DELETE', $url, [
            'headers' => [
                'Authorization' => $token,
                'app_source' => 'marketplace',
            ],
            'http_errors' => false,
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
            'headers' => [
                'Authorization' => $token,
                'app_source' => 'marketplace',
            ],
            'http_errors' => false,
            'json' => [
                'id' => $customer_bank_id,
                'accountName' => $account_name,
                'accountNumber' => $account_number,
                'bankId' => $bank_id,
            ],
        ]);

        $response = json_decode($response->getBody());

        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

        if ($response->success != true) {
            throw new Exception($response->message, $response->code);
        }

        return data_get($response, 'data');
    }

    public static function changePin($token, $old_pin, $new_pin, $confirm_new_pin)
    {
        $param = self::setParamAPI([]);

        $url = sprintf('%s/%s', self::$apiendpoint, 'api/auth/change-pin' . $param);

        $response = self::$curl->request('POST', $url, [
            'headers' => [
                'Authorization' => $token,
                'app_source' => 'marketplace',
            ],
            'http_errors' => false,
            'json' => [
                'confirmPin' => $confirm_new_pin,
                'newPin' => $new_pin,
                'oldPin' => $old_pin,
            ],
        ]);

        $response = json_decode($response->getBody());

        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

        if ($response->success != true) {
            throw new Exception($response->message, $response->code);
        }

        return $response;
    }

    public static function forgotPin($token, $otp, $new_pin, $confirm_new_pin, $phone, $corporate_id = 10)
    {
        $param = self::setParamAPI([]);

        $url = sprintf('%s/%s', self::$apiendpoint, 'api/auth/forgot-pin' . $param);

        $response = self::$curl->request('POST', $url, [
            'headers' => [
                'Authorization' => $token,
                'app_source' => 'marketplace',
            ],
            'http_errors' => false,
            'json' => [
                'confirmPin' => $confirm_new_pin,
                'corporateId' => $corporate_id,
                'newPin' => $new_pin,
                'otp' => $otp,
                'phoneNumber' => $phone,
            ],
        ]);

        $response = json_decode($response->getBody());

        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

        if ($response->success != true) {
            throw new Exception($response->message, $response->code);
        }

        return $response;
    }

    public static function historySaldo($token, $account_type_id)
    {
        $param = self::setParamAPI([]);

        $url = sprintf('%s/%s', self::$apiendpoint, 'api/query/history/customer' . $param);

        $response = self::$curl->request('GET', $url, [
            'headers' => [
                'Authorization' => $token,
                'app_source' => 'marketplace',
            ],
            'http_errors' => false,
            'json' => [
                'accountTypeId' => $account_type_id,
            ],
        ]);

        $response = json_decode($response->getBody());

        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

        if ($response->success != true) {
            throw new Exception($response->message, $response->code);
        }

        return data_get($response, 'data');
    }

    // Agent Iconcash Services

    public static function paymentProcess($data, $token, $fee, $amount, $amount_fee)
    {
        $param = self::setParamAPI([]);

        $url = sprintf('%s/%s', self::$apiendpointintegrator, 'api/command/order/direct' . $param);

        $kodeKonter = $data['kode_konter'];
        $kodeGateway = $data['kode_gateway']; // generate by id agent
        $kodeChanelId = substr(self::$channel_id, -2);
        $kodeProduct = (string) $data['kode_product'];
        $kodeBankPenampungan = self::$bank_code;
        $parnerId = self::$partner_id;
        $partnerRefrence = (string) time() . substr($data['transaction_id'], 0, 9);

        // return [
        //     'kodeKonter' => $kodeKonter,
        //     'kodeGateway' => $kodeGateway,
        //     'kodeChanelId' => $kodeChanelId,
        //     'kodeProduct' => $kodeProduct,
        //     'kodeBankPenampungan' => $kodeBankPenampungan,
        //     'parnerId' => $parnerId,
        //     'partnerRefrence' => strlen($partnerRefrence),
        // ];

        $client_ref = $kodeKonter . $kodeGateway . $kodeChanelId . $kodeProduct . $kodeBankPenampungan . $parnerId . $partnerRefrence;

        $body = [
            'amount' => (int) $amount,
            'fee' => (int) $fee,
            'amountFee' => (int) $amount_fee,
            'clientRef' => $client_ref,
            // 'id' => $trxId,
            'items' => json_encode([
                'refid' => $data['transaction_id'],
                'amount' => $amount,
            ]),
            // 'merchantId' => '',
            // 'merchantName' => '',
            'storeId' => $data['store_id'],
            // 'storeName' => '',
            'terminalId' => $data['terminal_id'],
        ];

        // $timestamp = time();
        // $appId = 'ICONCASH';
        // $key = $appId . 'iconcash123' . $timestamp;
        // $payload = json_encode($body);
        // $signature = base64_encode(hash_hmac('sha256', $payload, $key, true));

        $header = [
            // 'appId' => $appId,
            // 'timestamp' => $timestamp,
            // 'signature'    => $signature,
            'Authorization' => $token,
            'Content-type' => 'application/json',
        ];

        // return [
        //     'url' => $url,
        //     'header' => $header,
        //     'body' => $body,
        // ];

        $response = self::$curl->request('POST', $url, [
            'headers' => $header,
            'http_errors' => false,
            'json' => $body,
        ]);

        $response = json_decode($response->getBody(), true);
        // return $response;

        Log::info("E00002", [
            'path_url' => "agent.v2.endpoint/iconcash/api/command/order/direct",
            'query' => [],
            'body' => $body,
            'response' => $response,
        ]);

        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

        // if ($response->success != true) {
        //     throw new Exception($response->message, $response->code);
        // }

        return $response;
    }

    public static function paymentConfirm($accountPin, $sourceAccountId, $client_ref, $paymentRef, $token)
    {
        $param = self::setParamAPI([]);

        $url = sprintf('%s/%s', self::$apiendpointintegrator, 'api/command/payment' . $param);

        $body = [
            // "accounts"  => [
            //     [
            //         "amount" => $amount,
            //         "sourceAccountId" => $sourceAccountId,
            //     ]
            // ],
            "clientRef" => $client_ref,
            // "customerId"   => $customerId,
            "orderId" => $paymentRef,
            "sourceAccountId" => $sourceAccountId,
        ];

        // $timestamp = time();
        // $appId = 'ICONCASH';
        // $key = $appId . 'iconcash123' . $timestamp;
        // $payload = json_encode($body);
        // $signature = base64_encode(hash_hmac('sha256', $payload, $key, true));

        $header = [
            // 'appId' => $appId,
            // 'timestamp' => $timestamp,
            // 'signature'    => $signature,
            'credentials' => $accountPin,
            'Authorization' => $token,
            'Content-type' => 'application/json',
        ];

        // return [
        //     'url' => $url,
        //     'header' => $header,
        //     'body' => $body,
        // ];

        $response = self::$curl->request('POST', $url, [
            'headers' => $header,
            'http_errors' => false,
            'json' => $body,
        ]);

        $response = json_decode($response->getBody(), true);
        // return $response;

        Log::info("E00002", [
            'path_url' => "agent.v2.endpoint/iconcash/api/command/payment",
            'query' => [],
            'body' => $body,
            'response' => $response,
        ]);

        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

        // if ($response->success != true) {
        //     throw new Exception($response->message, $response->code);
        // }

        return $response;
    }

    public static function paymentRefund($client_ref, $paymentRef, $token, $sourceAccountId)
    {
        $param = self::setParamAPI([]);

        $url = sprintf('%s/%s', self::$apiendpointintegrator, 'api/command/refund' . $param);

        $body = [
            // "accounts"  => [
            //     [
            //         "amount" => $amount,
            //         "sourceAccountId" => $sourceAccountId,
            //     ]
            // ],
            "clientRef" => $client_ref,
            // "customerId"   => $customerId,
            "orderId" => $paymentRef,
            "sourceAccountId" => (int) $sourceAccountId,
        ];

        // $timestamp = time();
        // $appId = 'ICONCASH';
        // $key = $appId . 'iconcash123' . $timestamp;
        // $payload = json_encode($body);
        // $signature = base64_encode(hash_hmac('sha256', $payload, $key, true));

        $header = [
            // 'appId' => $appId,
            // 'timestamp' => $timestamp,
            // 'signature'    => $signature,
            // 'credentials' => '521a99e009e848639fbe45deef9ac803356974b4913187894bcc2a9634ae98ad',
            'Authorization' => $token,
            'Content-type' => 'application/json',
        ];

        // return [
        //     'url' => $url,
        //     'header' => $header,
        //     'body' => $body,
        // ];

        $response = self::$curl->request('POST', $url, [
            'headers' => $header,
            'http_errors' => false,
            'json' => $body,
        ]);

        $response = json_decode($response->getBody(), true);
        // return $response;

        Log::info("E00002", [
            'path_url' => "agent.v2.endpoint/iconcash/api/command/refund",
            'query' => [],
            'body' => $body,
            'response' => $response,
        ]);

        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

        // if ($response->success != true) {
        //     throw new Exception($response->message, $response->code);
        // }

        return $response;
    }

    public static function agentHistorySaldo($limit, $page, $token)
    {
        $param = self::setParamAPI([
            'size' => $limit,
            'page' => $page,
        ]);

        $url = sprintf('%s/%s', self::$apiendpointintegrator, 'api/query/history/customer' . $param);

        $response = self::$curl->request('GET', $url, [
            'headers' => [
                'Authorization' => $token,
            ],
            'http_errors' => false,
        ]);

        $response = json_decode($response->getBody());

        Log::info("E00002", [
            'path_url' => "agent.v2.endpoint/iconcash/api/query/history/customer",
            'query' => [],
            'body' => '',
            'response' => $response,
        ]);

        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

        if ($response->success != true) {
            throw new Exception($response->message, $response->code);
        }

        return data_get($response, 'data');
    }

    public static function registerDeposit($token)
    {
        $param = self::setParamAPI([]);

        $url = sprintf('%s/%s', self::$apiendpointintegrator, 'api/command/register_plnagent' . $param);

        $response = self::$curl->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
            'http_errors' => false,
        ]);

        $response = json_decode($response->getBody(), true);

        Log::info("E00002", [
            'path_url' => "agent.v2.endpoint/iconcash/api/command/register_plnagent",
            'query' => [],
            'body' => '',
            'response' => $response,
        ]);

        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

        if ($response['success'] != true) {
            return [
                'success' => false,
                'message' => $response['message'],
            ];
        }

        return $response;
    }

    public static function getVADeposit($token)
    {
        $param = self::setParamAPI([]);

        $url = sprintf('%s/%s', self::$apiendpointintegrator, 'api/query/getva_plnagent' . $param);

        $response = self::$curl->request('GET', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
            'http_errors' => false,
        ]);

        $response = json_decode($response->getBody(), true);

        Log::info("E00002", [
            'path_url' => "agent.v2.endpoint/iconcash/api/command/register_plnagent",
            'query' => [],
            'body' => '',
            'response' => $response,
        ]);

        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

        if ($response['success'] != true) {
            return [
                'success' => false,
                'message' => $response['message'],
            ];
        }

        return $response;
    }

    public static function topupDeposit($token, $amount, $clientRef)
    {
        $param = self::setParamAPI([]);

        $url = sprintf('%s/%s', self::$topupDepositApiEndpoint, 'api/command/topup/push-order' . $param);

        $response = self::$curl->request('POST', $url, [
            'headers' => array_merge([
                'Authorization' => 'Bearer ' . $token,
            ], self::$headerTopupDeposit),
            'http_errors' => false,
            'json' => [
                'amount' => $amount,
                'clientRef' => $clientRef,
            ],
        ]);

        $response = json_decode($response->getBody());

        Log::info('topupDeposit', [
            'response' => $response,
        ]);

        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

        return data_get($response, 'data');
    }

    public static function checkFeeTopupDeposit($token, $orderId, $pspId, $totalAmount)
    {
        $param = self::setParamAPI([]);

        $url = sprintf('%s/%s', self::$topupDepositApiEndpoint, 'api/command/topup/check-fee' . $param);

        $response = self::$curl->request('POST', $url, [
            'headers' => array_merge([
                'Authorization' => 'Bearer ' . $token,
            ], self::$headerTopupDeposit),
            'http_errors' => false,
            'json' => [
                'orderId' => $orderId,
                'paymentMethods' => [
                    [
                        'pspId' => $pspId,
                        'totalAmount' => $totalAmount,
                    ],
                ],
            ],
        ]);

        $response = json_decode($response->getBody());

        Log::info('checkFeeTopupDeposit', [
            'response' => $response,
        ]);

        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

        return data_get($response, 'data');
    }

    public static function confirmTopupDeposit($token, $orderId, $pspId, $totalAmount)
    {
        $param = self::setParamAPI([]);

        $url = sprintf('%s/%s', self::$topupDepositApiEndpoint, 'api/command/topup/confirm-payment' . $param);

        $response = self::$curl->request('POST', $url, [
            'headers' => array_merge([
                'Authorization' => 'Bearer ' . $token,
            ], self::$headerTopupDeposit),
            'http_errors' => false,
            'json' => [
                'orderId' => $orderId,
                'pspId' => $pspId,
                'totalAmount' => $totalAmount,
            ],
        ]);

        $response = json_decode($response->getBody());

        Log::info('confirmPaymentTopupDeposit', [
            'response' => $response,
        ]);

        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

        return data_get($response, 'data');
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
}

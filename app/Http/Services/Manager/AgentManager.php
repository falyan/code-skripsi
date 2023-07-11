<?php

namespace App\Http\Services\Manager;

use App\Models\AgentOrder;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AgentManager
{
    static $source = 'ICONCASH';
    static $api_key;
    static $secret_key;
    static $now;

    static $endpoint;
    static $username;
    static $password;
    static $header;
    static $curl;
    static $bearer_token;

    static $endpointv3;
    static $partner_id;
    static $client_id;
    static $client_secret;
    static $channel_id;
    static $timestamp;
    static $radagast_endpoint;
    static $radagast_agregator;

    public static function init()
    {
        self::$now = Carbon::now('Asia/Jakarta');
        self::$curl = new Client();

        self::$endpointv3 = config('credentials.agent.v3.endpoint');
        self::$partner_id = config('credentials.agent.v3.partner_id');
        self::$client_id = config('credentials.agent.v3.client_id');
        self::$client_secret = config('credentials.agent.v3.client_secret');
        self::$channel_id = config('credentials.agent.v3.channel_id');
        // self::$timestamp = self::$now->format('Y-m-d') . 'T' . self::$now->format('H:i:s.u');
        //get 3 digit milisecond
        $milisecond = substr((string) self::$now->format('u'), 0, 3);
        self::$timestamp = self::$now->format('Y-m-d') . 'T' . self::$now->format('H:i:s') . '.' . $milisecond . '+07:00';
    }

    public function getToken()
    {
        try {
            $url = sprintf('%s/%s', static::$endpoint, '/gettoken');

            $response = static::$curl->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode(static::$username . ':' . static::$password),
                ],
            ]);

            $response = json_decode($response->getBody(), true);

            throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

            $data = $response['data'];
            DB::beginTransaction();

            // STORE DATA TOKEN TO DB

            // InquiryToken::where('status', 1)->update(['status' => 0]);
            // InquiryToken::create([
            //     'token' => $data['token'],
            //     'type' => $data['type'],
            //     'status' => 1,
            // ]);
            DB::commit();

            return $response;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    // ========== API ICONPAY V3 =========== //

    // ==== PLN Postpaid & Prepaid Product
    public static function inquiryPostpaidV3($request)
    {
        $params = static::setParamAPI([]);

        $url = sprintf('%s/%s', self::$endpointv3, 'pln/inquiry' . $params);
        $body = [
            'customer_id' => data_get($request, 'customer_id'),
            'channel_id' => self::$channel_id,
            'product_id' => 'POSTPAID',
        ];

        $encode_body = json_encode($body, JSON_UNESCAPED_SLASHES);

        $key = sha1(self::$client_secret);
        $payload = $encode_body . self::$client_id . self::$timestamp;
        // dd($payload);

        $headers = static::headers([
            'timestamp' => self::$timestamp,
            'signature' => hash_hmac('sha256', $payload, $key),
        ]);

        $response = static::$curl->request('POST', $url, [
            'headers' => $headers,
            'http_errors' => false,
            'json' => $body,
        ]);

        // dd($headers, $url, $body);

        $response = json_decode($response->getBody(), true);
        // dd($response);

        Log::info("E00002", [
            'path_url' => "agent.v3.endpoint/pln/inquiry/postpaid",
            'query' => [],
            'body' => $body,
            'response' => $response,
        ]);

        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

        return $response;
    }

    public static function inquiryPrepaidV3($request)
    {
        $params = static::setParamAPI([]);

        $url = sprintf('%s/%s', self::$endpointv3, 'pln/inquiry' . $params);
        $body = [
            'customer_id' => data_get($request, 'customer_id'),
            'channel_id' => self::$channel_id,
            'product_id' => 'PREPAID',
        ];

        $encode_body = json_encode($body, JSON_UNESCAPED_SLASHES);

        $key = sha1(self::$client_secret);
        $payload = $encode_body . self::$client_id . self::$timestamp;
        // dd($payload);

        $headers = static::headers([
            'timestamp' => self::$timestamp,
            'signature' => hash_hmac('sha256', $payload, $key),
        ]);

        // dd($headers);

        $response = static::$curl->request('POST', $url, [
            'headers' => $headers,
            'http_errors' => false,
            'json' => $body,
        ]);

        $response = json_decode($response->getBody(), true);

        Log::info("E00002", [
            'path_url' => "agent.v3.endpoint/inquiry/prepaid",
            'query' => [],
            'body' => $body,
            'response' => $response,
        ]);

        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

        return $response;
    }

    // Payment for PLN Postpaid & Prepaid
    public static function confirmOrderIconcash($request, $timeout = null, $set_delay = null)
    {
        $order = AgentOrder::where('trx_no', $request['trx_no'])->first();

        $params = static::setParamAPI([]);

        $url = sprintf('%s/%s', self::$endpointv3, 'pln/payment' . $params);

        $body = [
            'transaction_id' => data_get($request, 'trx_no'),
            'partner_reference' => $request['client_ref'],
            'channel_id' => self::$channel_id,
            'amount' => (int) $request['amount'],
            'buying_options' => (int) $request['buying_options'],
        ];

        $encode_body = json_encode($body, JSON_UNESCAPED_SLASHES);

        $key = sha1(self::$client_secret);
        $payload = $encode_body . self::$client_id . self::$timestamp;

        $headers = static::headers([
            'timestamp' => self::$timestamp,
            'signature' => hash_hmac('sha256', $payload, $key),
        ]);

        $http = [
            'headers' => $headers,
            'http_errors' => false,
            'json' => $body,
        ];

        try {
            $response = static::$curl->request('POST', $url, !is_null($timeout) ? array_merge($http, ['delay' => $set_delay, 'timeout' => $timeout]) : $http);

            // if ($order->payment->payment_scenario == 'reversal') {
            //     sleep(30);
            // } else if ($order->payment->payment_scenario == 'repeat-reversal') {
            //     sleep(50);
            // }

            $response = json_decode($response->getBody(), true);
            // return $response;

            Log::info("E00002", [
                'path_url' => "agent.v2.endpoint/iconpay/pln/payment",
                'query' => [],
                'body' => $body,
                'response' => $response,
            ]);

            throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

            // if ($response->success != true) {
            //     throw new Exception($response->message, $response->code);
            // }

            return $response;
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            return [
                'response_code' => '408',
                'transaction_detail' => null,
            ];
        }
    }
    // ==== End of PLN Postpaid & Prepaid

    // ==== Iconnet Product
    // Inquiry for Iconnet
    public static function inquiryIconnetV3($request)
    {
        $params = static::setParamAPI([]);

        $url = sprintf('%s/%s', self::$endpointv3, 'iconnet/inquiry' . $params);
        $body = [
            'customer_id' => data_get($request, 'customer_id'),
            'channel_id' => self::$channel_id,
            'product_id' => 'ICONNET',
        ];

        $encode_body = json_encode($body, JSON_UNESCAPED_SLASHES);

        $key = sha1(self::$client_secret);
        $payload = $encode_body . self::$client_id . self::$timestamp;
        // dd($payload);

        $headers = static::headers([
            'timestamp' => self::$timestamp,
            'signature' => hash_hmac('sha256', $payload, $key),
        ]);

        // dd($headers);

        $response = static::$curl->request('POST', $url, [
            'headers' => $headers,
            'http_errors' => false,
            'json' => $body,
        ]);

        $response = json_decode($response->getBody(), true);

        Log::info("E00002", [
            'path_url' => "agent.v3.endpoint/inquiry/iconnet",
            'query' => [],
            'body' => $body,
            'response' => $response,
        ]);

        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

        return $response;
    }

    // Payment for Iconnet
    public static function confirmOrderIconnet($request, $timeout = null, $set_delay = null)
    {
        $params = static::setParamAPI([]);

        $url = sprintf('%s/%s', self::$endpointv3, 'iconnet/payment' . $params);

        $body = [
            'transaction_id' => data_get($request, 'trx_no'),
            'partner_reference' => $request['client_ref'],
            'channel_id' => self::$channel_id,
            'amount' => (int) $request['amount'],
        ];

        $encode_body = json_encode($body, JSON_UNESCAPED_SLASHES);

        $key = sha1(self::$client_secret);
        $payload = $encode_body . self::$client_id . self::$timestamp;

        $headers = static::headers([
            'timestamp' => self::$timestamp,
            'signature' => hash_hmac('sha256', $payload, $key),
        ]);

        $http = [
            'headers' => $headers,
            'http_errors' => false,
            'json' => $body,
        ];

        try {
            $response = static::$curl->request('POST', $url, !is_null($timeout) ? array_merge($http, ['delay' => $set_delay, 'timeout' => $timeout]) : $http);

            $response = json_decode($response->getBody(), true);
            // return $response;

            Log::info("E00002", [
                'path_url' => "agent.v3.endpoint/iconpay/iconnet/payment",
                'query' => [],
                'body' => $body,
                'response' => $response,
            ]);

            throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

            // if ($response->success != true) {
            //     throw new Exception($response->message, $response->code);
            // }

            return $response;
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            return [
                'response_code' => '408',
                'transaction_detail' => null,
            ];
        }
    }

    // Check Status Payment for Iconnet
    public static function checkStatusPaymentIconnet($request)
    {
        $params = static::setParamAPI([]);

        $url = sprintf('%s/%s', self::$endpointv3, 'iconnet/payment/status' . $params);

        $body = [
            'transaction_id' => $request['trx_no'],
            'biller_reference' => $request['client_ref'],
        ];

        $encode_body = json_encode($body, JSON_UNESCAPED_SLASHES);

        $key = sha1(self::$client_secret);
        $payload = $encode_body . self::$client_id . self::$timestamp;

        $headers = static::headers([
            'timestamp' => self::$timestamp,
            'signature' => hash_hmac('sha256', $payload, $key),
        ]);

        $response = static::$curl->request('POST', $url, [
            'headers' => $headers,
            'http_errors' => false,
            'json' => $body,
        ]);

        $response = json_decode($response->getBody(), true);

        Log::info("E00002", [
            'path_url' => "agent.v3.endpoint/iconnet/payment/status",
            'query' => [],
            'body' => $body,
            'response' => $response,
        ]);

        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

        return $response;
    }
    // ==== End of Iconnet Product

    public static function reversalPostpaidV3($request, $timeout = null)
    {
        $params = static::setParamAPI([]);

        $url = sprintf('%s/%s', self::$endpointv3, 'pln/reversal' . $params);

        $body = [
            'transaction_id' => data_get($request, 'trx_no'),
            'partner_reference' => $request['client_ref'],
            'product_id' => 'POSTPAID',
            'type' => $request['type'],
        ];

        $encode_body = json_encode($body, JSON_UNESCAPED_SLASHES);

        $key = sha1(self::$client_secret);
        $payload = $encode_body . self::$client_id . self::$timestamp;

        $headers = static::headers([
            'timestamp' => self::$timestamp,
            'signature' => hash_hmac('sha256', $payload, $key),
        ]);

        $http = [
            'headers' => $headers,
            'http_errors' => false,
            'json' => $body,
        ];

        try {
            $response = static::$curl->request('POST', $url, !is_null($timeout) ? array_merge($http, ['timeout' => $timeout]) : $http);

            $response = json_decode($response->getBody(), true);

            Log::info("E00002", [
                'path_url' => "agent.v2.endpoint/pln/reversal",
                'query' => [],
                'body' => $body,
                'response' => $response,
            ]);

            throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

            return $response;
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            return [
                'response_code' => '408',
                'transaction_detail' => null,
            ];
        }
    }

    public static function advicePrepaidV3($request, $timeout = null)
    {
        $params = static::setParamAPI([]);

        $url = sprintf('%s/%s', self::$endpointv3, 'pln/advice' . $params);

        $body = [
            'transaction_id' => data_get($request, 'trx_no'),
            'partner_reference' => $request['client_ref'],
            'type' => $request['type'],
            'amount' => (int) $request['amount'],
        ];

        $encode_body = json_encode($body, JSON_UNESCAPED_SLASHES);

        $key = sha1(self::$client_secret);
        $payload = $encode_body . self::$client_id . self::$timestamp;

        $headers = static::headers([
            'timestamp' => self::$timestamp,
            'signature' => hash_hmac('sha256', $payload, $key),
        ]);

        $http = [
            'headers' => $headers,
            'http_errors' => false,
            'json' => $body,
        ];

        try {
            $response = static::$curl->request('POST', $url, !is_null($timeout) ? array_merge($http, ['timeout' => $timeout]) : $http);

            $response = json_decode($response->getBody(), true);

            Log::info("E00002", [
                'path_url' => "agent.v2.endpoint/pln/advice",
                'query' => [],
                'body' => $body,
                'response' => $response,
            ]);

            throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

            return $response;
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            return [
                'response_code' => '408',
                'transaction_detail' => null,
            ];
        }
    }

    public static function checkStatusPayment($request)
    {
        $params = static::setParamAPI([]);

        $url = sprintf('%s/%s', self::$endpointv3, 'pln/payment/status' . $params);

        $body = [
            'transaction_id' => data_get($request, 'trx_no'),
            'product_id' => data_get($request, 'product_id'),
        ];

        $encode_body = json_encode($body, JSON_UNESCAPED_SLASHES);

        $key = sha1(self::$client_secret);
        $payload = $encode_body . self::$client_id . self::$timestamp;

        $headers = static::headers([
            'timestamp' => self::$timestamp,
            'signature' => hash_hmac('sha256', $payload, $key),
        ]);

        $response = static::$curl->request('POST', $url, [
            'headers' => $headers,
            'http_errors' => false,
            'json' => $body,
        ]);

        $response = json_decode($response->getBody(), true);

        Log::info("E00002", [
            'path_url' => "agent.v2.endpoint/pln/payment/status",
            'query' => [],
            'body' => $body,
            'response' => $response,
        ]);

        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

        return $response;
    }

    public static function headers(array $headers = []): array
    {
        return array_merge([
            'partnerId' => self::$partner_id,
            'clientId' => self::$client_id,
            'Content-Type' => 'application/json',
        ], $headers);
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

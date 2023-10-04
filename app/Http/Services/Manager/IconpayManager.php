<?php

namespace App\Http\Services\Manager;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use LogService;

class IconpayManager
{
    static $apiendpoint;
    static $appkey;
    static $curl;
    static $clientid;
    static $productid;
    static $appsource;
    static $timestamp;
    static $header;
    static $service_code = 'iconpay';

    public static function init()
    {
        self::$curl = new Client();
        self::$apiendpoint = config('credentials.iconpay.endpoint');
        self::$appkey = config('credentials.iconpay.app_key');
        self::$clientid = config('credentials.iconpay.client_id');
        self::$productid = config('credentials.iconpay.product_id');
        self::$appsource = config('credentials.iconpay.app_source');
        self::$timestamp = Carbon::now('Asia/Jakarta')->toIso8601String();
    }

    public static function booking($no_reference, $trx_date, $exp_date, $trx_code, $total_amount, $customer_name, $customer_email, $customer_phone, $throw_exception = true)
    {
        $param = self::setParamAPI([]);

        $url = sprintf('%s/%s', static::$apiendpoint, 'booking');

        $body = [
            'no_reference' => $no_reference,
            'transaction_date' => static::formatToICPDate($trx_date),
            'transaction_code' => $trx_code,
            'partner_reference' => $no_reference,
            'product_id' => self::$productid,
            'amount' => $total_amount,
            'customer_id' => $no_reference,
            'customer_name' => $customer_name,
            'email' => $customer_email,
            'phone_number' => $customer_phone,
            'expired_invoice' => static::formatToICPDate($exp_date),
        ];

        $encode_body = json_encode($body, JSON_UNESCAPED_SLASHES);

        $headers = self::headers([
            'timestamp' => self::$timestamp,
            'signature' => hash_hmac('sha256', $encode_body . self::$clientid . self::$timestamp, sha1(self::$appkey)),
            'Content-Type' => 'application/json',
        ]);

        $response = self::$curl->request('POST', $url, [
            'headers' => $headers,
            'http_errors' => false,
            'connect_timeout' => 10,
            'body' => $encode_body,
        ]);

        $response = json_decode($response->getBody());

        LogService::setUrl($url)->setRequest($body)->setResponse($response)->setServiceCode(self::$service_code)->setCategory('out')->log();

        if (!$response && $throw_exception) {
            throw new Exception('Terjadi kesalahan: Data tidak dapat diperoleh');
        }

        if (isset($response->response_details[0]->response_code) && $response->response_details[0]->response_code != "00" && $throw_exception) {
            throw new Exception($response->response_details[0]->response_message, (int) $response->response_details[0]->response_code);
        }

        if (isset($response->response_code) && $response->response_code != "00" && $throw_exception) {
            throw new Exception($response->response_message, (int) $response->response_code);
        }

        return $response;
    }

    /**
     * Setup headers
     *
     * @param  array $headers
     * @return array
     */
    protected static function headers(array $headers = []): array
    {
        return array_merge([
            'client-id' => static::$clientid,
            'appsource' => static::$appsource,
        ], $headers);
    }

    /**
     * Setup headers
     *
     * @param  array $headers
     * @return string
     */
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

    public static function formatToICPDate($value)
    {
        return date('Y/m/d H:i:s', Carbon::createFromFormat('Y-m-d H:i:s', $value)->timestamp);
    }
}

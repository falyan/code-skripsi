<?php

namespace App\Http\Services\Manager;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class KudoManager
{
    static $error_code = 'kudo_error';
    static $source = 'PLNMOBILE';
    static $api_key;
    static $secret_key;
    static $now;

    static $endpoint;
    static $header;
    static $curl;

    public static function init()
    {
        self::$now = Carbon::now('Asia/Jakarta');
        self::$curl = new Client([
            \GuzzleHttp\RequestOptions::VERIFY => false,
        ]);
        self::$api_key = config('credentials.digital-product.api_key');
        self::$secret_key = config('credentials.digital-product.secret_key');
        self::$endpoint = config('credentials.digital-product.endpoint');
    }

    public static function getProductCategory()
    {
        $params = static::setParamAPI([]);
        $headers = static::headers();

        $url = sprintf('%s/%s', static::$endpoint, 'kudo/product-categories' . $params);

        $response = static::$curl->request('GET', $url, [
            'headers' => $headers,
            'http_errors' => false,
        ]);

        Log::info("E00002", [
            'url' => $url,
            'request' => '',
            'response' => $response,
            'status' => $response->getStatusCode(),
            'time' => time(),
        ]);

        $statusCode = $response->getStatusCode();
        $response = json_decode($response->getBody()->getContents(), true);
        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh', 500));

        if (!$response['success'] != true && $statusCode != 200) {
            throw new Exception($response['message'], $statusCode);
        }

        return $response;
    }

    public static function getProductGroupByCategoryId($category_id)
    {
        $params = static::setParamAPI([]);
        $headers = static::headers();

        $url = sprintf('%s/%s', static::$endpoint, 'kudo/product-groups/product-categories/' . $category_id . $params);

        // $intactResponse = static::$curl->request('GET', $url, [
        //     'headers' => $headers,
        //     'http_errors' => false,
        //     'json_decode' => true,
        // ]);

        // $response = json_decode($intactResponse->getBody());
        $response = static::$curl->request('GET', $url, [
            'headers' => $headers,
            'http_errors' => false,
        ]);

        Log::info("E00002", [
            'url' => $url,
            'request' => '',
            'response' => $response,
            'status' => $response->getStatusCode(),
            'time' => time(),
        ]);

        $statusCode = $response->getStatusCode();
        $response = json_decode($response->getBody()->getContents(), true);
        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh', 500));

        if (!$response['success'] != true && $statusCode != 200) {
            throw new Exception($response['message'], $statusCode);
        }

        return $response;
    }

    public static function getProductsByGroupId($group_id)
    {
        $params = static::setParamAPI([]);
        $headers = static::headers();

        $url = sprintf('%s/%s', static::$endpoint, 'kudo/products/product-groups/' . $group_id . $params);

        $response = static::$curl->request('GET', $url, [
            'headers' => $headers,
            'http_errors' => false,
        ]);

        Log::info("E00002", [
            'url' => $url,
            'request' => '',
            'response' => $response,
            'status' => $response->getStatusCode(),
            'time' => time(),
        ]);

        $statusCode = $response->getStatusCode();
        $response = json_decode($response->getBody()->getContents(), true);
        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh', 500));

        if (!$response['success'] != true && $statusCode != 200) {
            throw new Exception($response['message'], $statusCode);
        }

        return $response;
    }

    public static function getUserInvoices($request, $category_id = null)
    {
        $params = static::setParamAPI([
            'user_id' => data_get($request, 'user_id'),
            'category_id' => $category_id,
            'status' => data_get($request, 'status'),
            'page' => data_get($request, 'page'),
            'limit' => data_get($request, 'limit'),
        ]);

        $url = sprintf('%s/%s', static::$endpoint, 'transaction/invoice/user' . $params);

        $response = static::$curl->request('GET', $url, [
            'headers' => static::headers(),
            'http_errors' => false,
        ]);

        Log::info("E00002", [
            'url' => $url,
            'request' => $params,
            'response' => $response,
            'status' => $response->getStatusCode(),
        ]);

        $response = json_decode($response->getBody(), true);

        if (empty($response)) {
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan: Data tidak dapat diperoleh',
                'data' => [],
            ];
        }

        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh', 500));

        return $response;
    }

    public static function inquiry($request)
    {
        $params = static::setParamAPI([]);
        $headers = static::headers();

        $url = sprintf('%s/%s', static::$endpoint, 'kudo/inquiry' . $params);

        $payload = [
            'country_calling_code' => (int) data_get($request, 'country_calling_code'),
            'customer_id' => data_get($request, 'customer_id'),
            'product_code' => data_get($request, 'product_code'),
        ];

        $response = static::$curl->request('POST', $url, [
            'headers' => $headers,
            'http_errors' => false,
            'json' => $payload,
        ]);

        Log::info("E00002", [
            'url' => $url,
            'request' => $payload,
            'response' => $response,
            'status' => $response->getStatusCode(),
            'time' => time(),
        ]);

        $response = json_decode($response->getBody()->getContents(), true);

        throw_if(!$response, Exception::class, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh', 500));
        throw_if($response['code'] != 9000, new Exception($response['message'], 500));
        throw_if($response['data']['status'] != "00", new Exception($response['data']['message'], 500));
        // throw_if($response->status != 200, new Exception($response->message, null));

        return $response['data'];
    }

    public static function createInvoice($request)
    {
        $params = static::setParamAPI([]);
        $headers = static::headers();

        $url = sprintf('%s/%s', static::$endpoint, 'transaction/invoice/create' . $params);

        $payload = [
            'product_id' => data_get($request, 'product_id'),
            'amount' => data_get($request, 'amount'),
            'total_amount' => data_get($request, 'total_amount'),
            'partner_amount' => data_get($request, 'partner_amount'),
            'user_id' => data_get($request, 'user_id'),
            'user_name' => data_get($request, 'user_name'),
            'user_email' => data_get($request, 'user_email'),
            'user_phone' => data_get($request, 'user_phone'),
            'psp_type' => data_get($request, 'psp_type'),
            'order_from' => data_get($request, 'order_from'),
        ];

        $payload = static::extendPayload($payload, [
            'phone_number' => data_get($request, 'phone_number'),
            'customer_id' => data_get($request, 'customer_id'),
            'admin_fee' => data_get($request, 'admin_fee'),
        ]);

        // $intactResponse = $this->curl
        // ->to($url)
        // ->returnResponseObject()
        // ->withHeaders($headers)
        // ->withData($payload)
        // ->asJson()
        // ->post();

        $response = static::$curl->request('POST', $url, [
            'headers' => $headers,
            'http_errors' => false,
            'json' => $payload,
        ]);

        Log::info("E00002", [
            'url' => $url,
            'request' => $payload,
            'response' => $response,
            'status' => $response->getStatusCode(),
            'time' => time(),
        ]);

        $response = json_decode($response->getBody()->getContents(), true);

        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh', 500));
        throw_if(!$response['success'], new Exception($response['message'], 500));

        return $response['data'];
    }

    public function payment($country_calling_code, $product_code, $customer_id, $invoice_id)
    {
        $params = static::setParamAPI([]);
        $headers = static::headers();

        $url = sprintf('%s/%s', static::$endpoint, 'kudo/tester/payment' . $params);

        $payload = [
            'country_calling_code' => (int) $country_calling_code,
            'product_code' => $product_code,
            'customer_id' => $customer_id,
            'invoice_id' => $invoice_id,
        ];

        $intactResponse = static::$curl->request('POST', $url, [
            'headers' => $headers,
            'http_errors' => false,
            'json' => $payload,
        ]);

        $response = json_decode($intactResponse->getBody());

        Log::info("E00002", [
            'url' => $url,
            'request' => $payload,
            'response' => $response,
            'status' => $intactResponse->getStatusCode(),
            'time' => time(),
        ]);

        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh', 500));

        return $response;
    }

    public static function getInvoiceByRefnum($refnum = "", $returnStatusOnly = false)
    {
        $headers = static::headers();

        $url = sprintf('%s/%s', static::$endpoint, 'transaction/payment-invoice/refnum/' . $refnum);

        $i = 0;
        do {
            $intactResponse = static::$curl->request('GET', $url, [
                'headers' => $headers,
                'http_errors' => false,
                'json_decode' => true,
            ]);

            if ($i == 3) {
                break;
            }

            ++$i;
        } while (!$intactResponse->getBody());

        $response = json_decode($intactResponse->getBody()) ?? null;

        Log::info("E00002", [
            'url' => $url,
            'request' => $refnum,
            'response' => $response,
            'status' => $intactResponse->getStatusCode(),
            'time' => time(),
        ]);

        throw_if(!$response && !$returnStatusOnly, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh', 500));
        throw_if(isset($response->success) && !$response->success && !$returnStatusOnly, new Exception(500, isset($response->message) ? $response->message : null));

        if ($returnStatusOnly) {
            return $response->data->invoice->status ?? null;
        }

        return $response;
    }

    public function billerPaymentNotif($headers, $payload)
    {
        $url = sprintf('%s/%s', static::$endpoint, 'transaction/biller/payment/notification');

        $i = 0;
        do {
            $intactResponse = static::$curl->request('POST', $url, [
                'headers' => $headers,
                'http_errors' => false,
                'json' => $payload,
            ]);

            if ($i == 3) {
                break;
            }

            ++$i;
        } while (!$intactResponse->getBody());

        $response = json_decode($intactResponse->getBody());

        Log::info("E00002", [
            'url' => $url,
            'request' => $payload,
            'response' => $response,
            'status' => $intactResponse->getStatusCode(),
            'time' => time(),
        ]);

        throw_if(!$response, new Exception(500, 'Terjadi kesalahan: Data tidak dapat diperoleh'));

        return $response;
    }

    /**
     * Set Param URL
     *
     * @param mixed $data
     *
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

    /**
     * Extend Request Field
     *
     * @param array $payload
     *
     * @param array $fields
     *
     * @return array
     */
    public static function extendPayload(array $payload = [], array $fields = []): array
    {
        foreach ($fields as $key => $value) {
            if (isset($key) && !is_null($value)) {
                $payload[$key] = $value;
            }
        }

        return $payload;
    }

    /**
     * Setup headers for charge in
     *
     * @param  array $headers
     * @return array
     */
    public static function headers(array $headers = []): array
    {
        return array_merge([
            'Authorization' => static::$api_key,
            'source' => static::$source,
            'signature' => hash_hmac('sha256', static::$source, static::$api_key),
        ], $headers);
    }

    /**
     * Generate signature for charge in
     *
     * @param  array $attributes
     * @return string
     */
    public static function signature(array $attributes): string
    {
        $hashing = sprintf('%s%s%s', static::$secret_key, time(), json_encode($attributes));

        return hash('sha256', $hashing);
    }

    /**
     * Log external request api to mongodb production
     *
     * @param array $attributes
     * @return void
     */
    public static function logApi(array $data)
    {
        extract($data);

        logApi('kudo', $url, $request, $response, $status, $time);

        if (env('APP_ENV', 'local') == 'production') {
            traceLogMongo([
                'url' => $url,
                'request' => $request,
                'response' => $response,
            ]);
        }
    }
}

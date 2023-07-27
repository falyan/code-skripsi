<?php

namespace App\Http\Services\Manager;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class GamificationManager
{
    static $endpoint;
    static $secret_key;
    static $now;
    static $curl;
    static $timestamp;
    static $bonus_id;

    public static function init()
    {
        self::$now = Carbon::now('Asia/Jakarta');
        self::$endpoint = config('credentials.gamification.bonus_discount.endpoint');
        self::$secret_key = config('credentials.gamification.bonus_discount.secret_key');
        self::$bonus_id = config('credentials.gamification.bonus_discount.bonus_id');
        self::$curl = new Client();

        self::$timestamp = self::$now->timestamp;
    }

    public static function claimBonusHold($userId, $amount)
    {
        $params = static::setParamAPI([]);

        $url = sprintf('%s/%s', self::$endpoint, 'v1/ext/plnm/bonus/claim/hold' . $params);

        $body = [
            'bonusId' => (int) self::$bonus_id,
            'productId' => 'MKP',
            'amount' => $amount,
            'userId' => $userId,
        ];

        $payload = json_encode($body, JSON_UNESCAPED_SLASHES);

        $prehash = self::$timestamp . $payload;

        $signature = hash_hmac('sha256', $prehash, self::$secret_key);

        $headers = [
            'Content-Type' => 'application/json',
            'timestamp' => self::$timestamp,
            'signature' => $signature,
        ];

        $response = self::$curl->request('POST', $url, [
            'headers' => $headers,
            'http_errors' => false,
            'json' => $body,
        ]);

        $response = json_decode($response->getBody()->getContents(), true);

        Log::info('E00002', [
            'url' => $url,
            'payload' => $payload,
            'response' => $response,
            'message' => 'Hit Gami Claim Hold API',
        ]);

        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

        return $response;
    }

    public static function claimBonusValidate($claimId, $amount)
    {
        $params = static::setParamAPI([]);

        $url = sprintf('%s/%s', self::$endpoint, 'v1/ext/plnm/bonus/claim/validate' . $params);

        $body = [
            'id' => $claimId,
            'productId' => 'MKP',
            'amount' => $amount,
        ];

        $payload = json_encode($body, JSON_UNESCAPED_SLASHES);

        $prehash = self::$timestamp . $payload;

        $signature = hash_hmac('sha256', $prehash, self::$secret_key);

        $headers = [
            'Content-Type' => 'application/json',
            'timestamp' => self::$timestamp,
            'signature' => $signature,
        ];

        $response = self::$curl->request('POST', $url, [
            'headers' => $headers,
            'http_errors' => false,
            'json' => $body,
        ]);

        $response = json_decode($response->getBody()->getContents(), true);

        Log::info('E00002', [
            'url' => $url,
            'payload' => $payload,
            'response' => $response,
            'message' => 'Hit Gami Claim Validate API',
        ]);

        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

        return $response;
    }

    public static function claimBonusApply($claimId, $trxReference, $trxAmount)
    {
        $params = static::setParamAPI([]);

        $url = sprintf('%s/%s', self::$endpoint, 'v1/ext/plnm/bonus/claim/apply' . $params);

        $body = [
            'id' => $claimId,
            'trxReference' => $trxReference,
            'trxAmount' => $trxAmount,
            'productId' => 'MKP',
        ];

        $payload = json_encode($body, JSON_UNESCAPED_SLASHES);

        $prehash = self::$timestamp . $payload;

        $signature = hash_hmac('sha256', $prehash, self::$secret_key);

        $headers = [
            'Content-Type' => 'application/json',
            'timestamp' => self::$timestamp,
            'signature' => $signature,
        ];

        $response = self::$curl->request('POST', $url, [
            'headers' => $headers,
            'http_errors' => false,
            'json' => $body,
        ]);

        $response = json_decode($response->getBody()->getContents(), true);

        Log::info('E00002', [
            'url' => $url,
            'payload' => $payload,
            'response' => $response,
            'message' => 'Hit Gami Claim Bonus Apply API',
        ]);

        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

        return $response;
    }

    public static function claimBonusRefund($claimId)
    {
        $params = static::setParamAPI([]);

        $url = sprintf('%s/%s', self::$endpoint, 'v1/ext/plnm/bonus/claim/refund' . $params);

        $payload = [
            'claimId' => $claimId,
        ];

        $payload = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', $payload . self::$timestamp, self::$secret_key);

        $headers = [
            'Content-Type' => 'application/json',
            'timestamp' => self::$timestamp,
            'signature' => $signature,
        ];

        $response = self::$curl->request('POST', $url, [
            'headers' => $headers,
            'http_errors' => false,
            'json' => $payload,
        ]);

        $response = json_decode($response->getBody()->getContents(), true);

        Log::info('E00002', [
            'url' => $url,
            'payload' => $payload,
            'response' => $response,
            'message' => 'Hit Gami Claim Bonus Refund API',
        ]);

        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

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
}

<?php

namespace App\Http\Services\Manager;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class EvSubsidyManager
{
    static $curl;
    static $now;

    static $endpointnik;
    static $endpointidpel;

    public static function init()
    {
        self::$now = Carbon::now('Asia/Jakarta');
        self::$curl = new Client();
        self::$endpointnik = config('credentials.evsubsidy.endpoint_nik');
        self::$endpointidpel = config('credentials.evsubsidy.endpoint_id_pel');
    }

    // checkIdPel
    public static function checkIdPel($id_pel, $tokenPln)
    {
        self::init();

        $url = sprintf('%s/%s', self::$endpointidpel, 'api/v3/meter/accounts/check/' . $id_pel);
        $headers = self::headers([
            'Authorization' => $tokenPln,
        ]);

        $response = self::$curl->request('GET', $url, [
            'headers' => $headers,
            'http_errors' => false,
        ]);

        $response = json_decode($response->getBody()->getContents(), true);
        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

        return $response;
    }

    // checkNik
    public static function checkNik($nik, $tokenPln)
    {
        self::init();

        $url = sprintf('%s/%s', self::$endpointnik, 'api/v1/master/nik/check');
        $body = [
            'id_number' => $nik,
        ];
        $headers = self::headers([
            'Authorization' => $tokenPln,
        ]);

        $response = self::$curl->request('POST', $url, [
            'headers' => $headers,
            'json' => $body,
            'http_errors' => false,
        ]);

        $response = json_decode($response->getBody()->getContents(), true);
        throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

        return $response;
    }

    public static function headers(array $headers = []): array
    {
        return array_merge([
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

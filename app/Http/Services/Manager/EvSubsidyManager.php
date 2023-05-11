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
    static $username;
    static $password;

    public static function init()
    {
        self::$now = Carbon::now('Asia/Jakarta');
        self::$curl = new Client();
        self::$endpointnik = config('credentials.evsubsidy.endpoint_nik');
        self::$username = config('credentials.evsubsidy.username');
        self::$password = config('credentials.evsubsidy.password');
    }

    // checkNik
    public static function checkNik($nik)
    {
        self::init();

        $url = sprintf('%s/%s', self::$endpointnik, 'api/dil-motor-listrik/data-exists');

        $body = [
            "nik" => $nik,
            "userId" => "plnmobile",
        ];

        $headers = self::headers([
            'Authorization' => 'Basic ' . base64_encode(self::$username . ':' . self::$password),
        ]);

        $response = self::$curl->request('POST', $url, [
            'headers' => $headers,
            'http_errors' => false,
            'json' => $body,
        ]);

        $response = json_decode($response->getBody()->getContents(), true);

        // return [
        //     'url' => $url,
        //     'body' => $body,
        //     'headers' => $headers,
        //     'user' => self::$username,
        //     'pass' => self::$password,
        //     'response' => $response,
        // ];

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

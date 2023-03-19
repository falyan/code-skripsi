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
    public static function checkNik($nik, $IdPln = null)
    {
        self::init();

        $url = sprintf('%s/%s', self::$endpointnik, 'api/dil-motor-listrik/data-exists');
        $body = '{
            "nik": "' . $nik . '",
            "id_pln": "plnmobile"
        }';
        $headers = self::headers([
            'Authorization' => 'Basic ' . base64_encode(self::$username . ':' . self::$password),
        ]);

        $request = new Request('GET', $url, $headers, $body);
        $response = self::$curl->sendAsync($request)->wait();

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

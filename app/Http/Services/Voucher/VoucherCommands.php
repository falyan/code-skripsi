<?php

namespace App\Http\Services\Voucher;

use App\Http\Services\Notification\NotificationCommands;
use App\Models\Order;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class VoucherCommands
{
    static $apiendpoint;
    static $curl;
    static $header;
    static $keyname;

    static function init()
    {
        self::$curl = new Client();
        self::$apiendpoint = config('credentials.gamification.endpoint');
        self::$keyname = config('credentials.gamification.key_name');
        self::$header = [
            'timestamp' => \Carbon\Carbon::now('Asia/Jakarta')->timestamp
        ];
    }

    public function generateVoucher($order)
    {
        //Request signature
        $param1 = static::setParamAPI([
            'email' => $order->buyer->email,
            'name' => $order->buyer->full_name,
            'phone' => $order->buyer->phone
        ]);
        $url1 = sprintf('%s/%s', static::$apiendpoint, 'oauth/signature' . $param1);
        $json_body1 = [];

        $response1 = static::$curl->request('POST', $url1, [
            'headers' => static::$header,
            'http_errors' => false,
            'json' => $json_body1
        ]);
        $signature = json_decode($response1->getBody());

        Log::info("E00003", [
            'path_url' => "voucher.signature",
            'query' => [],
            'body' => $json_body1,
            'response' => $signature
        ]);

        throw_if(!$signature, Exception::class, new Exception('Terjadi kesalahan: Tidak dapat terhubung ke server', 400));

        if (!isset($signature->success) || $signature->success != true) {
            throw new Exception('Terjadi kesalahan: Signature tidak dapat diperoleh', 400);
        }

        //Sync account
        $param2 = static::setParamAPI([
            'email' => $order->buyer->email,
            'name' => $order->buyer->full_name,
            'phone' => $order->buyer->phone,
            'signature' => $signature->data->signature
        ]);
        $url2 = sprintf('%s/%s', static::$apiendpoint, 'oauth/sync-account' . $param2);
        $json_body2 = [];

        $response2 = static::$curl->request('POST', $url2, [
            'headers' => static::$header,
            'http_errors' => false,
            'json' => $json_body2
        ]);
        $sync_account = json_decode($response2->getBody());

        Log::info("E00003", [
            'path_url' => "voucher.sync-account",
            'query' => [],
            'body' => $json_body2,
            'response' => $sync_account
        ]);

        throw_if(!$sync_account, Exception::class, new Exception('Terjadi kesalahan: Tidak dapat terhubung ke server', 400));

        if (!isset($sync_account->success) || $sync_account->success != true) {
            throw new Exception('Terjadi kesalahan: Gagal melakukan sync account', 400);
        }

        //Generate voucher ubahdaya
        static::$header = [
            'Authorization' => 'Bearer ' . $sync_account->data->token
        ];
        $param3 = static::setParamAPI([]);
        $url3 = sprintf('%s/%s', static::$apiendpoint, 'v1/voucher/redeem/specific' . $param3);
        $json_body3 = [
            'activity_code' => '02',
            'keyname' => static::$keyname
        ];

        $response3 = static::$curl->request('POST', $url3, [
            'headers' => static::$header,
            'http_errors' => false,
            'json' => $json_body3
        ]);
        $voucher = json_decode($response3->getBody());

        Log::info("E00003", [
            'path_url' => "voucher.generate",
            'query' => [],
            'body' => $json_body3,
            'response' => $voucher
        ]);

        throw_if(!$voucher, Exception::class, new Exception('Terjadi kesalahan: Tidak dapat terhubung ke server', 400));

        if ($voucher->success == true) {
            $orders = Order::where('no_reference', $order->no_reference)->update([
                'voucher_ubah_daya_code' => $voucher->data->voucher_code
            ]);

            throw_if(!$orders, Exception::class, new Exception('Terjadi kesalahan: Gagal menyimpan data voucher', 400));

            $title = 'Selamat Anda Mendapatkan Voucher';
            $message = 'Selamat anda mendapatkan voucher ubah daya! Cek voucher anda pada voucher saya di bagian profil ';
            $url_path = 'v1/buyer/query/transaction/' . $order->buyer->id . '/detail/' . $order->id;
            $notificationCommand = new NotificationCommands();
            $notificationCommand->create('customer_id', $order->buyer->id, 2, $title, $message, $url_path);
            //            $notificationCommand->sendPushNotification($order->buyer->id, $title, $message, 'active');
            $notificationCommand->sendPushNotificationCustomerPlnMobile($order->buyer->id, $title, $message);
        }

        return [
            'success' => true,
            'message' => 'Berhasil menyimpan data voucher'
        ];
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

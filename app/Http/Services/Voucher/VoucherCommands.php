<?php

namespace App\Http\Services\Voucher;

use App\Http\Services\Manager\MailSenderManager;
use App\Http\Services\Notification\NotificationCommands;
use App\Models\MasterData;
use App\Models\Order;
use App\Models\UbahDayaLog;
use App\Models\UbahDayaMaster;
use App\Models\UbahDayaPregenerate;
use App\Models\User;
use Carbon\Carbon;
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
            'body' => $param1,
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
            'body' => $param2,
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

    public function generateVoucher2($order)
    {
        $customer = User::where('id', $order->buyer_id)->first();

        $master_data = MasterData::whereIn('key', ['ubah_daya_min_customer_create', 'ubah_daya_implementation_period'])->get();

        $period = collect($master_data)->where('key', 'ubah_daya_implementation_period')->first();
        if (Carbon::parse(explode('/', $period->value)[0]) >= Carbon::now() || Carbon::parse(explode('/', $period->value)[1]) <= Carbon::now()) {
            return [
                'success' => false,
                'message' => 'Mohon maaf, event ini sudah tidak berlaku'
            ];
        }

        $min_customer_create = collect($master_data)->where('key', 'ubah_daya_min_customer_create')->first();
        if (Carbon::parse($customer->created_at)->diffInDays(Carbon::now()) < Carbon::parse($min_customer_create->value)->diffInDays(Carbon::now())) {
            return [
                'success' => false,
                'message' => 'Mohon maaf, anda belum bisa mengikuti event ini'
            ];
        }

        $master_ubah_dayas = UbahDayaMaster::with([
            'pregenerates' => function ($query) {
                $query->where('status', 1)->where('claimed_at', null);
            }
        ])
        ->where('status', 1)
        ->get();

        $master_ubah_daya = null;
        foreach ($master_ubah_dayas as $value) {
            if ($value->event_start_date <= date('Y-m-d') && $value->event_end_date >= date('Y-m-d')) {
                $master_ubah_daya = $value;
                break;
            }
        }

        $logs = UbahDayaLog::where([
            'customer_id' => $order->buyer_id,
            'status' => 1
        ])->get();

        foreach ($logs as $log) {
            if ($log->master_ubah_daya_id == $master_ubah_daya->id) {
                return [
                    'success' => false,
                    'message' => 'Anda sudah mengikuti event ini'
                ];
            }
        }

        if ($master_ubah_daya != null && count($master_ubah_daya->pregenerates) > 0) {
            $voucher_code = '';

            $voucher_code = $master_ubah_daya->pregenerates[0]->kode;
            UbahDayaPregenerate::where('id', $master_ubah_daya->pregenerates[0]->id)->update([
                'claimed_at' => date('Y-m-d H:i:s')
            ]);

            UbahDayaLog::create([
                'customer_id' => $order->buyer_id,
                'master_ubah_daya_id' => $master_ubah_daya->id,
                'customer_email' => $order->buyer->email,
                'event_name' => $master_ubah_daya->event_name,
                'event_start_date' => $master_ubah_daya->event_start_date,
                'event_end_date' => $master_ubah_daya->event_end_date,
                'status' => 1,
            ]);

            $orders = Order::where('no_reference', $order->no_reference)->update([
                'voucher_ubah_daya_code' => $voucher_code
            ]);

            throw_if(!$orders, Exception::class, new Exception('Terjadi kesalahan: Gagal menyimpan data voucher', 400));

            $title = 'Selamat Anda Mendapatkan Voucher';
            $message = 'Selamat anda mendapatkan voucher ubah daya! Cek voucher anda pada voucher saya di bagian profil';
            $url_path = 'v1/buyer/query/transaction/' . $order->buyer->id . '/detail/' . $order->id;
            $notificationCommand = new NotificationCommands();
            $notificationCommand->create('customer_id', $order->buyer->id, 2, $title, $message, $url_path);
            // $notificationCommand->sendPushNotification($order->buyer->id, $title, $message, 'active');
            $notificationCommand->sendPushNotificationCustomerPlnMobile($order->buyer->id, $title, $message);

            $mailSender = new MailSenderManager();
            $mailSender->mainVoucherClaim($order->id);

            return [
                'success' => true,
                'message' => 'Berhasil generate voucher'
            ];
        }

        return [
            'success' => false,
            'message' => 'Gagal generate voucher'
        ];
    }

    // generateVoucherCode random 14`char
    public function generateVoucherCode($customer_id, $ubah_daya_id)
    {
        $length = 14;
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        $voucher_code = $randomString . $customer_id . $ubah_daya_id;
        return $voucher_code;
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

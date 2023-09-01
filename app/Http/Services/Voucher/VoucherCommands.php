<?php

namespace App\Http\Services\Voucher;

use App\Http\Services\Manager\MailSenderManager;
use App\Http\Services\Notification\NotificationCommands;
use App\Models\MasterData;
use App\Models\Order;
use App\Models\OrderProgress;
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
    static $keyid;
    static $keysecret;

    public static function init()
    {
        self::$curl = new Client();
        self::$apiendpoint = config('credentials.gamification.endpoint');
        self::$keyid = config('credentials.gamification.key_id');
        self::$keysecret = config('credentials.gamification.secret_key');
        self::$header = [
            'timestamp' => \Carbon\Carbon::now('Asia/Jakarta')->timestamp,
        ];
    }

    public function generateVoucher($order, $master_ubah_daya, $is_insentif = false)
    {
        $param = static::setParamAPI([]);
        $url = sprintf('%s/%s', static::$apiendpoint, 'v1/ext/plnmkp/voucher/claim/ubahdaya' . $param);

        $json_body = [
            'voucherId' => (int) $master_ubah_daya->voucher_id
        ];
        if ($order->buyer->pln_mobile_customer_id != null) {
            $json_body['userIdPlnMobile'] = $order->buyer->pln_mobile_customer_id;
        } else {
            $json_body['email'] = $order->buyer->email;
        }
        if ($is_insentif) {
            $json_body['partnerRef'] = $order->no_reference;
        }

        $hashmac = hash_hmac('sha256', self::$header['timestamp'] . json_encode($json_body), self::$keysecret);
        self::$header['signature'] = $hashmac;

        $response = static::$curl->request('POST', $url, [
            'headers' => static::$header,
            'http_errors' => false,
            'json' => $json_body,
        ]);

        $response = json_decode($response->getBody());

        $log = [
            'path_url' => "voucher.claim.ubahdaya",
            'query' => [],
            'body' => $json_body,
            'response' => $response,
        ];
        Log::info("E00003", $log);
        OrderProgress::where('order_id', $order->id)->where('status', 1)->update(['ubah_daya_log' => json_encode($log)]);

        throw_if(!$response, Exception::class, new Exception('Terjadi kesalahan: Tidak dapat terhubung ke server', 400));

        if (!isset($response->success) || $response->success != true) {
            throw new Exception('Terjadi kesalahan: ' . $response->message, 400);
        }

        if ($master_ubah_daya != null && $response->data != null) {
            $voucher_code = '';
            $voucher_code = $response->data->voucherCode;
            UbahDayaLog::create([
                'customer_id' => $order->buyer_id,
                'master_ubah_daya_id' => $master_ubah_daya->id,
                'customer_email' => $order->buyer->email,
                'event_name' => $master_ubah_daya->event_name,
                'event_start_date' => $master_ubah_daya->event_start_date,
                'event_end_date' => $master_ubah_daya->event_end_date,
                'voucher_code' => $voucher_code,
            ]);

            $orders = Order::where('no_reference', $order->no_reference)->update([
                'voucher_ubah_daya_code' => $voucher_code,
            ]);

            throw_if(!$orders, Exception::class, new Exception('Terjadi kesalahan: Gagal menyimpan data voucher', 400));

            $title = 'Selamat Anda Mendapatkan Voucher';
            $message = 'Selamat anda mendapatkan voucher ubah daya! Cek voucher anda pada voucher saya di bagian profil';
            $url_path = 'v1/buyer/query/transaction/' . $order->buyer->id . '/detail/' . $order->id;
            $notificationCommand = new NotificationCommands();
            $notificationCommand->create('customer_id', $order->buyer->id, 2, $title, $message, $url_path);
            $notificationCommand->sendPushNotificationCustomerPlnMobile($order->buyer->id, $title, $message);

            $mailSender = new MailSenderManager();
            $mailSender->mainVoucherClaim($order->id);
        }
    }

    public function generateVoucher2($order)
    {
        $customer = User::where('id', $order->buyer_id)->first();

        $master_data = MasterData::whereIn('key', ['ubah_daya_min_customer_create', 'ubah_daya_implementation_period'])->get();

        $period = collect($master_data)->where('key', 'ubah_daya_implementation_period')->first();
        if (Carbon::parse(explode('/', $period->value)[0]) >= Carbon::now() || Carbon::parse(explode('/', $period->value)[1]) <= Carbon::now()) {
            return [
                'success' => false,
                'message' => 'Mohon maaf, event ini sudah tidak berlaku',
            ];
        }

        $min_customer_create = collect($master_data)->where('key', 'ubah_daya_min_customer_create')->first();
        if (Carbon::parse($customer->created_at)->diffInDays(Carbon::now()) < Carbon::parse($min_customer_create->value)->diffInDays(Carbon::now())) {
            return [
                'success' => false,
                'message' => 'Mohon maaf, anda belum bisa mengikuti event ini',
            ];
        }

        $master_ubah_dayas = UbahDayaMaster::with([
            'pregenerates' => function ($query) {
                $query->where('status', 1)->where('claimed_at', null);
            },
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

        if ($master_ubah_daya == null) {
            return [
                'success' => false,
                'message' => 'Mohon maaf, belum ada event yang sedang berlangsung',
            ];
        }

        $logs = UbahDayaLog::where([
            'customer_id' => $order->buyer_id,
            'status' => 1,
        ])->get();

        foreach ($logs as $log) {
            if ($log->master_ubah_daya_id == $master_ubah_daya->id) {
                return [
                    'success' => false,
                    'message' => 'Anda sudah mengikuti event ini',
                ];
            }
        }

        if ($master_ubah_daya != null && count($master_ubah_daya->pregenerates) > 0) {
            $voucher_code = '';

            $voucher_code = $master_ubah_daya->kode;
            UbahDayaPregenerate::where('id', $master_ubah_daya->id)->update([
                'claimed_at' => date('Y-m-d H:i:s'),
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
                'voucher_ubah_daya_code' => $voucher_code,
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
                'message' => 'Berhasil generate voucher',
            ];
        }

        return [
            'success' => false,
            'message' => 'Gagal generate voucher',
        ];
    }

    public function generateVoucherOld($order)
    {

        $master_ubah_dayas = UbahDayaMaster::where('status', 1)->get();

        $master_ubah_daya = null;
        foreach ($master_ubah_dayas as $value) {
            $master_ubah_daya = $value;
            break;
        }

        $param = static::setParamAPI([]);
        $url = sprintf('%s/%s', static::$apiendpoint, '/v1/ext/plnmkp/voucher/claim/ubahdaya' . $param);

        $json_body = null;
        if ($order->buyer->pln_mobile_customer_id != null) {
            $json_body = [
                'userIdPlnMobile' => $order->buyer->pln_mobile_customer_id,
                'voucherId' => (int) static::$keyid,
            ];
        } else {
            $json_body = [
                'email' => $order->buyer->email,
                'voucherId' => (int) static::$keyid,
            ];
        }

        $hashmac = hash_hmac('sha256', self::$header['timestamp'] . json_encode($json_body), self::$keysecret);
        self::$header['signature'] = $hashmac;

        $response = static::$curl->request('POST', $url, [
            'headers' => static::$header,
            'http_errors' => false,
            'json' => $json_body,
        ]);

        $response = json_decode($response->getBody());

        Log::info("E00003", [
            'path_url' => "voucher.claim.ubahdaya",
            'query' => [],
            'body' => $json_body,
            'response' => $response,
        ]);

        throw_if(!$response, Exception::class, new Exception('Terjadi kesalahan: Tidak dapat terhubung ke server', 400));

        if (!isset($response->success) || $response->success != true) {
            throw new Exception('Terjadi kesalahan: ' . $response->message, 400);
        }

        if ($master_ubah_daya != null && $response->data != null) {
            $voucher_code = '';
            $voucher_code = $response->data->voucherCode;
            UbahDayaLog::create([
                'customer_id' => $order->buyer_id,
                'master_ubah_daya_id' => $master_ubah_daya->id,
                'customer_email' => $order->buyer->email,
                'event_name' => $master_ubah_daya->event_name,
                'event_start_date' => $master_ubah_daya->event_start_date,
                'event_end_date' => $master_ubah_daya->event_end_date,
                'voucher_code' => $voucher_code,
            ]);

            $orders = Order::where('no_reference', $order->no_reference)->update([
                'voucher_ubah_daya_code' => $voucher_code,
            ]);

            throw_if(!$orders, Exception::class, new Exception('Terjadi kesalahan: Gagal menyimpan data voucher', 400));

            $title = 'Selamat Anda Mendapatkan Voucher';
            $message = 'Selamat anda mendapatkan voucher ubah daya! Cek voucher anda pada voucher saya di bagian profil';
            $url_path = 'v1/buyer/query/transaction/' . $order->buyer->id . '/detail/' . $order->id;
            $notificationCommand = new NotificationCommands();
            $notificationCommand->create('customer_id', $order->buyer->id, 2, $title, $message, $url_path);
            $notificationCommand->sendPushNotificationCustomerPlnMobile($order->buyer->id, $title, $message);

            $mailSender = new MailSenderManager();
            $mailSender->mainVoucherClaim($order->id);
        }
    }

    public function claimPregenerate($order, $master_ubah_daya_logs)
    {
        $log_ubah_daya = null;
        foreach ($master_ubah_daya_logs as $value) {
            $log_ubah_daya = $value;
            break;
        }

        $pregenerate = UbahDayaPregenerate::where([
            'master_ubah_daya_id' => $log_ubah_daya->master_ubah_daya_id,
            'status' => 1,
            'claimed_at' => null,
        ])->first();

        if ($pregenerate) {
            $voucher_code = $pregenerate->kode;
            UbahDayaLog::where('id', $log_ubah_daya->id)->update([
                'voucher_code' => $voucher_code,
                'with_nik_claim_at' => date('Y-m-d H:i:s'),
            ]);

            $orders = Order::where('no_reference', $order->no_reference)->update([
                'voucher_ubah_daya_code' => $voucher_code,
            ]);

            UbahDayaPregenerate::where('id', $pregenerate->id)->update([
                'claimed_at' => date('Y-m-d H:i:s'),
            ]);

            throw_if(!$orders, Exception::class, new Exception('Terjadi kesalahan: Gagal menyimpan data voucher', 400));

            $title = 'Selamat Anda Mendapatkan Voucher';
            $message = 'Selamat anda mendapatkan voucher ubah daya! Cek voucher anda pada voucher saya di bagian profil';
            $url_path = 'v1/buyer/query/transaction/' . $order->buyer->id . '/detail/' . $order->id;
            $notificationCommand = new NotificationCommands();
            $notificationCommand->create('customer_id', $order->buyer->id, 2, $title, $message, $url_path);
            $notificationCommand->sendPushNotificationCustomerPlnMobile($order->buyer->id, $title, $message);

            $mailSender = new MailSenderManager();
            $mailSender->mainVoucherClaim($order->id);
        }
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

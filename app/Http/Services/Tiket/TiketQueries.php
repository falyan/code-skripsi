<?php

namespace App\Http\Services\Tiket;

use App\Http\Services\Service;
use App\Models\Order;
use App\Models\UserTiket;
use Carbon\Carbon;

class TiketQueries extends Service
{
    static $SUCCESS = '00';
    static $TICKET_NOT_FOUND = '01';
    static $TICKET_NOT_ACTIVED = '02';
    static $TICKET_HAS_USED = '03';
    static $TICKET_DATE_NOT_VALID = '04';
    static $TICKET_TIME_NOT_VALID = '05';
    static $TICKET_UPDATE_FAILED = '06';
    static $HEADER_KEY_ACCESS_REQUIRED = '07';
    static $HEADER_KEY_ACCESS_INVALID = '08';

    static $ORDER_NOT_FOUND = '09';

    public function getTiket($qr)
    {
        $tiket = UserTiket::where('number_tiket', $qr)->first();

        if (!$tiket) {
            return [
                'error_code' => static::$TICKET_NOT_FOUND,
                'status' => 'error',
                'message' => 'Tiket tidak ditemukan',
            ];
        }

        $tiket->load('master_tiket');

        if ($tiket->master_tiket->status == 0) {
            return [
                'error_code' => static::$TICKET_NOT_ACTIVED,
                'status' => 'error',
                'message' => 'Tiket sudah tidak aktif',
            ];
        }

        if ($tiket->status == 2) {
            return [
                'error_code' => static::$TICKET_HAS_USED,
                'status' => 'error',
                'message' => 'Tiket telah digunakan',
            ];
        }

        if ($tiket->usage_date != Carbon::now()->format('Y-m-d')) {
            return [
                'error_code' => static::$TICKET_DATE_NOT_VALID,
                'status' => 'error',
                'message' => 'Tiket hanya bisa digunakan pada tanggal ' . Carbon::parse($tiket->usage_date)->format('d M Y'),
            ];
        }

        if ($tiket->start_time_usage != null || $tiket->end_time_usage != null) {
            $start_time_usage = Carbon::parse($tiket->usage_date . ' ' . $tiket->start_time_usage)->format('Y-m-d H:i:s');
            $end_time_usage = Carbon::parse($tiket->usage_date . ' ' . $tiket->end_time_usage)->format('Y-m-d H:i:s');
            $now = Carbon::now()->format('Y-m-d H:i:s');

            if (!Carbon::parse($now)->between($start_time_usage, $end_time_usage)) {
                return [
                    'error_code' => static::$TICKET_TIME_NOT_VALID,
                    'status' => 'error',
                    'message' => 'Tiket hanya bisa digunakan pada jam ' . Carbon::parse($tiket->start_time_usage)->format('H:i') . ' - ' . Carbon::parse($tiket->end_time_usage)->format('H:i'),
                ];
            }
        }

        return $tiket;
    }

    public function getTiketByOrder($trx_no)
    {
        $order = Order::where('trx_no', $trx_no)->first();

        if (!$order) {
            return [
                'error_code' => static::$ORDER_NOT_FOUND,
                'status' => 'error',
                'message' => 'Order tidak ditemukan',
            ];
        }

        $tiket = UserTiket::with('master_tiket')->where('order_id', $order->id)->get();

        if (!$tiket) {
            return [
                'error_code' => static::$TICKET_NOT_FOUND,
                'status' => 'error',
                'message' => 'Tiket tidak ditemukan',
            ];
        }

        return $tiket;
    }
}

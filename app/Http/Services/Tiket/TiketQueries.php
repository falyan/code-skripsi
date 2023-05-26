<?php

namespace App\Http\Services\Tiket;

use App\Http\Services\Service;
use App\Models\CustomerTiket;
use App\Models\Order;
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
        $tiket = CustomerTiket::where('number_tiket', $qr)->first();

        if (!$tiket) {
            return [
                'error_code' => static::$TICKET_NOT_FOUND,
                'status' => 'error',
                'message' => 'Tiket tidak ditemukan',
            ];
        }

        $tiket->load(['master_tiket', 'order', 'order.buyer']);

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
                'data' => $tiket,
            ];
        }

        if (Carbon::now()->format('Y-m-d') > $tiket->usage_date) {
            return [
                'error_code' => static::$TICKET_DATE_NOT_VALID,
                'status' => 'error',
                'message' => 'Tiket telah hangus pada ' . Carbon::parse($tiket->usage_date)->format('d M Y'),
            ];
        }

        // if ($tiket->usage_date != Carbon::now()->format('Y-m-d')) {
        //     return [
        //         'error_code' => static::$TICKET_DATE_NOT_VALID,
        //         'status' => 'error',
        //         'message' => 'Tiket hanya bisa digunakan pada tanggal ' . Carbon::parse($tiket->usage_date)->format('d M Y'),
        //     ];
        // }

        // if ($tiket->start_time_usage != null || $tiket->end_time_usage != null) {
        //     $start_time_usage = Carbon::parse($tiket->usage_date . ' ' . $tiket->start_time_usage)->format('Y-m-d H:i:s');
        //     $end_time_usage = Carbon::parse($tiket->usage_date . ' ' . $tiket->end_time_usage)->format('Y-m-d H:i:s');
        //     $now = Carbon::now()->format('Y-m-d H:i:s');

        //     if (!Carbon::parse($now)->between($start_time_usage, $end_time_usage)) {
        //         return [
        //             'error_code' => static::$TICKET_TIME_NOT_VALID,
        //             'status' => 'error',
        //             'message' => 'Tiket hanya bisa digunakan pada jam ' . Carbon::parse($tiket->start_time_usage)->format('H:i') . ' - ' . Carbon::parse($tiket->end_time_usage)->format('H:i'),
        //         ];
        //     }
        // }

        $master_data = MasterData::where('key', $tiket->master_tiket->master_data_key)->first();
        $master_data->load('parent');
        if ($master_data->parent->key == 'prodcat_vip_proliga_2023') {
            $tiket->is_vip = true;
        } else {
            $tiket->is_vip = false;
        }


        return $tiket;
    }

    public function getDashboard()
    {
        $getTiket = CustomerTiket::with(['master_tiket'])
        ->whereHas(
            'master_tiket',
            function ($query) {
                $query->where('status', 1);
            }
        )
        ->get();

        $tiket = collect($getTiket);

        return [
            'total_tiket' => $tiket->count(),
            'total_tiket_claim' => $tiket->where('status', 2)->count(),
        ];
    }

    public function getTiketByOrder($trx_no, $withId = false)
    {
        if ($withId) {
            $order = Order::find($trx_no);
        } else {
            $order = Order::where('trx_no', 'ILIKE', '%' . $trx_no)->first();
        }

        if (!$order) {
            return [
                'error_code' => static::$ORDER_NOT_FOUND,
                'status' => 'error',
                'message' => 'Order tidak ditemukan',
            ];
        }

        $order->load(
            'detail.product.category',
            'detail.product.category.parent',
            'buyer',
        );

        $master_data_tiket = [];
        foreach ($order->detail as $detail) {
            $master_data_tiket[] = $detail->product->category;
        }

        $user_tikets = CustomerTiket::with('master_tiket')->where('order_id', $order->id)->get();

        $tikets = [];
        foreach ($user_tikets as $user_tiket) {
            $user_tiket['user_name'] = $order->buyer->full_name;
            $user_tiket['user_email'] = $order->buyer->email;
            $user_tiket['trx_no'] = $order->trx_no;
            $user_tiket['order_date'] = Carbon::parse($order->created_at)->format('Y-m-d H:i:s');
            $tikets[] = $user_tiket;
        }
        if (!$tikets) {
            return [
                'error_code' => static::$TICKET_NOT_FOUND,
                'status' => 'error',
                'message' => 'Tiket tidak ditemukan',
            ];
        }

        return $tikets;
    }

    public function getTiketAll($limit = 10, $keyword = null)
    {
        $tikets = CustomerTiket::with(['master_tiket', 'order', 'order.buyer'])
            ->whereHas(
                'master_tiket',
                function ($query) {
                    $query->where('status', 1);
                }
            )
            ->when($keyword, function ($query, $keyword) {
                $query->whereHas('order', function ($query) use ($keyword) {
                    $query->whereHas('buyer', function ($query) use ($keyword) {
                        $query->where('full_name', 'ILIKE', '%' . $keyword . '%');
                        $query->orWhere('email', 'ILIKE', '%' . $keyword . '%');
                        $query->orWhere('phone', 'ILIKE', '%' . $keyword . '%');
                    });
                });
            })
            ->paginate($limit);

        if (!$tikets) {
            return [
                'error_code' => static::$TICKET_NOT_FOUND,
                'status' => 'error',
                'message' => 'Tiket tidak ditemukan',
            ];
        }

        return $tikets;
    }

    //==== Tiket PLN MUDIK 2023 ====//
    public function getOrder($trx_no, $email)
    {
        $order = Order::whereHas('buyer', function ($query) use ($email) {
            $query->where('email', $email);
        })->where('trx_no', 'ILIKE', '%' . $trx_no)->first();

        if (!$order) {
            return [
                'error_code' => static::$ORDER_NOT_FOUND,
                'status' => 'error',
                'message' => 'Order tidak ditemukan',
            ];
        }

        $order->load(
            'detail.product.category.parent.parent',
            'buyer',
        );

        $cat_product = null;
        foreach ($order->detail as $detail) {
            $cat_product = $detail->product->category->parent->parent->key;
        }

        if (substr($cat_product, 0, 22) != 'prodcat_pln_mudik_2023') {
            return [
                'error_code' => static::$ORDER_NOT_FOUND,
                'status' => 'error',
                'message' => 'Order tidak valid - bukan tiket PLN MUDIK 2023',
            ];
        }

        return $order;
    }
}

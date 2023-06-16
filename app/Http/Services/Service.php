<?php

namespace App\Http\Services;

use GuzzleHttp\Client;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class Service
{
    static $apiendpoint;
    static $appkey;
    static $curl;
    static $clientid;
    static $productid;
    static $appsource;
    static $header;
    static $banner_url;

    public static function init()
    {
        self::$curl = new Client();
        self::$apiendpoint = config('credentials.iconpay.endpoint');
        self::$appkey = config('credentials.iconpay.app_key');
        self::$clientid = config('credentials.iconpay.client_id');
        self::$productid = config('credentials.iconpay.product_id');
        self::$appsource = config('credentials.iconpay.app_source');
        self::$header = [
            'client-id' => static::$clientid,
            'appsource' => static::$appsource,
        ];
        self::$banner_url = config('credentials.banner.url_flash_popup');
    }

    static $error_codes = [400, 401, 402, 403, 404, 405, 406, 407, 408, 409, 410, 411, 412, 413, 414, 415, 416, 417, 418, 421, 422, 423, 424, 425, 426, 428, 429, 431, 451, 500, 501, 502, 503, 504, 505, 506, 507, 508, 510, 511];

    static $status_order = [
        '00' => 'Pesanan Belum Dibayar',
        '01' => 'Menunggu Konfirmasi',
        '02' => 'Pesanan Siap Dikirim',
        '03' => 'Pesanan Sedang Dikirim',
        '08' => 'Pesanan Telah Sampai',
        '09' => 'Pesanan Ditolak',
        '88' => 'Pesanan Selesai',
        '99' => 'Pesanan Dibatalkan',
        '98' => 'Pengembalian Dana Refund',
    ];

    static $notification_type = [
        '1' => 'Informasi',
        '2' => 'Transaksi',
        '3' => 'Promo',
        '4' => 'Lainnya',
    ];

    static $order_detail_type = [
        '1' => 'Detail',
        '2' => 'Fee',
        '3' => 'Discount',
        '3' => 'Partner Fee',
    ];

    static $status_agent_order = [
        '00' => 'Menunggu Pembayaran',
        '01' => 'Menunggu Pembayaran', // proses iconcash
        '02' => 'Diproses', // success iconcash
        '03' => 'Diproses', // proses iconpay
        '04' => 'Berhasil', // success iconpay
        '05' => 'Reversal', // reversal iconpay
        '08' => 'Gagal',
        '09' => 'Pending',
        '88' => 'Pesanan Selesai',
        '99' => 'Pesanan Dibatalkan',
        '98' => 'Pengembalian Dana Refund',
    ];

    static $status_ev_subsidy = [
        '00' => 'verifed',
        '01' => 'sudah dapet subsidi',
        '02' => 'nik / id_pel tidak ditemukan',
        '03' => 'daya tidak memenuhi persyaratan',
    ];

    public static function paginate(array $items, $perPage = 10, $page = 1, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);

        $paginated = new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
        $modified = [];
        foreach ($paginated->items() as $key) {
            array_push($modified, $key);
        }

        return [
            'current_page' => $paginated->currentPage(),
            'data' => $modified,
            'first_page_url' => "/?page=1",
            'from' => $paginated->firstItem(),
            'last_page' => $paginated->lastPage(),
            'last_page_url' => "/?page=" . $paginated->lastPage(),
            'links' => $paginated->linkCollection(),
            'next_page_url' => $paginated->nextPageUrl(),
            'path' => $paginated->path(),
            'per_page' => $paginated->perPage(),
            'prev_page_url' => $paginated->previousPageUrl(),
            'to' => count($modified),
            'total' => $paginated->total(),
        ];
    }

    public static function generateGateway($id)
    {
        $num = $id / 26;
        $num_rest = $id;

        if ($num > 1) {
            $num_rest = $id % 26;
        }

        for ($alpha = 1; $alpha <= 26; $alpha++) {
            $index = $alpha == 1 ? 1 : (($alpha - 1) * 26) + 1;
            $index_i = $alpha * 26;
            $aaa = $num_rest == 0 ? 26 : $num_rest;

            for ($i = $index; $i <= $index_i; $i++) {
                if ($num <= $i) {
                    $ab = $alpha == 1 ? 0 : ($alpha - 1) * 26;
                    $aa = $i - $ab;
                    return static::numToAlpha($alpha) . static::numToAlpha($aa) . static::numToAlpha($aaa);
                }
            }
        }
    }

    private static function numToAlpha($num)
    {
        $alphabet = range('A', 'Z');
        return $alphabet[$num - 1];
    }
}

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

  static function init()
  {
    self::$curl = new Client();
    self::$apiendpoint = config('credentials.iconpay.endpoint');
    self::$appkey = config('credentials.iconpay.app_key');
    self::$clientid = config('credentials.iconpay.client_id');
    self::$productid = config('credentials.iconpay.product_id');
    self::$appsource = config('credentials.iconpay.app_source');
    self::$header = [
      'client-id' => static::$clientid,
      'appsource' => static::$appsource
    ];
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
  ];

  static $notification_type = [
    '1' => 'Informasi',
    '2' => 'Transaksi',
    '3' => 'Promo',
  ];

  static $order_detail_type = [
    '1' => 'Detail',
    '2' => 'Fee',
    '3' => 'Discount',
    '3' => 'Partner Fee',
  ];

  static function paginate(array $items, $perPage = 10, $page = 1, $options = [])
  {
    $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
    $items = $items instanceof Collection ? $items : Collection::make($items);

    
    $paginated = new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    $modified = [];
    foreach ($paginated->items() as $key) {
      array_push($modified, $key);
    }

    return [
      'current_page'    => $paginated->currentPage(),
      'data'            => $modified,
      'first_page_url'  => "/?page=1",
      'from'            => $paginated->firstItem(),
      'last_page'       => $paginated->lastPage(),
      'last_page_url'   => "/?page=" . $paginated->lastPage(),
      'links'           => $paginated->linkCollection(),
      'next_page_url'   => $paginated->nextPageUrl(),
      'path'            => $paginated->path(),
      'per_page'        => $paginated->perPage(),
      'prev_page_url'   => $paginated->previousPageUrl(),
      'to'              => count($modified),
      'total'           => $paginated->total()
    ];
  }
}

<?php

namespace App\Http\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class Service
{
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

  static function paginate(array $items, $perPage = 10, $page = null, $options = [])
  {
    $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
    $items = $items instanceof Collection ? $items : Collection::make($items);

    return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
  }
}

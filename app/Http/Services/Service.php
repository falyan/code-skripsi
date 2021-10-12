<?php 

namespace App\Http\Services;

class Service
{
  static $error_codes = [400, 401, 402, 403, 404, 405, 406, 407, 408, 409, 410, 411, 412, 413, 414, 415, 416, 417, 418, 421, 422, 423, 424, 425, 426, 428, 429, 431, 451, 500, 501, 502, 503, 504, 505, 506, 507, 508, 510, 511];
  static $status_order = [
      '0' => 'Pesanan Belum Dibayar',
      '1' => 'Menunggu Konfirmasi',
      '2' => 'Pesanan Siap Dikirim',
      '3' => 'Pesanan Sedang Dikirim',
      '8' => 'Pesanan Telah Sampai',
      '88' => 'Pesanan Selesai',
      '99' => 'Pesanan Dibatalkan',
  ];
}
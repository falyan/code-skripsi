<?php

namespace App\Http\Services\Transaction;

use App\Models\OrderProgress;

class TransactionCommands
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
    public function createOrder()
    {
        # code...
    }

    public function updateOrderStatus($order_id, $status_code, $note = null)
    {
        $old_order_progress = OrderProgress::where('order_id', $order_id)->get();
        $total = count($old_order_progress) ?? 0;
        if ($total >= 0) {
            for ($i=0; $i < $total; $i++) { 
                $old_order_progress[$i]->status = 0;
                $old_order_progress[$i]->save();
            }
        }

        $new_order_progress = new OrderProgress();
        $new_order_progress->order_id = $order_id;
        $new_order_progress->status_code = $status_code;
        $new_order_progress->status_name = $this->status_order[$status_code];
        $new_order_progress->note = $note;
        $new_order_progress->status = 1;
        $new_order_progress->save();
    }
};

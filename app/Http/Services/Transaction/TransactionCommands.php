<?php

namespace App\Http\Services\Transaction;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderProgress;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Services\Service;
use App\Models\OrderPayment;
use Ramsey\Uuid\Uuid;

class TransactionCommands extends Service
{
    public function createOrder($datas)
    {
        DB::beginTransaction();
        try {
            $no_reference = Uuid::uuid4();
            $trx_date = date('Y/m/d H:i:s', Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now())->setTimezone('Asia/Jakarta')->timestamp);
            $exp_date = date('Y/m/d H:i:s', Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now()->addDay())->setTimezone('Asia/Jakarta')->timestamp);

            array_map(function ($data) use ($no_reference, $trx_date, $exp_date) {
                $order = new Order();
                $order->merchant_id = data_get($data, 'merchant_id');
                $order->buyer_id = Auth::user()->id;
                $order->trx_no = static::invoice_num(static::nextOrderId(), 9, "INVO/" . Carbon::now()->year . Carbon::now()->month . Carbon::now()->day . "/MKP/");
                $order->order_date = date('Y/m/d H:i:s', Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now())->setTimezone('Asia/Jakarta')->timestamp);
                $order->total_amount = data_get($data, 'total_amount');
                $order->payment_amount = data_get($data, 'payment_amount');
                $order->total_weight = data_get($data, 'total_weight');
                $order->payment_method = null;
                $order->delivery_method = data_get($data, 'delivery_method');
                $order->related_pln_mobile_customer_id = data_get($data, 'related_pln_mobile_customer_id');
                $order->no_reference = $no_reference;
                $order->save();

                array_map(function ($product) use ($order) {
                    $order_detail = new OrderDetail();
                    $order_detail->order_id = $order->id;
                    $order_detail->detail_type = 1;
                    $order_detail->product_id = data_get($product, 'product_id');
                    $order_detail->quantity = data_get($product, 'quantity');
                    $order_detail->price = data_get($product, 'price');
                    $order_detail->weight = data_get($product, 'weight');
                    $order_detail->insurance_cost = data_get($product, 'insurance_cost');
                    $order_detail->discount = data_get($product, 'discount');
                    $order_detail->total_price = data_get($product, 'total_price');
                    $order_detail->total_weight = data_get($product, 'total_weight');
                    $order_detail->total_discount = data_get($product, 'total_discount');
                    $order_detail->total_insurance_cost = data_get($product, 'total_insurance_cost');
                    $order_detail->total_amount = data_get($product, 'total_amount');
                    $order_detail->save();
                }, data_get($data, 'products'));
                    
                $order_progress = new OrderProgress();
                $order_progress->order_id = $order->id;
                $order_progress->status_code = 0;
                $order_progress->status_name = 'Pesanan Belum Dibayar';
                $order_progress->note = null;
                $order_progress->status = 1;
                $order_progress->save();

                $order_payment = new OrderPayment();
                $order_payment->order_id = $order->id;
                $order_payment->customer_id = data_get($data, 'related_pln_mobile_customer_id');
                $order_payment->payment_amount = data_get($data, 'payment_amount');
                $order_payment->date_created = $trx_date;
                $order_payment->date_expired = $exp_date;
                $order_payment->payment_method = null;
                $order_payment->booking_code = null;
                $order_payment->payment_note = data_get($data, 'payment_note') ?? null;
            }, $datas);
            
            // return [
            //     ''
            // ]
        } catch (Exception $th) {
            DB::rollBack();
            if (in_array($th->getCode(), self::$error_codes)) {
                throw new Exception($th->getMessage(), $th->getCode());
            }
            throw new Exception($th->getMessage(), 500);
        }
    }

    static function nextOrderId()
    {
        if (Order::count() < 1) {
            return 1;
        } else {
            $last_order_id = Order::orderBy('id', 'DESC')->first()->id;
            return ++$last_order_id;
        }
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

    static function invoice_num ($input, $pad_len = 3, $prefix = null) {
        if ($pad_len <= strlen($input))
        $pad_len++;
        
        if (is_string($prefix))
        return sprintf("%s%s", $prefix, str_pad($input, $pad_len, "0", STR_PAD_LEFT));
        
        return str_pad($input, $pad_len, "0", STR_PAD_LEFT);
    }    
};

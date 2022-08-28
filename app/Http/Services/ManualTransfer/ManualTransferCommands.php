<?php

namespace App\Http\Services\ManualTransfer;

use App\Http\Services\Service;
use App\Models\ManualTransferInquiry;
use App\Models\OrderPayment;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class ManualTransferCommands extends Service
{
    public function create($request)
    {
        try {
            DB::beginTransaction();

            $payment = OrderPayment::where('payment_method', 'bank-transfer')->getByRefnum($request['idpel'])->with(['order', 'order.merchant', 'customer'])->first();
            if (empty($payment)) throw new Exception('payment not found', 404);

            $random_num = sprintf("%03d", mt_rand(1, 999));
            $manual_tf_inquiry = ManualTransferInquiry::create([
                'idtrx' => date('YmdHis') . $random_num,
                'kodebank' => 'bank-transfer',
                'idpel' => $payment->no_reference,
                'produk' => $request['produk'],
                'caref' => $payment->order->trx_no,
                'date_expired' => Carbon::now()->addMinutes(30),
            ]);

            DB::commit();

            return [
                "idtrx" => $manual_tf_inquiry->idtrx,
                "kodebank" => $manual_tf_inquiry->kodebank,
                "idpel" => $manual_tf_inquiry->idpel,
                "produk" => $manual_tf_inquiry->produk,
                "refnum" => $manual_tf_inquiry->idpel,
                "caref" => $manual_tf_inquiry->caref,
                "nama" => $payment->customer->full_name,
                "rpadminBank" => 0,
                "rpadminAggregator" => 0,
                "rptag" => $payment->payment_amount + $payment->uniq_code,
                "rptotal" => $payment->payment_amount + $payment->uniq_code,
                "expired" => $manual_tf_inquiry->date_expired->format('Y/m/d H:i:s'),
                "billinfo1" => null,
                "billinfo2" => null,
                "billinfo3" => null,
                "billinfo4" => null,
                "billinfo5" => null,
                "billinfo6" => null,
                "billinfo7" => null,
                "billinfo8" => null,
                "billinfo9" => null,
                "billinfo10" => null,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }
}

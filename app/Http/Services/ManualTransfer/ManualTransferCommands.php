<?php

namespace App\Http\Services\ManualTransfer;

use App\Http\Services\Service;
use App\Models\InquiryToken;
use App\Models\ManualTransferInquiry;
use App\Models\OrderPayment;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class ManualTransferCommands extends Service
{
    static $inquiry_baseurl, $inquiry_username, $inquiry_password;

    function __construct()
    {
        self::$inquiry_baseurl = env('INQUIRY_BASEURL');
        self::$inquiry_username = env('MANDIRI_DEBIT_USERNAME');
        self::$inquiry_password = env('MANDIRI_DEBIT_PASSWORD');
    }

    private function generateUniqCode()
    {
        $num_str = sprintf("%03d", mt_rand(100, 999));

        return $num_str;
    }

    public function create($request)
    {
        try {
            DB::beginTransaction();

            $payment = OrderPayment::getByRefnum($request['idpel'])
                ->where('date_expired', '>', Carbon::now('Asia/Jakarta'))
                ->whereIn('status_verification', ['unpaid', 'waiting_verification'])
                ->with(['order', 'order.merchant', 'customer'])->first();

            if (empty($payment)) {
                return ['kode' => 91, 'pesan' => 'TAGIHAN TIDAK DITEMUKAN'];
            }

            // Merchant bukan volta
            if ($payment->order->merchant_id != env('VOLTA_MERCHANT_ID')) {
                return ['kode' => 92, 'pesan' => 'PEMBAYARAN BANK TRANSFER SAAT INI HANYA BERLAKU UNTUK MERCHANT VOLTA'];
            }

            $random_num = sprintf("%03d", mt_rand(100, 999));
            $payments = OrderPayment::where('payment_method', 'bank-transfer')->whereDate('created_at', date('Y-m-d'))->get();
            $uniq_code = $this->generateUniqCode();

            if ($payments->count() > 0) {
                foreach ($payments as $pay) {
                    $total_payment = $pay->payment_amount + $pay->uniq_code;
                    if ($total_payment == ($payment->payment_amount + $uniq_code)) {
                        $uniq_code = $this->generateUniqCode();
                    }
                }
            }

            $payment->update([
                'date_expired' => Carbon::now()->addMinutes(30),
                'payment_method' => 'bank-transfer',
                'uniq_code' => $uniq_code,
                'status_verification' => 'unpaid',
            ]);

            DB::commit();

            return [
                'kode' => '00',
                'pesan' => 'SUKSES',
                'data' => [
                    "idtrx" => $payment->no_reference,
                    "kodebank" => $payment->payment_method,
                    "idpel" => $payment->no_reference,
                    "produk" => $payment->produk,
                    "refnum" => date('YmdHis') . $random_num,
                    "caref" => $payment->order->trx_no,
                    "nama" => $payment->customer->full_name,
                    "rpadminBank" => 0,
                    "rpadminAggregator" => 0,
                    "rptag" => $payment->payment_amount + $payment->uniq_code,
                    "rptotal" => $payment->payment_amount + $payment->uniq_code,
                    "expired" => $payment->date_expired->format('Y/m/d H:i:s'),
                    "billinfo1" => '1260010026531',
                    "billinfo2" => null,
                    "billinfo3" => null,
                    "billinfo4" => null,
                    "billinfo5" => null,
                    "billinfo6" => null,
                    "billinfo7" => null,
                    "billinfo8" => null,
                    "billinfo9" => null,
                    "billinfo10" => null,
                ]
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public function getToken()
    {
        try {
            $url = sprintf('%s/%s', self::$inquiry_baseurl, 'gettoken');

            $response = self::$curl->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode(self::$inquiry_username . ':' . self::$inquiry_password),
                ],
            ]);

            $response = json_decode($response->getBody(), true);

            throw_if(!$response, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh'));

            $data = $response['data'];
            DB::beginTransaction();
            InquiryToken::where('status', 1)->update(['status' => 0]);
            InquiryToken::create([
                'token' => $data['token'],
                'type' => $data['type'],
                'status' => 1,
            ]);
            DB::commit();

            return $response;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }
}

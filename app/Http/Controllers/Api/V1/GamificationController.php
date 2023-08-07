<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Transaction\TransactionQueries;
use Exception;

class GamificationController extends Controller
{
    protected $transactionQueries, $transactionCommands;

    public function __construct()
    {
        $this->transactionQueries = new TransactionQueries();
        // $this->transactionCommands = new TransactionCommands();
    }

    public function orderByRefence($no_reference)
    {
        if (!$this->checkKey()) {
            return $this->respondWithResult(false, 'Key tidak valid.', 401);
        }

        try {
            $data = $this->transactionQueries->getTransactionByReference($no_reference);

            if (!$data) {
                return $this->respondWithResult(false, 'Data tidak ditemukan.', 404);
            }

            return $this->respondWithData($this->responMapper($data), 'Data berhasil didapatkan.');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    private function responMapper($data)
    {
        $respon = array_map(function ($item) {
            $total_product = (float) $item['total_amount'] - (float) $item['delivery']['delivery_fee'];

            return [
                'id' => $item['id'],
                'buyer_id' => $item['buyer_id'],
                'merchant_id' => $item['merchant_id'],
                'trx_no' => $item['trx_no'],
                'no_reference' => $item['no_reference'],
                'order_date' => $item['order_date'],
                'total_amount' => (float) $item['total_amount'],
                'total_without_shipping' => $total_product,
                'created_at' => $item['created_at'],
                'updated_at' => $item['updated_at'],
            ];
        }, $data->toArray());

        return $respon;
    }

    private function checkKey()
    {
        $key = (string) request()->header('key');
        $timestamp = request()->header('timestamp');

        date_default_timezone_set('Asia/Jakarta');
        if (time() - $timestamp >= 300) {
            return false;
        }

        $hash = hash_hmac('sha256', 'PLN-MKP' . $timestamp, env('GAMIFICATION_CHECKORDER_KEY'));

        if (hash_equals($hash, $key) === false) {
            return false;
        }

        return true;
    }
}

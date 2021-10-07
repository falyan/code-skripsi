<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Transaction\TransactionCommands;
use App\Http\Services\Transaction\TransactionQueries;
use App\Models\Customer;
use App\Models\Merchant;
use Exception, Input;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->transactionQueries = new TransactionQueries();
        $this->transactionCommand = new TransactionCommands();
    }

    #region Buyer
    public function buyerIndex()
    {
        try {
            if (!$rlc_id = request()->header('Related-Customer-Id')) {
                return $this->respondWithResult(false, 'Kolom related_customer_id kosong', 400);
            }

            if (Auth::check()) {
                $user = Customer::find(Auth::id());
                $data = $this->transactionQueries->getTransaction('buyer_id', $user->id);
            } else {
                $data = $this->transactionQueries->getTransaction('related_pln_mobile_customer_id', $rlc_id);
            }

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            }else {
                return $this->respondWithResult(true, 'belum ada transaksi');
            }
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }

    public function transactionToPay()
    {
        try {
            if (!$rlc_id = request()->header('Related-Customer-Id')) {
                return $this->respondWithResult(false, 'Kolom related_customer_id kosong', 400);
            }

            if (Auth::check()) {
                $user = Customer::find(Auth::id());
                $data = $this->transactionQueries->getTransactionWithStatusCode('buyer_id', $user->id, [0]);
            } else {
                $data = $this->transactionQueries->getTransactionWithStatusCode('related_pln_mobile_customer_id', $rlc_id, [0]);
            }

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            }else {
                return $this->respondWithResult(true, 'tidak ada transaksi yang belum dibayar');
            }
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }

    public function transactionOnApprove()
    {
        try {
            if (!$rlc_id = request()->header('Related-Customer-Id')) {
                return $this->respondWithResult(false, 'Kolom related_customer_id kosong', 400);
            }

            if (Auth::check()) {
                $user = Customer::find(Auth::id());
                $data = $this->transactionQueries->getTransactionWithStatusCode('buyer_id', $user->id, [1]);
            } else {
                $data = $this->transactionQueries->getTransactionWithStatusCode('related_pln_mobile_customer_id', $rlc_id, [1]);
            }

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            }else {
                return $this->respondWithResult(true, 'tidak ada transaksi yang menunggu persetujuan');
            }
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }

    public function transactionOnDelivery()
    {
        try {
            if (!$rlc_id = request()->header('Related-Customer-Id')) {
                return $this->respondWithResult(false, 'Kolom related_customer_id kosong', 400);
            }

            if (Auth::check()) {
                $user = Customer::find(Auth::id());
                $data = $this->transactionQueries->getTransactionWithStatusCode('buyer_id', $user->id, [3, 8]);
            } else {
                $data = $this->transactionQueries->getTransactionWithStatusCode('related_pln_mobile_customer_id', $rlc_id, [3]);
            }

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            }else {
                return $this->respondWithResult(true, 'tidak ada transaksi yang sedang dikirim');
            }
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }

    public function buyerTransactionDone()
    {
        try {
            if (!$rlc_id = request()->header('Related-Customer-Id')) {
                return $this->respondWithResult(false, 'Kolom related_customer_id kosong', 400);
            }

            if (Auth::check()) {
                $user = Customer::find(Auth::id());
                $data = $this->transactionQueries->getTransactionWithStatusCode('buyer_id', $user->id, [88]);
            } else {
                $data = $this->transactionQueries->getTransactionWithStatusCode('related_pln_mobile_customer_id', $rlc_id, [88]);
            }

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            }else {
                return $this->respondWithResult(true, 'belum ada transaksi yang selesai');
            }
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }

    public function buyerTransactionCanceled()
    {
        try {
            if (!$rlc_id = request()->header('Related-Customer-Id')) {
                return $this->respondWithResult(false, 'Kolom related_customer_id kosong', 400);
            }

            if (Auth::check()) {
                $user = Customer::find(Auth::id());
                $data = $this->transactionQueries->getTransactionWithStatusCode('buyer_id', $user->id, 99);
            } else {
                $data = $this->transactionQueries->getTransactionWithStatusCode('related_pln_mobile_customer_id', $rlc_id, 99);
            }

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            }else {
                return $this->respondWithResult(true, 'tidak ada transaksi yang dibatalkan');
            }
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }

    public function buyerSearchTransaction($keyword)
    {
        try {
            if (!$rlc_id = request()->header('Related-Customer-Id')) {
                return $this->respondWithResult(false, 'Kolom related_customer_id kosong', 400);
            }

            if (strlen(trim($keyword)) < 3) {
                return $this->respondWithResult(false, 'Kata kunci minimal 3 karakter', 400);
            }

            if (Auth::check()) {
                $user = Customer::find(Auth::id());
                $data = $this->transactionQueries->searchTransaction('buyer_id', $user->id, $keyword);
            } else {
                $data = $this->transactionQueries->searchTransaction('related_pln_mobile_customer_id', $rlc_id, $keyword);
            }

            return($data->count());
            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            }else {
                return $this->respondWithResult(false, 'transaksi untuk kata kunci ' . $keyword . ' tidak ditemukan');
            }
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }
    #End Region Buyer
    
    #Region Seller
    public function sellerIndex()
    {
        try {
            $data = $this->transactionQueries->getTransaction('merchant_id', Auth::user()->merchant_id);

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            }else {
                return $this->respondWithResult(true, 'belum ada transaksi');
            }
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }

    
    public function newOrder()
    {
        try {
            $data = $this->transactionQueries->getTransactionWithStatusCode('merchant_id', Auth::user()->merchant_id, [1]);

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            }else {
                return $this->respondWithResult(true, 'belum ada pesanan baru');
            }
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }

    public function orderToDeliver()
    {
        try {
            $data = $this->transactionQueries->getTransactionWithStatusCode('merchant_id', Auth::user()->merchant_id, [2]);

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');;
            }else {
                return $this->respondWithResult(true, 'belum ada pesanan yang siap dikirim');
            }
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }

    public function orderInDelivery()
    {
        try {
            $data = $this->transactionQueries->getTransactionWithStatusCode('merchant_id', Auth::user()->merchant_id, [3,8]);

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');;
            }else {
                return $this->respondWithResult(true, 'tidak ada pesanan yang sedang dikirim');
            }
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }

    public function orderDone()
    {
        try {
            $data = $this->transactionQueries->getTransactionWithStatusCode('merchant_id', Auth::user()->merchant_id, [88]);

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');;
            }else {
                return $this->respondWithResult(true, 'belum ada pesanan yang berhasil');
            }
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }

    public function sellerTransactionCanceled()
    {
        try {
            $data = $this->transactionQueries->getTransactionWithStatusCode('merchant_id', Auth::user()->merchant_id, 99);

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');;
            }else {
                return $this->respondWithResult(true, 'belum ada pesanan yang dibatalkan');
            }
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }

    public function sellerSearchTransaction($keyword)
    {
        try {
            
            if (strlen(trim($keyword)) < 3) {
                return $this->respondWithResult(false, 'Kata kunci minimal 3 karakter', 400);
            }

            $data = $this->transactionQueries->searchTransaction('merchant_id', Auth::user()->merchant_id, $keyword);

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data transaksi');
            }else {
                return $this->respondWithResult(false, 'transaksi untuk kata kunci ' . $keyword . ' tidak ditemukan');
            }
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }
    #End Region

    public function detailTransaction($id)
    {
        try {
            $data = $this->transactionQueries->getDetailTransaction($id);

            if (!empty($data)) {
                return $this->respondWithData($data, 'sukses get data transaksi');;
            }else {
                return $this->respondWithResult(false, 'ID transaksi salah', 400);
            }
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }
}

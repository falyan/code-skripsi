<?php

namespace App\Http\Services\Merchant\Agent;

use App\Http\Services\AgentMasterData\AgentMasterDataQueries;
use App\Http\Services\Iconcash\IconcashCommands;
use App\Http\Services\Manager\AgentManager;
use App\Http\Services\Service;
use App\Models\AgentMargin;
use App\Models\AgentMasterData;
use App\Models\AgentOrder;
use App\Models\AgentOrderProgres;
use App\Models\AgentPayment;
use App\Models\Merchant;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AgentCommands extends Service
{
    protected $agentManager, $agentMasterDataQueries, $iconcashCommands;

    public function __construct()
    {
        $this->agentManager = new AgentManager();
        $this->agentMasterDataQueries = new AgentMasterDataQueries();
        $this->iconcashCommands = new IconcashCommands();
    }

    public function setMargin($request)
    {
        try {
            DB::beginTransaction();

            $merchant_id = Auth::user()->merchant_id;
            $check_agent = AgentMargin::where('merchant_id', $merchant_id)->where('agent_menu_id', $request['agent_menu_id'])->first();
            if (!empty($check_agent)) {
                $check_agent->update([
                    'margin' => $request['margin'],
                    'updated_by' => Auth::user()->full_name,
                ]);
                DB::commit();
                return $check_agent;
            }

            $agent = AgentMargin::create([
                'merchant_id' => $merchant_id,
                'agent_menu_id' => $request['agent_menu_id'],
                'margin' => $request['margin'],
                'created_by' => Auth::user()->full_name,
            ]);

            DB::commit();

            return $agent;
        } catch (Exception $e) {
            DB::rollBack();
            if (in_array($e->getCode(), self::$error_codes)) {
                throw new Exception($e->getMessage(), $e->getCode());
            }
            throw new Exception($e->getMessage(), 500);
        }
    }

    // ========== ICONPAY V3 API ===========

    public function getInfoTagihanPostpaidV3($request)
    {
        try {
            $payload = [
                'customer_id' => $request->idpel,
            ];

            $merchant = Merchant::find(Auth::user()->merchant_id);

            if ($merchant->status == 3) {
                Log::info('Merchant tidak aktif');
                $data['status'] = 'error';
                $data['message'] = 'Akun PLN Agen Anda sedang tidak aktif. Hubungi plnagen@pln.co.id untuk mengaktifkan kembali.';
                return $data;
            }

            $hitCount = $merchant->api_count;
            if ($hitCount >= 5) {
                $merchant->status = 3;
                $merchant->save();

                Log::info('Request API melebihi batas, merchant di nonaktifkan');

                $data['status'] = 'error';
                $data['message'] = 'Akun PLN Agen Anda sedang tidak aktif. Hubungi plnagen@pln.co.id untuk mengaktifkan kembali.';
                return $data;
            }
            $hitCount++;
            $merchant->api_count = $hitCount;
            $merchant->save();

            $response = $this->agentManager->inquiryPostpaidV3($payload);
            if ($response['response_code'] == '0000') {
                // $response['transaction_detail']['customer_name'] = generate_name_secret($response['transaction_detail']['customer_name']);
                $response['transaction_detail']['secret_customer_name'] = generate_name_secret($response['transaction_detail']['customer_name']);
            }

            return $response;
        } catch (Exception $e) {
            if (in_array($e->getCode(), self::$error_codes)) {
                throw new Exception($e->getMessage(), $e->getCode());
            }
            throw new Exception($e->getMessage(), 500);
        }
    }

    public function getInquiryPostpaidV3($request)
    {

        $thbl_list = '';
        foreach ($request['data']['item_detail'] as $item) {
            $tarifdaya = $item['tarif'] . '/' . $item['daya'] . 'VA';
            $lembar_tagihan = $item['lembar_tagihan'];
            $sisa_lembar_tagihan = $item['sisa_lembar_tagihan'];
            $no_meter = $item['no_meter'];
            $idpel = $item['idpel'];
            $thbl = $item['thbl'];
            $thbl = substr($thbl, 0, 6);
            $thbl_list .= $thbl . ', ';
        }
        $thbl_list = rtrim($thbl_list, ', ');

        $fee_postpaid = AgentMasterData::where(['type' => 'fee_tagihan_listrik', 'status' => 1])->first();

        try {
            DB::beginTransaction();

            //INSERT TO DB ORDER
            $order = AgentOrder::create([
                'merchant_id' => Auth::user()->merchant_id,
                'customer_id' => $idpel,
                'meter_number' => $no_meter,
                'customer_name' => data_get($request, 'data.customer_name'),
                'trx_no' => data_get($request, 'data.transaction_id'),
                'product_id' => data_get($request, 'data.product_id'),
                'product_name' => data_get($request, 'data.product'),
                'product_key' => 'POSTPAID',
                'product_value' => $tarifdaya,
                'blth' => $thbl_list,
                'order_date' => data_get($request, 'data.transaction_date'),
                'amount' => data_get($request, 'data.amount'),
                'fee_agent' => $fee_postpaid->fee,
                'fee_iconpay' => data_get($request, 'data.total_fee'),
                'total_fee' => $fee_postpaid->fee + data_get($request, 'data.total_fee'),
                'total_amount' => data_get($request, 'data.total_amount') + $fee_postpaid->fee,
                'order_detail' => json_encode(['create' => $request['data']]),
                'margin' => $request['margin'] ?? 0,
                'created_by' => Auth::user()->id,
            ]);

            // $order->invoice_no = static::invoice_num($order->id, 4, "INV/" . $order->product_id . "/" . date('Ymd')) . "/";
            $order->invoice_no = 'INV/' . $order->product_id . '/' . date('Ymd') . '/' . $order->id;
            $order->save();

            $order_progress = AgentOrderProgres::create([
                'agent_order_id' => $order->id,
                'status_code' => '00',
                'status_name' => 'Menunggu Pembayaran',
                'created_by' => Auth::user()->id,
            ]);

            DB::commit();

            $response['status'] = 'success';
            $response['message'] = 'Berhasil melakukan inquiry';
            $response['data'] = [
                'info' => [
                    'status_name' => $order_progress->status_name,
                    'trx_no' => $order->trx_no,
                    'order_date' => $order->order_date,
                ],
                'detail' => [
                    'product_name' => $order->product_name,
                    'customer_id' => $order->customer_id,
                    'customer_name' => $order->customer_name,
                    'secret_customer_name' => generate_name_secret($order->customer_name),
                    'lembar_tagihan' => $lembar_tagihan,
                    'sisa_lembar_tagihan' => $sisa_lembar_tagihan,
                    'thbl' => $thbl_list,
                    'amount' => $order->amount,
                ],
                'payment' => [
                    'amount' => $order->amount,
                    'total_fee' => $order->total_fee,
                    'margin' => $order->margin,
                    'total_amount' => $order->total_amount,
                ],
            ];

            return $response;
        } catch (Exception $e) {
            DB::rollBack();
            if (in_array($e->getCode(), self::$error_codes)) {
                throw new Exception($e->getMessage(), $e->getCode());
            }
            throw new Exception($e->getMessage(), 500);
        }
    }

    public function getInfoTagihanPrepaidV3($request)
    {
        try {
            $payload = [
                'customer_id' => $request->idpel,
            ];

            $merchant = Merchant::find(Auth::user()->merchant_id);

            if ($merchant->status == 3) {
                Log::info('Merchant tidak aktif');
                $data['status'] = 'error';
                $data['message'] = 'Akun PLN Agen Anda sedang tidak aktif. Hubungi plnagen@pln.co.id untuk mengaktifkan kembali.';
                return $data;
            }

            $response = $this->agentManager->inquiryPrepaidV3($payload);

            if ($response['response_code'] == '0000' && $response['transaction_detail'] != null) {
                $list_denom = AgentMasterData::status(1)->type('token_listrik')->orderBy('value', 'ASC')->select('id', 'name', 'key', 'value', 'fee', 'type', 'strike_value', 'status')->get();

                $unsold1 = $response['transaction_detail']['item_detail'][0]['unsold1'];
                $unsold2 = $response['transaction_detail']['item_detail'][0]['unsold2'];

                if ($unsold1 != "0") {
                    $list_denom->prepend([
                        'id' => 0,
                        'name' => 'Token Unsold 1',
                        'value' => (int) $unsold1,
                        'type' => 'token_unsold',
                    ]);
                }

                if ($unsold2 != "0") {
                    $list_denom->prepend([
                        'id' => 0,
                        'name' => 'Token Unsold 2',
                        'value' => (int) $unsold2,
                        'type' => 'token_unsold',
                    ]);
                }

                $response['transaction_detail']['secret_customer_name'] = generate_name_secret($response['transaction_detail']['customer_name']) ?? $response['transaction_detail']['customer_name'];
                $response['transaction_detail']['denom'] = $list_denom;
            }

            return $response;
        } catch (Exception $e) {
            if (in_array($e->getCode(), self::$error_codes)) {
                throw new Exception($e->getMessage(), $e->getCode());
            }
            throw new Exception($e->getMessage(), 500);
        }
    }

    public function getInquiryPrepaidV3($request)
    {
        try {
            DB::beginTransaction();

            if (isset($request['unsold_value']) && !empty($request['unsold_value'])) {
                $unsold = $request['unsold_value'];
            } else {
                $denom = AgentMasterData::find($request['denom_id']);
            };

            foreach ($request['data']['item_detail'] as $item) {
                $tarifdaya = $item['tarif'] . '/' . $item['daya'] . 'VA';
                $no_meter = $item['no_meter'];
                $idpel = $item['idpel'];
                // $jml_kwh = str_replace(',', '.', $item['jml_kwh']);
            }

            //INSERT TO DB ORDER
            $order = AgentOrder::create([
                'merchant_id' => Auth::user()->merchant_id,
                'customer_id' => $idpel,
                'meter_number' => $no_meter,
                'customer_name' => data_get($request, 'data.customer_name'),
                'trx_no' => data_get($request, 'data.transaction_id'),
                'product_id' => data_get($request, 'data.product_id'),
                'product_name' => data_get($request, 'data.product'),
                'product_key' => isset($unsold) ? 'UNSOLD' : $denom->key,
                'product_value' => $tarifdaya,
                'order_date' => data_get($request, 'data.transaction_date'),
                'amount' => isset($unsold) ? $unsold : $denom->value,
                'fee_agent' => isset($unsold) ? 0 : $denom->fee,
                'fee_iconpay' => isset($unsold) ? 0 : $denom->fee_iconpay,
                'total_fee' => isset($unsold) ? 0 : $denom->fee + $denom->fee_iconpay,
                'total_amount' => isset($unsold) ? $unsold : $denom->value + $denom->fee + $denom->fee_iconpay,
                'order_detail' => json_encode(['create' => $request['data']]),
                'margin' => $request['margin'] ?? 0,
                // 'jml_kwh' => number_format($jml_kwh, 2, ',', '.'),
                'created_by' => Auth::user()->id,
            ]);

            // $order->invoice_no = static::invoice_num($order->id, 4, "INV/" . $order->product_id . "/" . date('Ymd')) . "/";
            $order->invoice_no = 'INV/' . $order->product_id . '/' . date('Ymd') . '/' . $order->id;
            $order->save();

            $order_progress = AgentOrderProgres::create([
                'agent_order_id' => $order->id,
                'status_code' => '00',
                'status_name' => 'Menunggu Pembayaran',
                'created_by' => Auth::user()->id,
            ]);

            DB::commit();

            $response['status'] = 'success';
            $response['message'] = 'Berhasil melakukan inquiry';
            $response['data'] = [
                'info' => [
                    'status_name' => $order_progress->status_name,
                    'trx_no' => $order->trx_no,
                    'order_date' => $order->order_date,
                ],
                'detail' => [
                    'product_name' => $order->product_name,
                    'customer_id' => $no_meter,
                    'customer_name' => $order->customer_name,
                    'secret_customer_name' => generate_name_secret($order->customer_name) ?? $order->customer_name,
                    'product_value' => $order->product_value,
                ],
                'payment' => [
                    'amount' => (int) $order->amount,
                    'total_fee' => (int) $order->total_fee,
                    'margin' => (int) $order->margin,
                    'total_amount' => (int) $order->total_amount,
                ],
            ];

            return $response;
        } catch (Exception $e) {
            DB::rollBack();
            if (in_array($e->getCode(), self::$error_codes)) {
                throw new Exception($e->getMessage(), $e->getCode());
            }
            throw new Exception($e->getMessage(), 500);
        }
    }

    public function getInfoManualAdviceV3($request, $page, $limit)
    {
        try {
            DB::beginTransaction();

            $merchant = Merchant::find(Auth::user()->merchant_id);
            $hitCount = $merchant->api_count;

            if ($hitCount >= 5 || $merchant->status == 3) {
                Log::info('Merchant tidak aktif');
                $data['status'] = 'error';
                $data['message'] = 'Akun PLN Agen Anda sedang tidak aktif. Hubungi plnagen@pln.co.id untuk mengaktifkan kembali.';
                return $data;
            }

            $order = new AgentOrder;
            $orders = $order->with([
                'progress_active',
                'payment',
            ])->where(function ($query) use ($request) {
                $query->where('customer_id', $request->idpel)
                    ->orWhere('meter_number', $request->idpel);
            })->whereHas('progress_active', function ($query) {
                $query->where('status_code', '09');
            })->whereDate('created_at', Carbon::now())->orderBy('created_at', 'desc')->get();

            // Append data secret_customer_name to $orders
            foreach ($orders as $key => $order) {
                $orders[$key]->secret_customer_name = generate_name_secret($order->customer_name) ?? $order->customer_name;
            }

            if (count($orders) == 0) {
                $response['status'] = 'error';
                $response['message'] = 'Data transaksi tidak ditemukan';
                return $response;
            }

            $orders = static::paginate($orders->toArray(), $limit, $page);

            $response['success'] = true;
            $response['message'] = 'Data transaksi berhasil ditemukan';
            $response['data'] = $orders;

            return $response;
        } catch (Exception $e) {
            DB::rollBack();
            if (in_array($e->getCode(), self::$error_codes)) {
                throw new Exception($e->getMessage(), $e->getCode());
            }
            throw new Exception($e->getMessage(), 500);
        }
    }

    // ======= CONFIRM PAYMENT TO ICONPAY PROCESS ======= //
    public static function confirmOrderIconcash($trx_no, $token, $client_ref, $source_account_id)
    {
        $order = AgentOrder::with([
            'progress_active',
            'payment' => fn($q) => $q->where('payment_method', 'iconcash')->where('trx_reference', $client_ref),
        ])
            ->where('trx_no', $trx_no)
            ->first();

        if (empty($order)) {
            return [
                'status' => 'error',
                'message' => 'Order tidak ditemukan',
            ];
        }

        if ($order->progress_active->status_code != '03') {
            return [
                'status' => 'error',
                'message' => 'Belum melakukan konfirmasi',
            ];
        }
        $payment_scenario = $order->payment->payment_scenario;

        $merchant = Merchant::find($order->merchant_id);
        $merchant->api_count = 0;
        $merchant->save();

        try {
            DB::beginTransaction();
            //Scenario For UAT Only - Normal
            if ($payment_scenario == 'normal' || $payment_scenario == null) {
                $start_time = microtime(true);
                //Payment Iconpay
                $response = AgentManager::confirmOrderIconcash(array_merge($order->toArray(), [
                    'client_ref' => $client_ref,
                    'buying_options' => $order->product_key == 'UNSOLD' ? 1 : 0,
                ]));
                // sleep(30);
                if (in_array($response['response_code'], ['5002', '5010', '5016', '408'])) {
                    sleep(20);
                }
                $end_time = microtime(true);
                $execution_time = ($end_time - $start_time);

                if (in_array($response['response_code'], ['5002', '5010', '5016', '408']) || $execution_time >= 20) {
                    if ($order->product_id == 'PREPAID') {
                        $response = static::advicePrepaid($order, null, null, null, $token, $client_ref, $source_account_id);
                        DB::commit();
                        return $response;
                    } else if ($order->product_id == 'POSTPAID') {
                        $response = static::reversalPostpaid($order, null, null, null, null, null, $token, $client_ref, $source_account_id);
                        DB::commit();
                        return $response;
                    }
                } else if ($response['response_code'] == '0000' && $execution_time <= 20 && $response['transaction_detail'] != null) {
                    //Payment Success
                    AgentOrderProgres::where('agent_order_id', $order->id)->update([
                        'status' => 0,
                        'updated_by' => 'system',
                    ]);

                    AgentOrderProgres::create([
                        'agent_order_id' => $order->id,
                        'status_code' => '04',
                        'status_note' => $response['response_message'],
                        'status_name' => static::$status_agent_order['04'],
                        'created_by' => 'system',
                    ]);

                    if ($response['transaction_detail'] != null) {
                        $response['transaction_detail']['customer_name'] = generate_name_secret($response['transaction_detail']['customer_name']);
                    }

                    AgentPayment::create([
                        'agent_order_id' => $order->id,
                        'payment_id' => $response['transaction_detail']['biller_reference'],
                        'payment_method' => 'iconpay',
                        'trx_reference' => $response['transaction_detail']['transaction_id'],
                        'payment_detail' => json_encode(['create' => null, 'confirm' => $response['transaction_detail']]),
                        'payment_scenario' => $payment_scenario ?? null,
                        'amount' => $response['transaction_detail']['amount'],
                        'fee_agent' => $order->fee_agent,
                        'fee_iconpay' => $response['transaction_detail']['total_fee'],
                        'total_fee' => $response['transaction_detail']['total_fee'] + $order->fee_agent,
                        'total_amount' => $response['transaction_detail']['total_amount'] + $order->fee_agent,
                        'created_by' => 'system',
                    ]);

                    DB::commit();
                    $data['status'] = 'success';
                    $data['message'] = 'Transaksi berhasil';
                    return $data;
                } else {
                    static::updateAgentOrderStatus($order->id, '08', $response['response_message']);
                    DB::commit();

                    // Async Refund Via Queue
                    // dispatch(new AgentRefundIconcashJob());
                    IconcashCommands::orderRefund($order->trx_no, $token, $client_ref, $source_account_id);

                    $data['status'] = 'success';
                    $data['message'] = 'Transaksi gagal - ' . $response['response_code'];
                    return $data;
                }
            }

            //Scenario For UAT Only - Reversal Postpaid
            if ($payment_scenario == 'reversal') {
                $start_time = microtime(true);
                //Payment Iconpay
                $response = AgentManager::confirmOrderIconcash(array_merge($order->toArray(), [
                    'client_ref' => $client_ref,
                    'buying_options' => $order->product_key == 'UNSOLD' ? 1 : 0,
                ]));
                sleep(20);
                $end_time = microtime(true);
                $execution_time = ($end_time - $start_time);

                //Handle Payment Pending & Timeout
                if ($response['response_code'] == '5002' || $response['response_code'] == '5010' || $response['response_code'] == '5016' || $response['response_code'] == '408' || $execution_time >= 20) {
                    if ($order->product_id == 'POSTPAID') {
                        $response = static::reversalPostpaid($order, null, null, null, null, null, $token, $client_ref, $source_account_id);

                        DB::commit();
                        return $response;
                    }
                }
            }

            //Scenario For UAT Only - Repeat Reversal Postpaid
            if ($payment_scenario == 'repeat_reversal') {
                $start_time = microtime(true);
                //Payment Iconpay
                $response = AgentManager::confirmOrderIconcash(array_merge($order->toArray(), [
                    'client_ref' => $client_ref,
                    'buying_options' => $order->product_key == 'UNSOLD' ? 1 : 0,
                ]));
                sleep(20);
                $end_time = microtime(true);
                $execution_time = ($end_time - $start_time);

                //Handle Payment Pending & Timeout
                if ($response['response_code'] == '5002' || $response['response_code'] == '5010' || $response['response_code'] == '5016' || $response['response_code'] == '408' || $execution_time >= 20) {
                    if ($order->product_id == 'POSTPAID') {
                        $response = static::reversalPostpaid($order, 20, null, null, null, null, $token, $client_ref, $source_account_id);

                        DB::commit();
                        return $response;
                    }
                }
            }

            //Scenario For UAT Only - Reversal Postpaid
            if ($payment_scenario == 'fourth_reversal') {
                $start_time = microtime(true);
                //Payment Iconpay
                $response = AgentManager::confirmOrderIconcash(array_merge($order->toArray(), [
                    'client_ref' => $client_ref,
                    'buying_options' => $order->product_key == 'UNSOLD' ? 1 : 0,
                ]));
                sleep(20);
                $end_time = microtime(true);
                $execution_time = ($end_time - $start_time);

                //Handle Payment Pending & Timeout
                if ($response['response_code'] == '5002' || $response['response_code'] == '5010' || $response['response_code'] == '5016' || $response['response_code'] == '408' || $execution_time >= 20) {
                    if ($order->product_id == 'POSTPAID') {
                        $response = static::reversalPostpaid($order, 20, 20, 20, 20, null, $token, $client_ref, $source_account_id);

                        DB::commit();
                        return $response;
                    }
                }
            }

            //Scenario For UAT Only - Late Reversal Postpaid
            if ($payment_scenario == 'late_reversal') {
                $start_time = microtime(true);
                //Payment Iconpay
                $response = AgentManager::confirmOrderIconcash(array_merge($order->toArray(), [
                    'client_ref' => $client_ref,
                    'buying_options' => $order->product_key == 'UNSOLD' ? 1 : 0,
                ]));
                sleep(122);
                $end_time = microtime(true);
                $execution_time = ($end_time - $start_time);

                //Handle Payment Pending & Timeout
                if ($response['response_code'] == '5002' || $response['response_code'] == '5010' || $response['response_code'] == '5016' || $response['response_code'] == '408' || $execution_time >= 20) {
                    if ($order->product_id == 'POSTPAID') {
                        $response = static::reversalPostpaid($order, null, null, null, null, null, $token, $client_ref, $source_account_id);

                        DB::commit();
                        return $response;
                    }
                }
            }

            //Scenario For UAT Only - Advice Prepaid
            if ($payment_scenario == 'advice') {
                $start_time = microtime(true);
                //Payment Iconpay
                $response = AgentManager::confirmOrderIconcash(array_merge($order->toArray(), [
                    'client_ref' => $client_ref,
                    'buying_options' => $order->product_key == 'UNSOLD' ? 1 : 0,
                ]));
                sleep(20);
                $end_time = microtime(true);
                $execution_time = ($end_time - $start_time);

                //Handle Payment Pending & Timeout
                if ($response['response_code'] == '5002' || $response['response_code'] == '5010' || $response['response_code'] == '5016' || $response['response_code'] == '408' || $execution_time >= 20) {
                    if ($order->product_id == 'PREPAID') {
                        $response = static::advicePrepaid($order, null, null, null, $token, $client_ref, $source_account_id);

                        DB::commit();
                        return $response;
                    }
                }
            }

            //Scenario For UAT Only - Repeat Advice Prepaid
            if ($payment_scenario == 'repeat_advice') {
                $start_time = microtime(true);
                //Payment Iconpay
                $response = AgentManager::confirmOrderIconcash(array_merge($order->toArray(), [
                    'client_ref' => $client_ref,
                    'buying_options' => $order->product_key == 'UNSOLD' ? 1 : 0,
                ]));
                sleep(20);
                $end_time = microtime(true);
                $execution_time = ($end_time - $start_time);

                //Handle Payment Pending & Timeout
                if ($response['response_code'] == '5002' || $response['response_code'] == '5010' || $response['response_code'] == '5016' || $response['response_code'] == '408' || $execution_time >= 20) {
                    if ($order->product_id == 'PREPAID') {
                        $response = static::advicePrepaid($order, 20, null, null, $token, $client_ref, $source_account_id);

                        DB::commit();
                        return $response;
                    }
                }
            }

            //Scenario For UAT Only - Manual Advice Prepaid
            if ($payment_scenario == 'manual_advice') {
                $start_time = microtime(true);
                //Payment Iconpay
                $response = AgentManager::confirmOrderIconcash(array_merge($order->toArray(), [
                    'client_ref' => $client_ref,
                    'buying_options' => $order->product_key == 'UNSOLD' ? 1 : 0,
                ]));
                sleep(20);
                $end_time = microtime(true);
                $execution_time = ($end_time - $start_time);

                //Handle Payment Pending & Timeout
                if ($response['response_code'] == '5002' || $response['response_code'] == '5010' || $response['response_code'] == '5016' || $response['response_code'] == '408' || $execution_time >= 20) {
                    if ($order->product_id == 'PREPAID') {
                        $response = static::advicePrepaid($order, 20, 20, 20, $token, $client_ref, $source_account_id);

                        DB::commit();
                        return $response;
                    }
                }
            }

            //Scenario For UAT Only - No Purchase Postpaid Prepaid
            if ($payment_scenario == 'no_purchase') {
                if ($order->product_id == 'PREPAID') {
                    sleep(20);
                    $response = static::advicePrepaid($order, null, null, null, $token, $client_ref, $source_account_id);

                    DB::commit();
                    return $response;
                }

                if ($order->product_id == 'POSTPAID') {
                    sleep(20);
                    $response = static::reversalPostpaid($order, null, null, null, null, null, $token, $client_ref, $source_account_id);

                    DB::commit();
                    return $response;
                }
            }
        } catch (Exception $e) {
            DB::rollBack();
            if (in_array($e->getCode(), self::$error_codes)) {
                throw new Exception($e->getMessage(), $e->getCode());
            }
            throw new Exception($e->getMessage(), 500);
        }
    }

    public static function advicePrepaid($order, $repeat_advice_delay = 0, $second_repeat_advice_delay = 0, $manual_advice_delay = 0, $token, $client_ref, $source_account_id)
    {
        $start_time = microtime(true);
        $advice = AgentManager::advicePrepaidV3(array_merge($order->toArray(), [
            'client_ref' => $client_ref,
            'type' => '01',
            'amount' => $order->payment->amount,
        ]));
        if (in_array($advice['response_code'], ['5002', '5010', '5016', '408'])) {
            sleep(20);
            Log::info('Advice Prepaid Delay 20s RC5002/5010/5016/408, Retry');
        } else {
            sleep($repeat_advice_delay);
        }
        $end_time = microtime(true);
        $execution_time = ($end_time - $start_time);

        //Advice Success
        if ($advice['response_code'] == '0000' && $execution_time <= 20 && $advice['transaction_detail'] != null) {
            static::updateAgentOrderStatus($order->id, '04', $advice['response_message']);

            AgentPayment::create([
                'agent_order_id' => $order->id,
                'payment_id' => $advice['transaction_detail']['biller_reference'] ?? null,
                'payment_method' => 'iconpay',
                'trx_reference' => $advice['transaction_detail']['transaction_id'] ?? null,
                'payment_detail' => json_encode(['create' => null, 'confirm' => $advice['transaction_detail']]),
                'payment_scenario' => $order->payment->payment_scenario ?? null,
                'amount' => $advice['transaction_detail']['amount'] ?? null,
                'fee_agent' => $order->fee_agent ?? null,
                'fee_iconpay' => $advice['transaction_detail']['total_fee'] ?? null,
                'total_fee' => $advice['transaction_detail']['total_fee'] + $order->fee_agent ?? null,
                'total_amount' => $advice['transaction_detail']['total_amount'] + $order->fee_agent ?? null,
                'created_by' => 'system',
            ]);

            $data['status'] = 'success';
            $data['message'] = 'Berhasil melakukan advice - ' . $advice['response_code'];
            return $data;
        } else if ($advice['response_code'] == '5002' || $advice['response_code'] == '5010' || $advice['response_code'] == '5016' || $advice['response_code'] == '408' || $execution_time >= 20) {
            $start_time = microtime(true);
            $repeat_advice = AgentManager::advicePrepaidV3(array_merge($order->toArray(), [
                'client_ref' => $client_ref,
                'type' => '02',
                'amount' => $order->payment->amount,
            ]));
            if (in_array($repeat_advice['response_code'], ['5002', '5010', '5016', '408'])) {
                sleep(20);
                Log::info('Repeat Advice Prepaid Delay 20s RC5002/5010/5016/408, Retry 2nd');
            } else {
                sleep($second_repeat_advice_delay);
            }
            $end_time = microtime(true);
            $execution_time = ($end_time - $start_time);

            //Repeat Advice Success
            if ($repeat_advice['response_code'] == '0000' && $execution_time <= 20 && $repeat_advice['transaction_detail'] != null) {
                static::updateAgentOrderStatus($order->id, '04', $repeat_advice['response_message']);

                AgentPayment::create([
                    'agent_order_id' => $order->id,
                    'payment_id' => $repeat_advice['transaction_detail']['biller_reference'] ?? null,
                    'payment_method' => 'iconpay',
                    'trx_reference' => $repeat_advice['transaction_detail']['transaction_id'] ?? null,
                    'payment_detail' => json_encode(['create' => null, 'confirm' => $repeat_advice['transaction_detail']]),
                    'payment_scenario' => $order->payment->payment_scenario ?? null,
                    'amount' => $repeat_advice['transaction_detail']['amount'] ?? null,
                    'fee_agent' => $order->fee_agent ?? null,
                    'fee_iconpay' => $repeat_advice['transaction_detail']['total_fee'] ?? null,
                    'total_fee' => $repeat_advice['transaction_detail']['total_fee'] + $order->fee_agent ?? null,
                    'total_amount' => $repeat_advice['transaction_detail']['total_amount'] + $order->fee_agent ?? null,
                    'created_by' => 'system',
                ]);

                $data['status'] = 'success';
                $data['message'] = 'Berhasil melakukan repeat advice - ' . $repeat_advice['response_code'];
                return $data;
            } else if ($repeat_advice['response_code'] == '5002' || $repeat_advice['response_code'] == '5010' || $repeat_advice['response_code'] == '5016' || $repeat_advice['response_code'] == '408' || $execution_time >= 20) {
                $start_time = microtime(true);
                $second_repeat_advice = AgentManager::advicePrepaidV3(array_merge($order->toArray(), [
                    'client_ref' => $client_ref,
                    'type' => '02',
                    'amount' => $order->payment->amount,
                ]));
                sleep($manual_advice_delay);
                $end_time = microtime(true);
                $execution_time = ($end_time - $start_time);

                //2nd Repeat Advice Success
                if ($second_repeat_advice['response_code'] == '0000' && $execution_time <= 20 && $second_repeat_advice['transaction_detail'] != null) {
                    static::updateAgentOrderStatus($order->id, '04', $second_repeat_advice['response_message']);

                    AgentPayment::create([
                        'agent_order_id' => $order->id,
                        'payment_id' => $second_repeat_advice['transaction_detail']['biller_reference'] ?? null,
                        'payment_method' => 'iconpay',
                        'trx_reference' => $second_repeat_advice['transaction_detail']['transaction_id'] ?? null,
                        'payment_detail' => json_encode(['create' => null, 'confirm' => $second_repeat_advice['transaction_detail']]),
                        'amount' => $second_repeat_advice['transaction_detail']['amount'] ?? null,
                        'fee_agent' => $order->fee_agent ?? null,
                        'fee_iconpay' => $second_repeat_advice['transaction_detail']['total_fee'] ?? null,
                        'total_fee' => $second_repeat_advice['transaction_detail']['total_fee'] + $order->fee_agent ?? null,
                        'total_amount' => $second_repeat_advice['transaction_detail']['total_amount'] + $order->fee_agent ?? null,
                        'created_by' => 'system',
                    ]);

                    $data['status'] = 'success';
                    $data['message'] = 'Berhasil melakukan second repeat advice - ' . $second_repeat_advice['response_code'];
                    return $data;
                } else if ($second_repeat_advice['response_code'] == '5002' || $second_repeat_advice['response_code'] == '5010' || $second_repeat_advice['response_code'] == '5016' || $second_repeat_advice['response_code'] == '408' || $execution_time >= 20) {
                    static::updateAgentOrderStatus($order->id, '09', $second_repeat_advice['response_message']);

                    $data['status'] = 'success';
                    $data['message'] = 'Gagal melakukan second repeat advice, transaksi dalam status pending - ' . $second_repeat_advice['response_code'];
                    return $data;
                } else {
                    static::updateAgentOrderStatus($order->id, '08', $second_repeat_advice['response_message']);
                    IconcashCommands::orderRefund($order->trx_no, $token, $client_ref, $source_account_id);

                    $data['status'] = 'success';
                    $data['message'] = 'Transaksi gagal - ' . $second_repeat_advice['response_code'];
                    return $data;
                }
            } else {
                static::updateAgentOrderStatus($order->id, '08', $repeat_advice['response_message']);
                IconcashCommands::orderRefund($order->trx_no, $token, $client_ref, $source_account_id);

                $data['status'] = 'success';
                $data['message'] = 'Transaksi gagal - ' . $repeat_advice['response_code'];
                return $data;
            }
            //If Repeat Advice fail or timeout 20s, hit 2nd Repeat Advice
        } else {
            static::updateAgentOrderStatus($order->id, '08', $advice['response_message']);
            IconcashCommands::orderRefund($order->trx_no, $token, $client_ref, $source_account_id);

            $data['status'] = 'success';
            $data['message'] = 'Transaksi gagal - ' . $advice['response_code'];
            return $data;
        }

        return $advice;
    }

    public function manualAdvicePrepaid($request, $token)
    {
        $order = AgentOrder::with([
            'progress_active',
            'payment' => fn($q) => $q->where('payment_method', 'iconcash')->where('trx_reference', $request['client_ref']),
        ])
            ->where('trx_no', $request['transaction_id'])
            ->where('product_id', 'PREPAID')
            ->first();

        if (empty($order)) {
            return [
                'status' => 'error',
                'message' => 'Order tidak ditemukan',
            ];
        }

        if ($order->progress_active->status_code != '09') {
            return [
                'status' => 'error',
                'message' => 'Gagal melakukan manual advice, transaksi tidak dalam status pending',
            ];
        }

        // $iconcash = Auth::user()->iconcash;

        // $manual_advice = $this->advicePrepaid($order, null, null, null, $iconcash->token, $client_ref, $source_account_id);

        $start_time = microtime(true);
        $advice = AgentManager::advicePrepaidV3(array_merge($order->toArray(), [
            'client_ref' => $request['client_ref'],
            'type' => '02',
            'amount' => $order->payment->amount,
        ]));
        // sleep($repeat_advice_delay);
        if (in_array($advice['response_code'], ['5002', '5010', '5016', '408'])) {
            sleep(20);
            Log::info('Advice Prepaid Delay 20s RC5002/5010/5016/408, Retry');
        }
        $end_time = microtime(true);
        $execution_time = ($end_time - $start_time);

        if ($advice['response_code'] == '0000' && $execution_time <= 20 && $advice['transaction_detail'] != null) {
            static::updateAgentOrderStatus($order->id, '04', $advice['response_message']);

            AgentPayment::create([
                'agent_order_id' => $order->id,
                'payment_id' => $advice['transaction_detail']['biller_reference'] ?? null,
                'payment_method' => 'iconpay',
                'trx_reference' => $advice['transaction_detail']['transaction_id'] ?? null,
                'payment_detail' => json_encode(['create' => null, 'confirm' => $advice['transaction_detail']]),
                'payment_scenario' => $order->payment->payment_scenario ?? null,
                'amount' => $advice['transaction_detail']['amount'] ?? null,
                'fee_agent' => $order->fee_agent ?? null,
                'fee_iconpay' => $advice['transaction_detail']['total_fee'] ?? null,
                'total_fee' => $advice['transaction_detail']['total_fee'] + $order->fee_agent ?? null,
                'total_amount' => $advice['transaction_detail']['total_amount'] + $order->fee_agent ?? null,
                'created_by' => 'system',
            ]);

            $data['status'] = 'success';
            $data['message'] = 'Berhasil melakukan advice - ' . $advice['response_code'];
            return $data;
        } else if ($advice['response_code'] == '5002' || $advice['response_code'] == '5010' || $advice['response_code'] == '5016' || $advice['response_code'] == '408' || $execution_time >= 20) {
            $start_time = microtime(true);
            $repeat_advice = AgentManager::advicePrepaidV3(array_merge($order->toArray(), [
                'client_ref' => $request['client_ref'],
                'type' => '02',
                'amount' => $order->payment->amount,
            ]));
            // sleep($second_repeat_advice_delay);
            if (in_array($repeat_advice['response_code'], ['5002', '5010', '5016', '408'])) {
                sleep(20);
                Log::info('Advice Prepaid Delay 20s RC5002/5010/5016/408, Retry 2nd');
            }
            $end_time = microtime(true);
            $execution_time = ($end_time - $start_time);

            //Repeat Advice Success
            if ($repeat_advice['response_code'] == '0000' && $execution_time <= 20 && $repeat_advice['transaction_detail'] != null) {
                static::updateAgentOrderStatus($order->id, '04', $repeat_advice['response_message']);

                AgentPayment::create([
                    'agent_order_id' => $order->id,
                    'payment_id' => $repeat_advice['transaction_detail']['biller_reference'] ?? null,
                    'payment_method' => 'iconpay',
                    'trx_reference' => $repeat_advice['transaction_detail']['transaction_id'] ?? null,
                    'payment_detail' => json_encode(['create' => null, 'confirm' => $repeat_advice['transaction_detail']]),
                    'payment_scenario' => $order->payment->payment_scenario ?? null,
                    'amount' => $repeat_advice['transaction_detail']['amount'] ?? null,
                    'fee_agent' => $order->fee_agent ?? null,
                    'fee_iconpay' => $repeat_advice['transaction_detail']['total_fee'] ?? null,
                    'total_fee' => $repeat_advice['transaction_detail']['total_fee'] + $order->fee_agent ?? null,
                    'total_amount' => $repeat_advice['transaction_detail']['total_amount'] + $order->fee_agent ?? null,
                    'created_by' => 'system',
                ]);

                $data['status'] = 'success';
                $data['message'] = 'Berhasil melakukan repeat advice - ' . $repeat_advice['response_code'];
                return $data;
            } else if ($repeat_advice['response_code'] == '5002' || $repeat_advice['response_code'] == '5010' || $repeat_advice['response_code'] == '5016' || $repeat_advice['response_code'] == '408' || $execution_time >= 20) {
                //Repeat Advice Failed -> Set Pending -> Manual Advice
                static::updateAgentOrderStatus($order->id, '09', $repeat_advice['response_message']);

                $data['status'] = 'success';
                $data['message'] = 'Gagal melakukan repeat advice, transaksi dalam status pending - ' . $repeat_advice['response_code'];
                return $data;
                //2nd Repeat Advice Failed -> Set Pending -> Manual Advice
            } else {
                static::updateAgentOrderStatus($order->id, '08', $repeat_advice['response_message']);
                IconcashCommands::orderRefund($order->trx_no, $token, $request['client_ref'], $request['source_account_id']);

                $data['status'] = 'success';
                $data['message'] = 'Transaksi gagal - ' . $repeat_advice['response_code'];
                return $data;
            }
            //If Repeat Advice fail or timeout 20s, hit 2nd Repeat Advice
        } else {
            static::updateAgentOrderStatus($order->id, '08', $advice['response_message']);
            IconcashCommands::orderRefund($order->trx_no, $token, $request['client_ref'], $request['source_account_id']);

            $data['status'] = 'success';
            $data['message'] = 'Transaksi gagal - ' . $advice['response_code'];
            return $data;
        }

        // return $advice;
    }

    public static function reversalPostpaid($order, $repeat_reversal_delay = 0, $second_reversal_delay = 0, $third_reversal_delay = 0, $fourth_reversal_delay = 0, $manual_reversal_delay = 0, $token, $client_ref, $source_account_id)
    {
        $start_time = microtime(true);
        $reversal = AgentManager::reversalPostpaidV3(array_merge($order->toArray(), [
            'client_ref' => $client_ref,
            'type' => '01',
        ]));
        if (in_array($reversal['response_code'], ['5002', '5010', '5016', '408'])) {
            sleep(20);
            Log::info('Reversal Postpaid Delay 20s RC5002/5010/5016/408, Retry');
        } else {
            sleep($repeat_reversal_delay);
        }
        $end_time = microtime(true);
        $execution_time = ($end_time - $start_time);

        Log::info("E00001", [
            'path_url' => "reversalPostpaid",
            'response' => $reversal,
        ]);

        //Reversal has been taken -> Set Reversal (refund)
        if ($reversal['response_code'] == '0094' && $reversal['transaction_detail'] == null) {
            static::updateAgentOrderStatus($order->id, '05', $reversal['response_message'] == 'Success' ? 'TRANSAKSI GAGAL' : $reversal['response_message']);
            IconcashCommands::orderRefund($order->trx_no, $token, $client_ref, $source_account_id);

            $data['status'] = 'success';
            $data['message'] = 'Reversal sudah dilakukan - ' . $reversal['response_code'];
            return $data;
        }

        //Reversal Payment Not Found -> Set Failed
        if ($reversal['response_code'] == '0063' || $reversal['response_code'] == '0068' && $reversal['transaction_detail'] == null) {
            static::updateAgentOrderStatus($order->id, '08', $reversal['response_message']);
            IconcashCommands::orderRefund($order->trx_no, $token, $client_ref, $source_account_id);

            $data['status'] = 'success';
            $data['message'] = 'Transaksi tidak ditemukan / gagal - ' . $reversal['response_code'];
            return $data;
        }

        //Reversal Cancelled -> Set Failed
        if ($reversal['response_code'] == '5001' && $reversal['transaction_detail'] == null) {
            static::updateAgentOrderStatus($order->id, '08', $reversal['response_message']);
            IconcashCommands::orderRefund($order->trx_no, $token, $client_ref, $source_account_id);

            $data['status'] = 'success';
            $data['message'] = 'Transaksi dibatalkan - ' . $reversal['response_code'];
            return $data;
        }

        //Late Reversal -> Set Paid/Success
        if ($reversal['response_code'] == '0012') {
            static::updateAgentOrderStatus($order->id, '04', $reversal['response_message']);

            AgentPayment::create([
                'agent_order_id' => $order->id,
                'payment_id' => $reversal['transaction_detail']['biller_reference'] ?? null,
                'payment_method' => 'iconpay',
                'trx_reference' => $reversal['transaction_detail']['transaction_id'] ?? null,
                'payment_detail' => json_encode(['create' => null, 'confirm' => $reversal['transaction_detail']]),
                'payment_scenario' => $order->payment->payment_scenario ?? null,
                'amount' => $reversal['transaction_detail']['amount'] ?? null,
                'fee_agent' => $order->fee_agent ?? null,
                'fee_iconpay' => $reversal['transaction_detail']['total_fee'] ?? null,
                'total_fee' => $reversal['transaction_detail']['total_fee'] + $order->fee_agent ?? null,
                'total_amount' => $reversal['transaction_detail']['total_amount'] + $order->fee_agent ?? null,
                'created_by' => 'system',
            ]);

            $data['status'] = 'success';
            $data['message'] = 'Berhasil melakukan late reversal - ' . $reversal['response_code'];
            return $data;
            // return [
            //     'status' => 'success',
            //     'message' => 'Berhasil melakukan late reversal - ' . $reversal['response_code'],
            // ];
        }

        //Invalid bank in cycle -> Set Paid/Success
        if ($reversal['response_code'] == '0097') {
            static::updateAgentOrderStatus($order->id, '04', $reversal['response_message']);

            AgentPayment::create([
                'agent_order_id' => $order->id,
                'payment_id' => $reversal['transaction_detail']['biller_reference'] ?? null,
                'payment_method' => 'iconpay',
                'trx_reference' => $reversal['transaction_detail']['transaction_id'] ?? null,
                'payment_detail' => json_encode(['create' => null, 'confirm' => $reversal['transaction_detail']]),
                'amount' => $reversal['transaction_detail']['amount'] ?? null,
                'fee_agent' => $order->fee_agent ?? null,
                'fee_iconpay' => $reversal['transaction_detail']['total_fee'] ?? null,
                'total_fee' => $reversal['transaction_detail']['total_fee'] + $order->fee_agent ?? null,
                'total_amount' => $reversal['transaction_detail']['total_amount'] + $order->fee_agent ?? null,
                'created_by' => 'system',
            ]);

            $data['status'] = 'success';
            $data['message'] = 'Berhasil melakukan late reversal - ' . $reversal['response_code'];
            return $data;
            // return [
            //     'status' => 'success',
            //     'message' => 'Berhasil melakukan late reversal - ' . $reversal['response_code'],
            // ];
        }

        //Reversal pending or timeout 20s -> Repeat Reversal
        if ($reversal['response_code'] == '5002' || $reversal['response_code'] == '5010' || $reversal['response_code'] == '5016' || $reversal['response_code'] == '408' || $execution_time >= 20) {
            $start_time = microtime(true);
            $repeat_reversal = AgentManager::reversalPostpaidV3(array_merge($order->toArray(), [
                'client_ref' => $order->payment->trx_reference,
                'type' => '02',
            ]));
            if (in_array($repeat_reversal['response_code'], ['5002', '5010', '5016', '408'])) {
                sleep(20);
                Log::info('Repeat Reversal Postpaid Delay 20s RC5002/5010/5016/408, Retry 2nd');
            } else {
                sleep($second_reversal_delay);
            }
            $end_time = microtime(true);
            $execution_time = ($end_time - $start_time);

            Log::info('Repeat Reversal Started');

            //Reversal has been taken -> Set Reversal (refund)
            if ($repeat_reversal['response_code'] == '0094' && $repeat_reversal['transaction_detail'] == null && $execution_time <= 20) {
                static::updateAgentOrderStatus($order->id, '05', $repeat_reversal['response_message'] == 'Success' ? 'TRANSAKSI GAGAL' : $repeat_reversal['response_message']);
                IconcashCommands::orderRefund($order->trx_no, $token, $client_ref, $source_account_id);

                $data['status'] = 'success';
                $data['message'] = 'Berhasil melakukan repeat reversal - ' . $repeat_reversal['response_code'];
                return $data;
            }

            //Reversal Payment Not Found -> Set Failed
            if ($repeat_reversal['response_code'] == '0063' || $repeat_reversal['response_code'] == '0068' && $repeat_reversal['transaction_detail'] == null) {
                static::updateAgentOrderStatus($order->id, '08', $repeat_reversal['response_message']);
                IconcashCommands::orderRefund($order->trx_no, $token, $client_ref, $source_account_id);

                $data['status'] = 'success';
                $data['message'] = 'Transaksi tidak ditemukan / gagal - ' . $repeat_reversal['response_code'];
                return $data;
            }

            //Reversal Cancelled -> Set Failed
            if ($repeat_reversal['response_code'] == '5001' && $repeat_reversal['transaction_detail'] == null) {
                static::updateAgentOrderStatus($order->id, '08', $repeat_reversal['response_message']);
                IconcashCommands::orderRefund($order->trx_no, $token, $client_ref, $source_account_id);

                $data['status'] = 'success';
                $data['message'] = 'Transaksi dibatalkan - ' . $repeat_reversal['response_code'];
                return $data;
            }

            //Late Reversal -> Set Paid/Success
            if ($repeat_reversal['response_code'] == '0012') {
                static::updateAgentOrderStatus($order->id, '04', $repeat_reversal['response_message']);

                AgentPayment::create([
                    'agent_order_id' => $order->id,
                    'payment_id' => $repeat_reversal['transaction_detail']['biller_reference'] ?? null,
                    'payment_method' => 'iconpay',
                    'trx_reference' => $repeat_reversal['transaction_detail']['transaction_id'] ?? null,
                    'payment_detail' => json_encode(['create' => null, 'confirm' => $repeat_reversal['transaction_detail']]),
                    'amount' => $repeat_reversal['transaction_detail']['amount'] ?? null,
                    'fee_agent' => $order->fee_agent ?? null,
                    'fee_iconpay' => $repeat_reversal['transaction_detail']['total_fee'] ?? null,
                    'total_fee' => $repeat_reversal['transaction_detail']['total_fee'] + $order->fee_agent ?? null,
                    'total_amount' => $repeat_reversal['transaction_detail']['total_amount'] + $order->fee_agent ?? null,
                    'created_by' => 'system',
                ]);

                $data['status'] = 'success';
                $data['message'] = 'Berhasil melakukan late reversal - ' . $repeat_reversal['response_code'];
                return $data;
                // return [
                //     'status' => 'success',
                //     'message' => 'Berhasil melakukan pembayaran - ' . $repeat_reversal['response_code'],
                // ];
            }

            //Invalid bank in cycle -> Set Paid/Success
            if ($repeat_reversal['response_code'] == '0097') {
                static::updateAgentOrderStatus($order->id, '04', $repeat_reversal['response_message']);

                AgentPayment::create([
                    'agent_order_id' => $order->id,
                    'payment_id' => $repeat_reversal['transaction_detail']['biller_reference'] ?? null,
                    'payment_method' => 'iconpay',
                    'trx_reference' => $repeat_reversal['transaction_detail']['transaction_id'] ?? null,
                    'payment_detail' => json_encode(['create' => null, 'confirm' => $repeat_reversal['transaction_detail']]),
                    'amount' => $repeat_reversal['transaction_detail']['amount'] ?? null,
                    'fee_agent' => $order->fee_agent ?? null,
                    'fee_iconpay' => $repeat_reversal['transaction_detail']['total_fee'] ?? null,
                    'total_fee' => $repeat_reversal['transaction_detail']['total_fee'] + $order->fee_agent ?? null,
                    'total_amount' => $repeat_reversal['transaction_detail']['total_amount'] + $order->fee_agent ?? null,
                    'created_by' => 'system',
                ]);

                $data['status'] = 'success';
                $data['message'] = 'Berhasil melakukan pembayaran - ' . $repeat_reversal['response_code'];
                return $data;
                // return [
                //     'status' => 'success',
                //     'message' => 'Berhasil proses pembayaran - ' . $repeat_reversal['response_code'],
                // ];
            }

            //Repeat Reversal pending or timeout -> 2nd Repeat Reversal
            if ($repeat_reversal['response_code'] == '5002' || $repeat_reversal['response_code'] == '5010' || $repeat_reversal['response_code'] == '5016' || $repeat_reversal['response_code'] == '408' || $execution_time >= 20) {
                $start_time = microtime(true);
                $second_repeat_reversal = AgentManager::reversalPostpaidV3(array_merge($order->toArray(), [
                    'client_ref' => $order->payment->trx_reference,
                    'type' => '02',
                ]));
                if (in_array($second_repeat_reversal['response_code'], ['5002', '5010', '5016', '408'])) {
                    sleep(20);
                    Log::info('Repeat Reversal Postpaid Delay 20s RC5002/5010/5016/408, Retry 2nd');
                } else {
                    sleep($third_reversal_delay);
                }
                $end_time = microtime(true);
                $execution_time = ($end_time - $start_time);

                Log::info('2nd Repeat Reversal Started');

                //Reversal has been taken -> Set Reversal (refund)
                if ($second_repeat_reversal['response_code'] == '0094' && $second_repeat_reversal['transaction_detail'] == null && $execution_time <= 20) {
                    static::updateAgentOrderStatus($order->id, '05', $second_repeat_reversal['response_message'] == 'Success' ? 'TRANSAKSI GAGAL' : $second_repeat_reversal['response_message']);
                    IconcashCommands::orderRefund($order->trx_no, $token, $client_ref, $source_account_id);

                    $data['status'] = 'success';
                    $data['message'] = 'Berhasil melakukan repeat reversal - ' . $second_repeat_reversal['response_code'];
                    return $data;
                }

                //Reversal Payment Not Found -> Set Failed
                if ($second_repeat_reversal['response_code'] == '0063' || $second_repeat_reversal['response_code'] == '0068' && $second_repeat_reversal['transaction_detail'] == null) {
                    static::updateAgentOrderStatus($order->id, '08', $second_repeat_reversal['response_message']);
                    IconcashCommands::orderRefund($order->trx_no, $token, $client_ref, $source_account_id);

                    $data['status'] = 'success';
                    $data['message'] = 'Transaksi tidak ditemukan / gagal - ' . $second_repeat_reversal['response_code'];
                    return $data;
                }

                //Reversal Cancelled -> Set Failed
                if ($second_repeat_reversal['response_code'] == '5001' && $second_repeat_reversal['transaction_detail'] == null) {
                    static::updateAgentOrderStatus($order->id, '08', $second_repeat_reversal['response_message']);
                    IconcashCommands::orderRefund($order->trx_no, $token, $client_ref, $source_account_id);

                    $data['status'] = 'success';
                    $data['message'] = 'Transaksi dibatalkan - ' . $second_repeat_reversal['response_code'];
                    return $data;
                }

                //Late Reversal -> Set Paid/Success
                if ($second_repeat_reversal['response_code'] == '0012') {
                    static::updateAgentOrderStatus($order->id, '04', $second_repeat_reversal['response_message']);

                    AgentPayment::create([
                        'agent_order_id' => $order->id,
                        'payment_id' => $second_repeat_reversal['transaction_detail']['biller_reference'] ?? null,
                        'payment_method' => 'iconpay',
                        'trx_reference' => $second_repeat_reversal['transaction_detail']['transaction_id'] ?? null,
                        'payment_detail' => json_encode(['create' => null, 'confirm' => $second_repeat_reversal['transaction_detail']]),
                        'amount' => $second_repeat_reversal['transaction_detail']['amount'] ?? null,
                        'fee_agent' => $order->fee_agent ?? null,
                        'fee_iconpay' => $second_repeat_reversal['transaction_detail']['total_fee'] ?? null,
                        'total_fee' => $second_repeat_reversal['transaction_detail']['total_fee'] + $order->fee_agent ?? null,
                        'total_amount' => $second_repeat_reversal['transaction_detail']['total_amount'] + $order->fee_agent ?? null,
                        'created_by' => 'system',
                    ]);

                    $data['status'] = 'success';
                    $data['message'] = 'Berhasil melakukan late reversal - ' . $second_repeat_reversal['response_code'];
                    return $data;
                }

                //Invalid bank in cycle -> Set Paid/Success
                if ($second_repeat_reversal['response_code'] == '0097') {
                    static::updateAgentOrderStatus($order->id, '04', $second_repeat_reversal['response_message']);

                    AgentPayment::create([
                        'agent_order_id' => $order->id,
                        'payment_id' => $second_repeat_reversal['transaction_detail']['biller_reference'] ?? null,
                        'payment_method' => 'iconpay',
                        'trx_reference' => $second_repeat_reversal['transaction_detail']['transaction_id'] ?? null,
                        'payment_detail' => json_encode(['create' => null, 'confirm' => $second_repeat_reversal['transaction_detail']]),
                        'amount' => $second_repeat_reversal['transaction_detail']['amount'] ?? null,
                        'fee_agent' => $order->fee_agent ?? null,
                        'fee_iconpay' => $second_repeat_reversal['transaction_detail']['total_fee'] ?? null,
                        'total_fee' => $second_repeat_reversal['transaction_detail']['total_fee'] + $order->fee_agent ?? null,
                        'total_amount' => $second_repeat_reversal['transaction_detail']['total_amount'] + $order->fee_agent ?? null,
                        'created_by' => 'system',
                    ]);

                    $data['status'] = 'success';
                    $data['message'] = 'Berhasil melakukan pembayaran - ' . $second_repeat_reversal['response_code'];
                    return $data;
                }

                //Repeat Reversal pending or timeout -> 3rd Repeat Reversal
                if ($second_repeat_reversal['response_code'] == '5002' || $second_repeat_reversal['response_code'] == '5010' || $second_repeat_reversal['response_code'] == '5016' || $second_repeat_reversal['response_code'] == '408' || $execution_time >= 20) {
                    $start_time = microtime(true);
                    $third_repeat_reversal = AgentManager::reversalPostpaidV3(array_merge($order->toArray(), [
                        'client_ref' => $order->payment->trx_reference,
                        'type' => '02',
                    ]));
                    if (in_array($third_repeat_reversal['response_code'], ['5002', '5010', '5016', '408'])) {
                        sleep(20);
                        Log::info('Repeat Reversal Postpaid Delay 20s RC5002/5010/5016/408, Retry 2nd');
                    } else {
                        sleep($fourth_reversal_delay);
                    }
                    $end_time = microtime(true);
                    $execution_time = ($end_time - $start_time);

                    Log:info('3rd Repeat Reversal Started');

                    //Reversal has been taken -> Set Reversal (refund)
                    if ($third_repeat_reversal['response_code'] == '0094' && $third_repeat_reversal['transaction_detail'] == null && $execution_time <= 20) {
                        static::updateAgentOrderStatus($order->id, '05', $third_repeat_reversal['response_message'] == 'Success' ? 'TRANSAKSI GAGAL' : $third_repeat_reversal['response_message']);
                        IconcashCommands::orderRefund($order->trx_no, $token, $client_ref, $source_account_id);

                        $data['status'] = 'success';
                        $data['message'] = 'Berhasil melakukan repeat reversal - ' . $third_repeat_reversal['response_code'];
                        return $data;
                    }

                    //Reversal Payment Not Found -> Set Failed
                    if ($third_repeat_reversal['response_code'] == '0063' || $third_repeat_reversal['response_code'] == '0068' && $third_repeat_reversal['transaction_detail'] == null) {
                        static::updateAgentOrderStatus($order->id, '08', $third_repeat_reversal['response_message']);
                        IconcashCommands::orderRefund($order->trx_no, $token, $client_ref, $source_account_id);

                        $data['status'] = 'success';
                        $data['message'] = 'Transaksi tidak ditemukan / gagal - ' . $third_repeat_reversal['response_code'];
                        return $data;
                    }

                    //Reversal Cancelled -> Set Failed
                    if ($third_repeat_reversal['response_code'] == '5001' && $third_repeat_reversal['transaction_detail'] == null) {
                        static::updateAgentOrderStatus($order->id, '08', $third_repeat_reversal['response_message']);
                        IconcashCommands::orderRefund($order->trx_no, $token, $client_ref, $source_account_id);

                        $data['status'] = 'success';
                        $data['message'] = 'Transaksi dibatalkan - ' . $third_repeat_reversal['response_code'];
                        return $data;
                    }

                    //Late Reversal -> Set Paid/Success
                    if ($third_repeat_reversal['response_code'] == '0012') {
                        static::updateAgentOrderStatus($order->id, '04', $third_repeat_reversal['response_message']);

                        AgentPayment::create([
                            'agent_order_id' => $order->id,
                            'payment_id' => $third_repeat_reversal['transaction_detail']['biller_reference'] ?? null,
                            'payment_method' => 'iconpay',
                            'trx_reference' => $third_repeat_reversal['transaction_detail']['transaction_id'] ?? null,
                            'payment_detail' => json_encode(['create' => null, 'confirm' => $third_repeat_reversal['transaction_detail']]),
                            'amount' => $third_repeat_reversal['transaction_detail']['amount'] ?? null,
                            'fee_agent' => $order->fee_agent ?? null,
                            'fee_iconpay' => $third_repeat_reversal['transaction_detail']['total_fee'] ?? null,
                            'total_fee' => $third_repeat_reversal['transaction_detail']['total_fee'] + $order->fee_agent ?? null,
                            'total_amount' => $third_repeat_reversal['transaction_detail']['total_amount'] + $order->fee_agent ?? null,
                            'created_by' => 'system',
                        ]);

                        $data['status'] = 'success';
                        $data['message'] = 'Berhasil melakukan late reversal - ' . $third_repeat_reversal['response_code'];
                        return $data;
                    }

                    //Invalid bank in cycle -> Set Paid/Success
                    if ($third_repeat_reversal['response_code'] == '0097') {
                        static::updateAgentOrderStatus($order->id, '04', $third_repeat_reversal['response_message']);

                        AgentPayment::create([
                            'agent_order_id' => $order->id,
                            'payment_id' => $third_repeat_reversal['transaction_detail']['biller_reference'] ?? null,
                            'payment_method' => 'iconpay',
                            'trx_reference' => $third_repeat_reversal['transaction_detail']['transaction_id'] ?? null,
                            'payment_detail' => json_encode(['create' => null, 'confirm' => $third_repeat_reversal['transaction_detail']]),
                            'amount' => $third_repeat_reversal['transaction_detail']['amount'] ?? null,
                            'fee_agent' => $order->fee_agent ?? null,
                            'fee_iconpay' => $third_repeat_reversal['transaction_detail']['total_fee'] ?? null,
                            'total_fee' => $third_repeat_reversal['transaction_detail']['total_fee'] + $order->fee_agent ?? null,
                            'total_amount' => $third_repeat_reversal['transaction_detail']['total_amount'] + $order->fee_agent ?? null,
                            'created_by' => 'system',
                        ]);

                        $data['status'] = 'success';
                        $data['message'] = 'Berhasil melakukan pembayaran - ' . $third_repeat_reversal['response_code'];
                        return $data;
                    }

                    //Repeat Reversal pending or timeout -> 4th Repeat Reversal
                    if ($third_repeat_reversal['response_code'] == '5002' || $third_repeat_reversal['response_code'] == '5010' || $third_repeat_reversal['response_code'] == '5016' || $third_repeat_reversal['response_code'] == '408' || $execution_time >= 20) {
                        $start_time = microtime(true);
                        $fourth_repeat_reversal = AgentManager::reversalPostpaidV3(array_merge($order->toArray(), [
                            'client_ref' => $order->payment->trx_reference,
                            'type' => '02',
                        ]));
                        if (in_array($fourth_repeat_reversal['response_code'], ['5002', '5010', '5016', '408'])) {
                            sleep(20);
                            Log::info('Repeat Reversal Postpaid Delay 20s RC5002/5010/5016/408, Retry 2nd');
                        } else {
                            sleep($manual_reversal_delay);
                        }
                        $end_time = microtime(true);
                        $execution_time = ($end_time - $start_time);

                        Log::info('4th Repeat Reversal Started');

                        //Reversal has been taken -> Set Reversal (refund)
                        if ($fourth_repeat_reversal['response_code'] == '0094' && $fourth_repeat_reversal['transaction_detail'] == null && $execution_time <= 20) {
                            static::updateAgentOrderStatus($order->id, '05', $fourth_repeat_reversal['response_message'] == 'Success' ? 'TRANSAKSI GAGAL' : $fourth_repeat_reversal['response_message']);
                            IconcashCommands::orderRefund($order->trx_no, $token, $client_ref, $source_account_id);

                            $data['status'] = 'success';
                            $data['message'] = 'Berhasil melakukan repeat reversal - ' . $fourth_repeat_reversal['response_code'];
                            return $data;
                        }

                        //Reversal Payment Not Found -> Set Failed
                        if ($fourth_repeat_reversal['response_code'] == '0063' || $fourth_repeat_reversal['response_code'] == '0068' && $fourth_repeat_reversal['transaction_detail'] == null) {
                            static::updateAgentOrderStatus($order->id, '08', $fourth_repeat_reversal['response_message']);
                            IconcashCommands::orderRefund($order->trx_no, $token, $client_ref, $source_account_id);

                            $data['status'] = 'success';
                            $data['message'] = 'Transaksi tidak ditemukan / gagal - ' . $fourth_repeat_reversal['response_code'];
                            return $data;
                        }

                        //Reversal Cancelled -> Set Failed
                        if ($fourth_repeat_reversal['response_code'] == '5001' && $fourth_repeat_reversal['transaction_detail'] == null) {
                            static::updateAgentOrderStatus($order->id, '08', $fourth_repeat_reversal['response_message']);
                            IconcashCommands::orderRefund($order->trx_no, $token, $client_ref, $source_account_id);

                            $data['status'] = 'success';
                            $data['message'] = 'Transaksi dibatalkan - ' . $fourth_repeat_reversal['response_code'];
                            return $data;
                        }

                        //Late Reversal -> Set Paid/Success
                        if ($fourth_repeat_reversal['response_code'] == '0012') {
                            static::updateAgentOrderStatus($order->id, '04', $fourth_repeat_reversal['response_message']);

                            AgentPayment::create([
                                'agent_order_id' => $order->id,
                                'payment_id' => $fourth_repeat_reversal['transaction_detail']['biller_reference'] ?? null,
                                'payment_method' => 'iconpay',
                                'trx_reference' => $fourth_repeat_reversal['transaction_detail']['transaction_id'] ?? null,
                                'payment_detail' => json_encode(['create' => null, 'confirm' => $fourth_repeat_reversal['transaction_detail']]),
                                'amount' => $fourth_repeat_reversal['transaction_detail']['amount'] ?? null,
                                'fee_agent' => $order->fee_agent ?? null,
                                'fee_iconpay' => $fourth_repeat_reversal['transaction_detail']['total_fee'] ?? null,
                                'total_fee' => $fourth_repeat_reversal['transaction_detail']['total_fee'] + $order->fee_agent ?? null,
                                'total_amount' => $fourth_repeat_reversal['transaction_detail']['total_amount'] + $order->fee_agent ?? null,
                                'created_by' => 'system',
                            ]);

                            $data['status'] = 'success';
                            $data['message'] = 'Berhasil melakukan late reversal - ' . $fourth_repeat_reversal['response_code'];
                            return $data;
                        }

                        //Invalid bank in cycle -> Set Paid/Success
                        if ($fourth_repeat_reversal['response_code'] == '0097') {
                            static::updateAgentOrderStatus($order->id, '04', $fourth_repeat_reversal['response_message']);

                            AgentPayment::create([
                                'agent_order_id' => $order->id,
                                'payment_id' => $fourth_repeat_reversal['transaction_detail']['biller_reference'] ?? null,
                                'payment_method' => 'iconpay',
                                'trx_reference' => $fourth_repeat_reversal['transaction_detail']['transaction_id'] ?? null,
                                'payment_detail' => json_encode(['create' => null, 'confirm' => $fourth_repeat_reversal['transaction_detail']]),
                                'amount' => $fourth_repeat_reversal['transaction_detail']['amount'] ?? null,
                                'fee_agent' => $order->fee_agent ?? null,
                                'fee_iconpay' => $fourth_repeat_reversal['transaction_detail']['total_fee'] ?? null,
                                'total_fee' => $fourth_repeat_reversal['transaction_detail']['total_fee'] + $order->fee_agent ?? null,
                                'total_amount' => $fourth_repeat_reversal['transaction_detail']['total_amount'] + $order->fee_agent ?? null,
                                'created_by' => 'system',
                            ]);

                            $data['status'] = 'success';
                            $data['message'] = 'Berhasil melakukan pembayaran - ' . $fourth_repeat_reversal['response_code'];
                            return $data;
                        }

                        if ($fourth_repeat_reversal['response_code'] == '5002' || $fourth_repeat_reversal['response_code'] == '5010' || $fourth_repeat_reversal['response_code'] == '5016' || $fourth_repeat_reversal['response_code'] == '408' || $execution_time >= 20) {
                            static::updateAgentOrderStatus($order->id, '09', $fourth_repeat_reversal['response_message']);

                            $data['status'] = 'success';
                            $data['message'] = 'Gagal melakukan reversal, transaksi dalam status pending - ' . $fourth_repeat_reversal['response_code'];
                            return $data;
                        }

                        if ($fourth_repeat_reversal['response_code'] == '0000' && $execution_time <= 20 && $fourth_repeat_reversal['transaction_detail'] != null) {
                            static::updateAgentOrderStatus($order->id, '05', $fourth_repeat_reversal['response_message'] == 'Success' ? 'TRANSAKSI GAGAL' : $fourth_repeat_reversal['response_message']);
                            IconcashCommands::orderRefund($order->trx_no, $token, $client_ref, $source_account_id);

                            $data['status'] = 'success';
                            $data['message'] = 'Berhasil melakukan repeat reversal - ' . $fourth_repeat_reversal['response_code'];
                            return $data;
                        }
                    }

                    if ($third_repeat_reversal['response_code'] == '0000' && $execution_time <= 20 && $third_repeat_reversal['transaction_detail'] != null) {
                        static::updateAgentOrderStatus($order->id, '05', $third_repeat_reversal['response_message'] == 'Success' ? 'TRANSAKSI GAGAL' : $third_repeat_reversal['response_message']);
                        IconcashCommands::orderRefund($order->trx_no, $token, $client_ref, $source_account_id);

                        $data['status'] = 'success';
                        $data['message'] = 'Berhasil melakukan repeat reversal - ' . $third_repeat_reversal['response_code'];
                        return $data;
                    }
                }

                if ($second_repeat_reversal['response_code'] == '0000' && $execution_time <= 20 && $second_repeat_reversal['transaction_detail'] != null) {
                    static::updateAgentOrderStatus($order->id, '05', $second_repeat_reversal['response_message'] == 'Success' ? 'TRANSAKSI GAGAL' : $second_repeat_reversal['response_message']);
                    IconcashCommands::orderRefund($order->trx_no, $token, $client_ref, $source_account_id);

                    $data['status'] = 'success';
                    $data['message'] = 'Berhasil melakukan repeat reversal - ' . $second_repeat_reversal['response_code'];
                    return $data;
                }
            }

            //Repeat Reversal Success -> refund iconcash
            if ($repeat_reversal['response_code'] == '0000' && $execution_time <= 20 && $repeat_reversal['transaction_detail'] != null) {
                static::updateAgentOrderStatus($order->id, '05', $repeat_reversal['response_message'] == 'Success' ? 'TRANSAKSI GAGAL' : $repeat_reversal['response_message']);
                IconcashCommands::orderRefund($order->trx_no, $token, $client_ref, $source_account_id);

                $data['status'] = 'success';
                $data['message'] = 'Berhasil melakukan repeat reversal - ' . $repeat_reversal['response_code'];
                return $data;
            }
        }

        //Reversal Success -> refund iconcash
        if ($reversal['response_code'] == '0000' && $execution_time <= 20 && $reversal['transaction_detail'] != null) {
            static::updateAgentOrderStatus($order->id, '05', $reversal['response_message'] == 'Success' ? 'TRANSAKSI GAGAL' : $reversal['response_message']);
            IconcashCommands::orderRefund($order->trx_no, $token, $client_ref, $source_account_id);

            $data['status'] = 'success';
            $data['message'] = 'Berhasil melakukan reversal - ' . $reversal['response_code'];
            return $data;
        }

        return $reversal;
    }

    public function manualReversalPostpaid($request, $client_ref, $source_account_id)
    {
        $order = AgentOrder::with([
            'progress_active',
            'payment' => fn($q) => $q->where('payment_method', 'iconcash'),
        ])
            ->where('trx_no', $request['transaction_id'])
            ->where('product_id', 'POSTPAID')
            ->first();

        if (empty($order)) {
            return [
                'status' => 'error',
                'message' => 'Order tidak ditemukan',
            ];
        }

        if ($order->progress_active->status_code != '09') {
            return [
                'status' => 'error',
                'message' => 'Gagal melakukan manual reversal, transaksi tidak dalam status pending',
            ];
        }

        $iconcash = Auth::user()->iconcash;

        $manual_reversal = $this->reversalPostpaid($order, null, null, null, null, null, $iconcash->token, $client_ref, $source_account_id);

        return $manual_reversal;
    }

    public static function updateAgentOrderStatus($agent_order_id, $status_code, $status_note)
    {
        AgentOrderProgres::where('agent_order_id', $agent_order_id)->update([
            'status' => 0,
            'updated_by' => 'system',
        ]);

        AgentOrderProgres::create([
            'agent_order_id' => $agent_order_id,
            'status_code' => $status_code,
            'status_note' => $status_note,
            'status_name' => static::$status_agent_order[$status_code],
            'created_by' => 'system',
        ]);

        return true;
    }

    public function downloadAgentReceipt($trx_no)
    {
        $merchant = Merchant::where('id', Auth::user()->merchant_id)->first();

        $order = AgentOrder::with([
            'progress_active',
            'payment' => fn($q) => $q->where('payment_method', 'iconpay'),
        ])->where('trx_no', $trx_no)->first();

        if (empty($order)) {
            throw new Exception('Order tidak ditemukan', 500);
        }

        $payment_detail = json_decode($order->payment->payment_detail, true);

        // if ($order->has_receipt == true) {
        //     $invoice_number = 'INV/' . $order->product_id . '/' . date('Ymd') . '/' . $order->id . '/' . 'COPY';
        // } else {
        //     $invoice_number = 'INV/' . $order->product_id . '/' . date('Ymd') . '/' . $order->id . '/' . 'ORI';
        // }
        $invoice_number = $order->invoice_no . '/COPY';

        $stand_awal = $payment_detail['confirm']['item_detail'][0]['stand_awal'];
        $stand_akhir = $payment_detail['confirm']['item_detail'][count($payment_detail['confirm']['item_detail']) - 1]['stand_akhir'];
        $partner_reference = $payment_detail['confirm']['partner_reference'];

        $stand_formatted = str_pad($stand_awal, 8, '0', STR_PAD_LEFT) . '-' . str_pad($stand_akhir, 8, '0', STR_PAD_LEFT);

        if ($order->product_id == 'POSTPAID') {
            $blth_list = '';
            foreach ($payment_detail['confirm']['item_detail'] as $item) {
                $idpel = $item['idpel'];
                $no_meter = $item['no_meter'];
                $pengesahan = $item['pengesahan'];
                $tagline = $item['tagline'];
                $info_tagihan = $item['info_tagihan'];
                $stand_meter = $stand_formatted;
                $thbl = $item['thbl'];
                $thbl = substr($thbl, 4, 2) . '/' . substr($thbl, 0, 4);
                $blth_list .= $thbl . ', ';
            }
            $blth_list = rtrim($blth_list, ', ');
            $blth_list = $this->blthDateFormat($blth_list);

            $data = Pdf::loadView('agent.postpaid-receipt', [
                'idpel' => $idpel,
                'no_meter' => $no_meter,
                'invoice_number' => $invoice_number,
                'blth_list' => $blth_list,
                'pengesahan' => $pengesahan,
                'tagline' => $tagline,
                'info_tagihan' => $info_tagihan,
                'stand_meter' => $stand_meter,
                'partner_reference' => $partner_reference,
                'payment_detail' => $payment_detail,
                'order' => $order,
                'merchant' => $merchant,
            ])->setPaper('a4', 'potrait');

            // $order->update([
            //     'has_receipt' => true
            // ]);

            return response($data->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="receipt-' . $order->invoice_no . '.pdf"');
            // $pdf = $data->setPaper('a4', 'potrait')->download('receipt-' . $order->invoice_no . '.pdf');
        } else if ($order->product_id == 'PREPAID') {
            foreach ($payment_detail['confirm']['item_detail'] as $item) {
                $pengesahan = $item['pengesahan'];
                $tagline = $item['tagline'];
                $info_tagihan = $item['info_tagihan'];
                $idpel = $item['idpel'];
                $no_meter = $item['no_meter'];
                $rptotal = $item['rptotal'];
                $meterai = str_replace(',', '.', $item['meterai']);
                $rpppn = str_replace(',', '.', $item['rpppn']);
                $rpppj = str_replace(',', '.', $item['rpppj']);
                $angsuran = str_replace(',', '.', $item['angsuran']);
                $rp_stroom = str_replace(',', '.', $item['rp_stroom']);
                $jml_kwh = str_replace(',', '.', $item['jml_kwh']);
                $stroom_token = $item['token'];
            }

            $data = Pdf::loadView('agent.prepaid-receipt', [
                'invoice_number' => $invoice_number,
                'pengesahan' => $pengesahan,
                'tagline' => $tagline,
                'info_tagihan' => $info_tagihan,
                'payment_detail' => $payment_detail,
                'order' => $order,
                'merchant' => $merchant,
                'idpel' => $idpel,
                'no_meter' => $no_meter,
                'rptotal' => $rptotal,
                'meterai' => $meterai,
                'rpppn' => $rpppn,
                'rpppj' => $rpppj,
                'angsuran' => $angsuran,
                'rp_stroom' => $rp_stroom,
                'jml_kwh' => $jml_kwh,
                'stroom_token' => $stroom_token,
                'partner_reference' => $partner_reference,
            ])->setPaper('a4', 'potrait');

            return response($data->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="receipt-' . $order->invoice_no . '.pdf"');

            // $pdf = $data->setPaper('a4', 'potrait')->download('receipt-' . $order->invoice_no . '.pdf');
        }

        // return $pdf;
    }

    public function blthDateFormat($blth)
    {
        $date = $blth;
        $date_array = explode(',', $date);
        $new_date_array = array();
        setlocale(LC_TIME, 'id_ID');
        foreach ($date_array as $date) {
            $date = trim($date);
            $date_obj = \Carbon\Carbon::createFromFormat('m/Y', $date)->locale('id');
            $new_date = $date_obj->isoFormat('MMMYY');
            $new_date_array[] = $new_date;
        }
        $new_date_string = strtoupper(implode(', ', $new_date_array));

        return $new_date_string;
    }

    public static function invoice_num($input, $pad_len = 3, $prefix = null)
    {
        if ($pad_len <= strlen($input)) {
            $pad_len++;
        }

        if (is_string($prefix)) {
            return sprintf("%s%s", $prefix, str_pad($input, $pad_len, "0", STR_PAD_LEFT));
        }

        return str_pad($input, $pad_len, "0", STR_PAD_LEFT);
    }
}

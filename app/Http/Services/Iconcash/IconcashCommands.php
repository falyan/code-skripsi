<?php

namespace App\Http\Services\Iconcash;

use App\Http\Services\Manager\IconcashManager;
use App\Http\Services\Service;
use App\Models\AgentOrder;
use App\Models\AgentOrderProgres;
use App\Models\AgentPayment;
use App\Models\IconcashCredential;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class IconcashCommands extends Service
{
    public static function register($user)
    {
        try {
            DB::beginTransaction();
            IconcashCredential::create([
                'customer_id' => $user->id,
                'phone' => $user->phone,
                'key' => 'authorization',
                'token' => null,
                'status' => 'Requested',
            ]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public static function login($user, $data)
    {
        try {
            DB::beginTransaction();
            IconcashCredential::where('id', $user->iconcash->id)
                ->where('customer_id', $user->id)
                ->update([
                    'status' => 'Activated',
                    'token' => $data->token,
                    'iconcash_username' => $data->username,
                    'iconcash_session_id' => $data->sessionId,
                    'iconcash_customer_id' => $data->customerId,
                    'iconcash_customer_name' => $data->customerName,
                ]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public static function logout($user)
    {
        try {
            DB::beginTransaction();
            IconcashCredential::where('id', $user->iconcash->id)
                ->where('customer_id', $user->id)
                ->update([
                    'status' => 'Inactivated',
                    'token' => null,
                ]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public static function createOrder($request, $token)
    {
        $order = AgentOrder::with(['progress_active'])->where('trx_no', $request['transaction_id'])->first();

        if (empty($order)) {
            return [
                'status' => 'error',
                'message' => 'Order not found',
            ];
        }

        // if ($order->progress_active->status_code != '00') {
        //     return [
        //         'status'    => 'error',
        //         'message'   => 'Sedang dalam proses pembayaran',
        //     ];
        // }

        $data = [
            'kode_konter' => ($order->total_fee + $order->margin) > 0 ? '0' : '1',
            'kode_product' => $order->product_id == 'PREPAID' ? 1 : 2,
            // 'kode_gateway' => static::generateGateway($order->merchant_id), // max 17.576
            'kode_gateway' => env('ICONPAY_V3_AGENT_GATEWAY_CODE') ? env('ICONPAY_V3_AGENT_GATEWAY_CODE') : static::generateGateway($order->merchant_id), // max 17.576
            'buying_options' => 1,
            'transaction_id' => $order->trx_no,
        ];

        try {
            DB::beginTransaction();
            $createOrder = IconcashManager::paymentProcess($data, $token, 0, $order->total_amount, $order->total_amount);

            if ($createOrder['success'] && $createOrder['data'] != null) {
                AgentOrderProgres::where('agent_order_id', $order->id)->update([
                    'status' => 0,
                    'updated_by' => Auth::user()->id,
                ]);

                AgentOrderProgres::create([
                    'agent_order_id' => $order->id,
                    'status_code' => '01',
                    'status_name' => static::$status_agent_order['01'],
                    'created_by' => Auth::user()->id,
                ]);

                AgentPayment::create([
                    'agent_order_id' => $order->id,
                    'payment_id' => $createOrder['data']['id'],
                    'payment_method' => 'iconcash',
                    'trx_reference' => $createOrder['data']['clientRef'],
                    'payment_detail' => json_encode(['create' => $createOrder['data']]),
                    'amount' => $createOrder['data']['amount'],
                    'fee_agent' => $order->fee_agent,
                    'fee_iconpay' => $order->fee_iconpay,
                    'total_fee' => $createOrder['data']['fee'],
                    'total_amount' => $createOrder['data']['amountFee'],
                    'created_by' => Auth::user()->id,
                ]);

                DB::commit();

                return [
                    'status' => 'success',
                    'message' => 'Order created successfully',
                    'data' => [
                        'client_ref' => $createOrder['data']['clientRef'],
                    ],
                ];
            } else {
                return $createOrder;
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public static function orderConfirm($request, $token)
    {
        $order = AgentOrder::with([
            'progress_active',
            'payment' => fn($q) => $q->where('payment_method', 'iconcash')->where('trx_reference', $request['client_ref']),
        ])
            ->where('trx_no', $request['transaction_id'])
            ->first();
        // return $order;

        if (empty($order)) {
            return [
                'status' => 'error',
                'message' => 'Order not found',
            ];
        }

        if ($order->progress_active->status_code != '01') {
            return [
                'status' => 'error',
                'message' => 'Belum melakukan order',
            ];
        }

        try {

            // return AgentManager::paymentIconpayJob($request['transaction_id'], $request['payment_scenario'], $token, $request['client_ref'], $request['source_account_id']);
            DB::beginTransaction();
            $confirmOrder = IconcashManager::paymentConfirm($request['account_pin'], $request['source_account_id'], $request['client_ref'], $order->payment->payment_id, $token);

            if ($confirmOrder['success'] && $confirmOrder['data'] != null) {
                AgentOrderProgres::where('agent_order_id', $order->id)->update([
                    'status' => 0,
                    'updated_by' => Auth::user()->id,
                ]);

                AgentOrderProgres::create([
                    'agent_order_id' => $order->id,
                    'status_code' => '03', // sementara sebelum api apt2t
                    'status_name' => static::$status_agent_order['03'],
                    'created_by' => Auth::user()->id,
                ]);

                $payment = AgentPayment::where([
                    'payment_method' => 'iconcash',
                    'trx_reference' => $request['client_ref'],
                ])->first();

                $payment->update([
                    'payment_detail' => json_encode(array_merge(json_decode($payment->payment_detail, true), ['confirm' => $confirmOrder['data']])),
                    'payment_scenario' => $request['payment_scenario'] ?? null,
                    'source_account_id' => $request['source_account_id'] ?? null,
                    'updated_by' => Auth::user()->id,
                ]);

                // return AgentManager::paymentIconpayJob($order, $request['payment_scenario'], $token, $request['client_ref'], $request['source_account_id']);
                //running process async job
                // dispatch(new AgentPaymentIconpayJob($order, $request['payment_scenario'], $token, $request['client_ref'], $request['source_account_id']));
                // AgentManager::paymentIconpayJob($request['transaction_id'], $request['payment_scenario'], $token, $request['client_ref'], $request['source_account_id']);

                // $url = sprintf('%s/%s', env('RADAGAST_AGREGATOR_ENDPOINT'), 'api/payment/iconpay');

                // $client = new \React\Http\Browser();
                // $data = array(
                //     'transaction_id'    => $request['transaction_id'],
                //     'payment_scenario'  => $request['payment_scenario'],
                //     'token'             => $token,
                //     'client_ref'        => $request['client_ref'],
                //     'source_account_id' => $request['source_account_id'],
                // );

                // $client->post($url, array(
                //     'Content-Type' => 'application/json',
                // ), json_encode($data));

                // $client = new Client();
                // $client->postAsync($url, [
                //     'json' => [
                //         'transaction_id'    => $request['transaction_id'],
                //         'payment_scenario'  => $request['payment_scenario'],
                //         'token'             => $token,
                //         'client_ref'        => $request['client_ref'],
                //         'source_account_id' => $request['source_account_id'],
                //     ]
                // ])->then(
                //     function () {
                //         Log::info('success async');
                //     }
                // )->wait();

                // $req = new Request('POST', $url, [
                //     'json' => [
                //         'transaction_id'    => $request['transaction_id'],
                //         'payment_scenario'  => $request['payment_scenario'],
                //         'token'             => $token,
                //         'client_ref'        => $request['client_ref'],
                //         'source_account_id' => $request['source_account_id'],
                //     ]
                // ]);

                // //run in background without waiting for the response (async) and without blocking the current request (non-blocking)
                // $client->sendAsync($req)->then(
                //     function ($response) {
                //         Log::info('success async');
                //     }
                // )->wait();

                DB::commit();
                return [
                    'status' => 'success',
                    // 'data'      => $order->data,
                    'message' => 'Order checked successfully',
                ];
            } else {
                return $confirmOrder;
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public static function orderRefund($trx_no, $token, $client_ref, $source_account_id)
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
                'message' => 'Order not found',
            ];
        }

        // if ($order->progress_active->status_code != '03') {
        //     return [
        //         'status'    => 'error',
        //         'message'   => 'Belum melakukan pembayaran',
        //     ];
        // }

        try {
            DB::beginTransaction();
            $confirmOrder = IconcashManager::paymentRefund($order->payment->trx_reference, $order->payment->payment_id, $token, $source_account_id);

            if ($confirmOrder['success'] && $confirmOrder['data'] != null) {
                AgentPayment::create([
                    'agent_order_id' => $order->id,
                    'payment_id' => $confirmOrder['data']['invoiceId'],
                    'payment_method' => 'iconcash_refund',
                    'trx_reference' => $confirmOrder['data']['clientRef'],
                    'payment_detail' => json_encode(['create' => $confirmOrder['data']]),
                    'amount' => $confirmOrder['data']['amount'],
                    'fee_agent' => $order->fee_agent,
                    'fee_iconpay' => $order->fee_iconpay,
                    'total_fee' => $confirmOrder['data']['fee'],
                    'total_amount' => $confirmOrder['data']['amountFee'],
                    'payment_scenario' => 'refund_success',
                    'created_by' => 'system',
                ]);

                DB::commit();
                return [
                    'status' => 'success',
                    // 'data'      => $order->data,
                    'message' => 'Berhasil melakukan refund',
                ];
            } else if ($confirmOrder['success'] == false && ($confirmOrder['code'] == -4 || $confirmOrder['code'] == -2)) {
                AgentPayment::create([
                    'agent_order_id' => $order->id,
                    'payment_id' => null,
                    'payment_method' => 'iconcash_refund',
                    'trx_reference' => null,
                    'payment_detail' => json_encode(['create' => $confirmOrder['message']]),
                    'amount' => null,
                    'fee_agent' => null,
                    'fee_iconpay' => null,
                    'total_fee' => null,
                    'total_amount' => null,
                    'payment_scenario' => 'refund_anomaly',
                    'created_by' => 'system',
                ]);

                DB::commit();
                return [
                    'status' => 'success',
                    // 'data'      => $order->data,
                    'message' => 'Berhasil melakukan refund',
                ];
            } else {
                AgentPayment::create([
                    'agent_order_id' => $order->id,
                    'payment_id' => null,
                    'payment_method' => 'iconcash_refund',
                    'trx_reference' => null,
                    'payment_detail' => json_encode(['create' => $confirmOrder['message']]),
                    'amount' => null,
                    'fee_agent' => null,
                    'fee_iconpay' => null,
                    'total_fee' => null,
                    'total_amount' => null,
                    'payment_scenario' => 'refund_failed',
                    'created_by' => 'system',
                ]);

                DB::commit();
                return [
                    'status' => 'success',
                    // 'data'      => $order->data,
                    'message' => 'Refund failed',
                ];
            }
        } catch (\Throwable $th) {
            DB::rollBack();

            AgentPayment::create([
                'agent_order_id' => $order->id,
                'payment_id' => null,
                'payment_method' => 'iconcash_refund',
                'trx_reference' => null,
                'payment_detail' => json_encode(['create' => $th->getMessage()]),
                'amount' => null,
                'fee_agent' => null,
                'fee_iconpay' => null,
                'total_fee' => null,
                'total_amount' => null,
                'payment_scenario' => 'refund_failed',
                'created_by' => 'system',
            ]);

            throw $th;
        }
    }

    public static function manualOrderRefund($trx_no, $token, $client_ref, $source_account_id)
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
                'message' => 'Order not found',
            ];
        }

        // if ($order->progress_active->status_code != '03') {
        //     return [
        //         'status'    => 'error',
        //         'message'   => 'Belum melakukan pembayaran',
        //     ];
        // }

        try {
            DB::beginTransaction();
            $confirmOrder = IconcashManager::paymentRefund($order->payment->trx_reference, $order->payment->payment_id, $token, $source_account_id);

            if ($confirmOrder['success'] && $confirmOrder['data'] != null) {
                AgentOrderProgres::where('agent_order_id', $order->id)->update([
                    'status' => 0,
                    'updated_by' => 'system',
                ]);

                AgentOrderProgres::create([
                    'agent_order_id' => $order->id,
                    'status_code' => '08', // sementara sebelum api apt2t
                    'status_name' => static::$status_agent_order['08'],
                    'created_by' => 'system',
                ]);

                $payment = AgentPayment::where([
                    'trx_reference' => $client_ref,
                    'payment_method' => 'iconcash',
                ])->first();

                $payment->update([
                    'payment_detail' => json_encode(array_merge(json_decode($payment->payment_detail, true), ['confirm' => $confirmOrder['data']])),
                    'updated_by' => 'system',
                ]);

                DB::commit();
                return [
                    'status' => 'success',
                    // 'data'      => $order->data,
                    'message' => 'Berhasil melakukan refund',
                ];
            } else {
                return $confirmOrder;
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

}

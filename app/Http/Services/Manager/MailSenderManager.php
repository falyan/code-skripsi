<?php

namespace App\Http\Services\Manager;

use App\Http\Services\Transaction\TransactionQueries;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailSenderManager
{
    public function mailCheckout($customer, $order_id)
    {
        $transactionQueries = new TransactionQueries();
        $order = $transactionQueries->getDetailTransaction($order_id);
        $data = [
            'destination_name' => $customer->full_name ?? 'Pengguna Setia',
            'order' => $order,
            'order_detail' => $order->detail,
            'payment' => $order->payment
        ];

        Mail::send('email.checkoutFeedback', $data, function ($mail) use ($customer) {
            $mail->to($customer->email, 'no-reply')
                ->subject("Selesaikan Pembayaranmu");
            $mail->from(env('MAIL_FROM_ADDRESS'), 'PLN Marketplace');
        });

        if (Mail::failures()) {
            Log::error('Gagal mengirim email checkout untuk ID order ' . $order_id . ' ke email: ' . $customer->email,);
        }

        Log::info('Berhasil mengirim email checkout untuk ID order ' . $order_id . ' ke email: ' . $customer->email,);
    }

    public function mailPaymentSuccess($customer, $order_id)
    {
        $transactionQueries = new TransactionQueries();
        $order = $transactionQueries->getDetailTransaction($order_id);
        $data = [
            'destination_name' => $customer->full_name ?? 'Pengguna Setia',
            'order' => $order,
            'order_detail' => $order->detail,
            'payment' => $order->payment
        ];

        Mail::send('email.paymentSuccess', $data, function ($mail) use ($customer) {
            $mail->to($customer->email, 'no-reply')
                ->subject("Pembayaran Berhasil");
            $mail->from(env('MAIL_FROM_ADDRESS'), 'PLN Marketplace');
        });

        if (Mail::failures()) {
            Log::error('Gagal mengirim email pembayaran berhasil untuk ID order ' . $order_id . ' ke email: ' . $customer->email,);
        }

        Log::info('Berhasil mengirim email pembayaran berhasil untuk ID order ' . $order_id . ' ke email: ' . $customer->email,);
    }

    public function mailOrderDone($customer, $order_id)
    {
        $transactionQueries = new TransactionQueries();
        $order = $transactionQueries->getDetailTransaction($order_id);
        $data = [
            'destination_name' => $customer->full_name ?? 'Pengguna Setia',
            'order' => $order,
            'order_detail' => $order->detail,
            'payment' => $order->payment
        ];

        Mail::send('email.orderDone', $data, function ($mail) use ($customer) {
            $mail->to($customer->email, 'no-reply')
                ->subject("Pesanan Selesai");
            $mail->from(env('MAIL_FROM_ADDRESS'), 'PLN Marketplace');
        });

        if (Mail::failures()) {
            Log::error('Gagal mengirim email pesanan selesai untuk ke email: ' . $customer->email,);
        }

        Log::info('Berhasil mengirim email pesanan selesai untuk ke email: ' . $customer->email,);
    }

    public function mailConfirmFinish($customer, $order_id)
    {
        $transactionQueries = new TransactionQueries();
        $order = $transactionQueries->getDetailTransaction($order_id);
        $data = [
            'destination_name' => $order->merchant->name ?? 'Toko Favorit',
            'customer' => $customer,
            'order' => $order,
            'order_detail' => $order->detail,
            'payment' => $order->payment
        ];

        Mail::send('email.confirmFinishOrder', $data, function ($mail) use ($customer) {
            $mail->to($customer->email, 'no-reply')
                ->subject("Pesanan Selesai");
            $mail->from(env('MAIL_FROM_ADDRESS'), 'PLN Marketplace');
        });

        if (Mail::failures()) {
            Log::error('Gagal mengirim email pesanan selesai untuk ke email: ' . $customer->email,);
        }

        Log::info('Berhasil mengirim email pesanan selesai untuk ke email: ' . $customer->email,);
    }

    public function mailorderRejected($customer, $reason)
    {
        $data = [
            'user' => $customer->full_name,
            'reason' => $reason,
        ];

        Mail::send('email.orderRejected', $data, function ($mail) use ($customer) {
            $mail->to($customer->email, 'no-reply')
                ->subject("Pesanan Ditolak");
            $mail->from(env('MAIL_FROM_ADDRESS'), 'PLN Marketplace');
        });

        if (Mail::failures()) {
            Log::error('Gagal mengirim email pesanan ditolak untuk email: ' . $customer->email,);
        }

        Log::info('Berhasil mengirim email pesanan ditolak untuk email: ' . $customer->email,);
    }

    public function mailorderCanceled($customer)
    {
        $data = [
            'user' => $customer->full_name,
        ];

        Mail::send('email.orderCanceled', $data, function ($mail) use ($customer) {
            $mail->to($customer->email, 'no-reply')
                ->subject("Pesanan Ditolak");
            $mail->from(env('MAIL_FROM_ADDRESS'), 'PLN Marketplace');
        });

        if (Mail::failures()) {
            Log::error('Gagal mengirim email pesanan ditolak untuk email: ' . $customer->email,);
        }

        Log::info('Berhasil mengirim email pesanan ditolak untuk email: ' . $customer->email,);
    }
}

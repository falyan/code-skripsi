<?php

namespace App\Http\Services\Manager;

use App\Http\Services\Transaction\TransactionQueries;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailSenderManager
{
    public function mailCheckout($order_id)
    {
        $transactionQueries = new TransactionQueries();
        $order = $transactionQueries->getDetailTransaction($order_id);
        $customer = $order->buyer;
        $data = [
            'destination_name' => $customer->full_name ?? 'Pengguna Setia',
            'order' => $order,
        ];

        Mail::send('email.checkoutFeedback', $data, function ($mail) use ($customer) {
            $mail->to($customer->email, 'no-reply')
                ->subject("Selesaikan Pembayaranmu");
            $mail->from(env('MAIL_FROM_ADDRESS'), 'PLN Marketplace');
        });

        if (Mail::failures()) {
            Log::error('Gagal mengirim email checkout ke email: ' . $customer->email);
        } else {
            Log::info('Berhasil mengirim email checkout ke email: ' . $customer->email);
        }
    }

    public function mailPaymentSuccess($order_id)
    {
        $transactionQueries = new TransactionQueries();
        $order = $transactionQueries->getDetailTransaction($order_id);
        $customer = $order->buyer;
        $data = [
            'destination_name' => $customer->full_name ?? 'Pengguna Setia',
            'order' => $order,
        ];

        Mail::send('email.paymentSuccess', $data, function ($mail) use ($customer) {
            $mail->to($customer->email, 'no-reply')
                ->subject("Pembayaran Berhasil");
            $mail->from(env('MAIL_FROM_ADDRESS'), 'PLN Marketplace');
        });

        if (Mail::failures()) {
            Log::error('Gagal mengirim email pembayaran berhasil ke email: ' . $customer->email);
        } else {
            Log::info('Berhasil mengirim email pembayaran berhasil ke email: ' . $customer->email);
        }
    }

    public function mailNewOrder($order_id)
    {
        $transactionQueries = new TransactionQueries();
        $order = $transactionQueries->getDetailTransaction($order_id);
        $merchant = $order->merchant;
        $data = [
            'destination_name' => $order->merchant->name ?? 'Toko Favorit',
            'order' => $order
        ];

        Mail::send('email.newOrder', $data, function ($mail) use ($merchant) {
            $mail->to($merchant->email, 'no-reply')
                ->subject("{$merchant->name}, Ada Pesanan Baru nih");
            $mail->from(env('MAIL_FROM_ADDRESS'), 'PLN Marketplace');
        });

        if (Mail::failures()) {
            Log::error('Gagal mengirim email pesanan baru untuk ke email: ' . $merchant->email);
        } else {
            Log::info('Berhasil mengirim email pesanan baru untuk ke email: ' . $merchant->email);
        }


        return;
    }

    public function mailOrderOnDelivery($order_id)
    {
        $transactionQueries = new TransactionQueries();
        $order = $transactionQueries->getDetailTransaction($order_id);
        $customer = $order->buyer;
        $data = [
            'destination_name' => $order->merchant->name ?? 'Toko Favorit',
            'order' => $order
        ];

        Mail::send('email.orderOnDelivery', $data, function ($mail) use ($customer) {
            $mail->to($customer->email, 'no-reply')
                ->subject("Pesanan Sedang Dikirim");
            $mail->from(env('MAIL_FROM_ADDRESS'), 'PLN Marketplace');
        });

        if (Mail::failures()) {
            Log::error('Gagal mengirim email pesanan baru untuk ke email: ' . $customer->email);
        } else {
            Log::info('Berhasil mengirim email pesanan baru untuk ke email: ' . $customer->email);
        }

        return;
    }

    public function mailAcceptOrder($order_id)
    {
        $transactionQueries = new TransactionQueries();
        $order = $transactionQueries->getDetailTransaction($order_id);
        $customer = $order->buyer;
        $data = [
            'destination_name' => $order->merchant->name ?? 'Toko Favorit',
            'order' => $order
        ];

        Mail::send('email.acceptOrder', $data, function ($mail) use ($customer) {
            $mail->to($customer->email, 'no-reply')
            ->subject("Pesanan Siap Dikirim");
            $mail->from(env('MAIL_FROM_ADDRESS'), 'PLN Marketplace');
        });

        if (Mail::failures()) {
            Log::error('Gagal mengirim email pesanan baru untuk ke email: ' . $customer->email);
        } else {
            Log::info('Berhasil mengirim email pesanan baru untuk ke email: ' . $customer->email);
        }

        return;
    }

    public function mailOrderArrived($order_id, $date_arrived)
    {
        $transactionQueries = new TransactionQueries();
        $order = $transactionQueries->getDetailTransaction($order_id);
        $customer = $order->buyer;
        $merchant = $order->merchant;
        $data = [
            'destination_name' => $customer->full_name ?? 'Pengguna Setia',
            'order' => $order,
            'date_arrived' => $date_arrived
        ];

        Mail::send('email.orderDeliveredBuyer', $data, function ($mail) use ($customer) {
            $mail->to($customer->email, 'no-reply')
                ->subject("Pesanan Telah Dikirim");
            $mail->from(env('MAIL_FROM_ADDRESS'), 'PLN Marketplace');
        });

        if (Mail::failures()) {
            Log::error('Gagal mengirim email pesanan sampai untuk ke email: ' . $customer->email,);
        } else {
            Log::info('Berhasil mengirim email pesanan sampai ke email: ' . $customer->email);
        }

        $data = [
            'destination_name' => $merchant->name ?? 'Toko Favorit',
            'order' => $order,
            'date_arrived' => $date_arrived
        ];

        Mail::send('email.orderDeliveredSeller', $data, function ($mail) use ($customer, $merchant) {
            $mail->to($merchant->email, 'no-reply')
                ->subject("Produk Anda Telah Sampai ke Alamat {$customer->full_name}");
            $mail->from(env('MAIL_FROM_ADDRESS'), 'PLN Marketplace');
        });

        if (Mail::failures()) {
            Log::error('Gagal mengirim email pesanan sampai untuk ke email: ' . $merchant->email);
        } else {
            Log::info('Berhasil mengirim email pesanan sampai untuk ke email: ' . $merchant->email);
        }
    }

    public function mailOrderDone($order_id)
    {
        $transactionQueries = new TransactionQueries();
        $order = $transactionQueries->getDetailTransaction($order_id);
        $customer = $order->buyer;
        $merchant = $order->merchant;
        $data = [
            'destination_name' => $customer->full_name ?? 'Pengguna Setia',
            'order' => $order
        ];

        Mail::send('email.orderDone', $data, function ($mail) use ($customer) {
            $mail->to($customer->email, 'no-reply')
                ->subject("Pesanan Selesai");
            $mail->from(env('MAIL_FROM_ADDRESS'), 'PLN Marketplace');
        });

        if (Mail::failures()) {
            Log::error('Gagal mengirim email pesanan selesai untuk ke email: ' . $customer->email);
        } else {
            Log::info('Berhasil mengirim email pesanan selesai ke email: ' . $customer->email);
        }

        $data = [
            'destination_name' => $merchant->name ?? 'Toko Favorit',
            'order' => $order
        ];

        Mail::send('email.confirmFinishOrder', $data, function ($mail) use ($merchant) {
            $mail->to($merchant->email, 'no-reply')
                ->subject("Pesanan Selesai");
            $mail->from(env('MAIL_FROM_ADDRESS'), 'PLN Marketplace');
        });

        if (Mail::failures()) {
            Log::error('Gagal mengirim email pesanan selesai untuk ke email: ' . $merchant->email);
        } else {
            Log::info('Berhasil mengirim email pesanan selesai ke email: ' . $merchant->email);
        }
    }

    public function mailorderRejected($order_id, $reason)
    {
        $transactionQueries = new TransactionQueries();
        $order = $transactionQueries->getDetailTransaction($order_id);
        $customer = $order->buyer;

        $data = [
            'destination_name' => $customer->full_name,
            'reason' => $reason,
            'order' => $order,
        ];

        Mail::send('email.orderRejected', $data, function ($mail) use ($customer) {
            $mail->to($customer->email, 'no-reply')
                ->subject("Pesanan Ditolak");
            $mail->from(env('MAIL_FROM_ADDRESS'), 'PLN Marketplace');
        });

        if (Mail::failures()) {
            Log::error('Gagal mengirim email pesanan ditolak untuk email: ' . $customer->email);
        } else {
            Log::info('Berhasil mengirim email pesanan ditolak ke email: ' . $customer->email);
        }
    }

    public function mailorderCanceled($order_id)
    {
        $transactionQueries = new TransactionQueries();
        $order = $transactionQueries->getDetailTransaction($order_id);
        $customer = $order->buyer;
        $data = [
            'destination_name' => $customer->full_name,
            'order' => $order,
        ];

        Mail::send('email.orderCanceled', $data, function ($mail) use ($customer) {
            $mail->to($customer->email, 'no-reply')
                ->subject("Pesanan Dibatalkan");
            $mail->from(env('MAIL_FROM_ADDRESS'), 'PLN Marketplace');
        });

        if (Mail::failures()) {
            Log::error('Gagal mengirim email pesanan dibatalkan untuk email: ' . $customer->email);
        } else {
            Log::info('Berhasil mengirim email pesanan dibatalkan ke email: ' . $customer->email);
        }
    }

    public function mailTestDrive($destination_name, $destination_email, $message)
    {
        $data = [
            'destination_name' => $destination_name,
            'message_body' => $message,
        ];

        Mail::send('email.testDriveMail', $data, function ($mail) use ($destination_email) {
            $mail->to($destination_email, 'no-reply')
                ->subject("Notifikasi Event Test Drive");
            $mail->from(env('MAIL_FROM_ADDRESS'), 'PLN Marketplace');
        });

        if (Mail::failures()) {
            Log::error('Gagal mengirim email pesanan dibatalkan untuk email: ' . $destination_email);
        } else {
            Log::info('Berhasil mengirim email pesanan dibatalkan ke email: ' . $destination_email);
        }
    }
}

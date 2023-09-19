<?php

namespace App\Http\Services\Manager;

use App\Http\Services\Transaction\TransactionQueries;
use App\Models\CustomerTiket;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailSenderManager
{
    public function mailCheckout($order_id)
    {
        $transactionQueries = new TransactionQueries();
        $order = $transactionQueries->getDetailTransaction($order_id);
        $customer = $order->buyer;
        $payment = $order->payment;
        $data = [
            'destination_name' => $customer->full_name ?? 'Pengguna Setia',
            'order' => $order,
            'payment' => $payment,
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
            'order' => $order,
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
            'destination_name' => $customer->full_name ?? 'Pengguna Setia',
            'order' => $order,
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
            'order' => $order,
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
            'date_arrived' => $date_arrived,
        ];

        Mail::send('email.orderDeliveredBuyer', $data, function ($mail) use ($customer) {
            $mail->to($customer->email, 'no-reply')
                ->subject("Pesanan Telah Dikirim");
            $mail->from(env('MAIL_FROM_ADDRESS'), 'PLN Marketplace');
        });

        if (Mail::failures()) {
            Log::error('Gagal mengirim email pesanan sampai untuk ke email: ' . $customer->email, );
        } else {
            Log::info('Berhasil mengirim email pesanan sampai ke email: ' . $customer->email);
        }

        $data = [
            'destination_name' => $merchant->name ?? 'Toko Favorit',
            'order' => $order,
            'date_arrived' => $date_arrived,
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
            'order' => $order,
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
            'order' => $order,
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

    public function mailResendVoucher($order_id)
    {
        $transactionQueries = new TransactionQueries();
        $order = $transactionQueries->getDetailTransaction($order_id);
        $customer = $order->buyer;
        $data = [
            'destination_name' => $order->merchant->name ?? 'Toko Favorit',
            'order' => $order,
        ];

        Mail::send('email.acceptOrder', $data, function ($mail) use ($customer) {
            $mail->to($customer->email, 'no-reply')
                ->subject("Pesanan Siap Dikirim (Retry Voucher)");
            $mail->from(env('MAIL_FROM_ADDRESS'), 'PLN Marketplace');
        });

        if (Mail::failures()) {
            Log::error('Gagal mengirim email pesanan baru untuk ke email: ' . $customer->email);
        } else {
            Log::info('Berhasil mengirim email pesanan baru untuk ke email: ' . $customer->email);
        }

        return;
    }

    public function mailSendTicket($order_id, $user_tikets)
    {
        // check if file exist
        if (!file_exists(storage_path('app/public/ticket'))) {
            mkdir(storage_path('app/public/ticket'), 0777, true);
        }

        $transactionQueries = new TransactionQueries();
        $order = $transactionQueries->getDetailTransaction($order_id);
        $customer = $order->buyer;
        $order->load('detail.product.category', 'detail.product.category.parent');

        $master_data_tiket = [];
        foreach ($order->detail as $detail) {
            $master_data_tiket[] = $detail->product->category;
        }

        $user_tikets = CustomerTiket::with('master_tiket')->whereIn('id', collect($user_tikets)->pluck('id')->toArray())->get();

        //generate ticket pdf and send to customer
        $attachments = [];
        foreach ($user_tikets as $user_tiket) {
            // Generate QR Code using Endroid/QRCode and builder
            $result = Builder::create()
                ->writer(new PngWriter())
                ->writerOptions([])
                ->data($user_tiket->number_tiket)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
                ->size(85)
                ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
                ->validateResult(false)
                ->build();

            $result->saveToFile(storage_path('app/public/ticket/ticket-' . $user_tiket->number_tiket . '.png'));

            Pdf::loadView('pdf.ticket', [
                'order' => $order,
                'customer' => $customer,
                'user_tiket' => $user_tiket,
            ])->save(storage_path('app/public/ticket/ticket-' . $user_tiket->number_tiket . '.pdf'));
            $attachments[] = storage_path('app/public/ticket/ticket-' . $user_tiket->number_tiket . '.pdf');
        }

        $data = [
            'destination_name' => $customer->full_name ?? 'Pengguna Setia',
            'order' => $order,
            'user_tikets' => $user_tikets,
        ];

        Mail::send('email.sendTicket', $data, function ($mail) use ($customer, $attachments, $order) {
            $mail->to($customer->email, 'no-reply')
                ->subject("Pemesanan Tiket GJLS COMEDY NIGHT");
            $mail->from(env('MAIL_FROM_ADDRESS'), 'PLN Marketplace');
            foreach ($attachments as $file_path) {
                $mail->attach($file_path);
            }
        });

        if (file_exists(storage_path('app/public/ticket'))) {
            File::deleteDirectory(storage_path('app/public/ticket'));
        }

        if (Mail::failures()) {
            Log::error('Gagal mengirim email pesanan selesai untuk ke email: ' . $customer->email);
        } else {
            Log::info('Berhasil mengirim email pesanan selesai ke email: ' . $customer->email);
        }
    }

    public function mailResendTicket($order_id, $user_tikets)
    {
        // check if file exist
        if (!file_exists(storage_path('app/public/ticket'))) {
            mkdir(storage_path('app/public/ticket'), 0777, true);
        }

        $transactionQueries = new TransactionQueries();
        $order = $transactionQueries->getDetailTransaction($order_id);
        $customer = $order->buyer;
        $order->load('detail.product.category', 'detail.product.category.parent');

        $master_data_tiket = [];
        foreach ($order->detail as $detail) {
            $master_data_tiket[] = $detail->product->category;
        }

        //generate ticket pdf and send to customer
        $attachments = [];
        foreach ($user_tikets as $user_tiket) {
            // Generate QR Code using Endroid/QRCode and builder
            $result = Builder::create()
                ->writer(new PngWriter())
                ->writerOptions([])
                ->data($user_tiket->number_tiket)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
                ->size(85)
                ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
                ->validateResult(false)
                ->build();

            $result->saveToFile(storage_path('app/public/ticket/ticket-' . $user_tiket->number_tiket . '.png'));

            Pdf::loadView('pdf.ticket', [
                'order' => $order,
                'customer' => $customer,
                'user_tiket' => $user_tiket,
            ])->save(storage_path('app/public/ticket/ticket-' . $user_tiket->number_tiket . '.pdf'));
            $attachments[] = storage_path('app/public/ticket/ticket-' . $user_tiket->number_tiket . '.pdf');
        }

        $data = [
            'destination_name' => $customer->full_name ?? 'Pengguna Setia',
            'order' => $order,
            'user_tikets' => $user_tikets,
        ];

        Mail::send('email.sendTicket', $data, function ($mail) use ($customer, $attachments, $order) {
            $mail->to($customer->email, 'no-reply')
                ->subject("[Pengiriman Ulang] Pemesanan Tiket GJLS COMEDY NIGHT");
            $mail->from(env('MAIL_FROM_ADDRESS'), 'PLN Marketplace');
            foreach ($attachments as $file_path) {
                $mail->attach($file_path);
            }
        });

        if (file_exists(storage_path('app/public/ticket'))) {
            File::deleteDirectory(storage_path('app/public/ticket'));
        }

        if (Mail::failures()) {
            Log::error('Gagal mengirim email pesanan selesai untuk ke email: ' . $customer->email);
        } else {
            Log::info('Berhasil mengirim email pesanan selesai ke email: ' . $customer->email);
        }
    }

    public function mainVoucherClaim($order_id)
    {
        $transactionQueries = new TransactionQueries();
        $order = $transactionQueries->getDetailTransaction($order_id);
        $customer = $order->buyer;
        $data = [
            'destination_name' => $customer->full_name ?? 'Pengguna Setia',
            'order' => $order,
            'district_name' => null,
        ];

        Mail::send('email.vaoucherClaim', $data, function ($mail) use ($customer) {
            $mail->to($customer->email, 'no-reply')
                ->subject("Klaim Voucher Ubah Daya PLN");
            $mail->from(env('MAIL_FROM_ADDRESS'), 'PLN Marketplace');
        });

        if (Mail::failures()) {
            Log::error('Gagal mengirim email klaim voucher ke email: ' . $customer->email);
        } else {
            Log::info('Berhasil mengirim email klaim voucher ke email: ' . $customer->email);
        }

        return;
    }

    public function mailCheckoutSubsidy($order_id)
    {
        $transactionQueries = new TransactionQueries();
        $order = $transactionQueries->getDetailTransaction($order_id);
        $customer = $order->buyer;
        $data = [
            'destination_name' => $customer->full_name ?? 'Pengguna Setia',
            'order' => $order,
        ];

        Mail::send('email.checkoutSubsidyFeedback', $data, function ($mail) use ($customer) {
            $mail->to($customer->email, 'no-reply')
                ->subject("Pesananmu lagi direview");
            $mail->from(env('MAIL_FROM_ADDRESS'), 'PLN Marketplace');
        });

        if (Mail::failures()) {
            Log::error('Gagal mengirim email checkout ke email: ' . $customer->email);
        } else {
            Log::info('Berhasil mengirim email checkout ke email: ' . $customer->email);
        }
    }

    public function approvalTokoEmail($email, $data)
    {
        Mail::send('email.approve-toko', $data, function ($mail) use ($email) {
            $mail->to($email, 'no-reply')
                ->subject("Persetujuan Toko");
            $mail->from(env('MAIL_FROM_ADDRESS'), 'PLN Marketplace');
        });

        if (Mail::failures()) {
            Log::error('Gagal mengirim email checkout ke email: ' . $email);
        } else {
            Log::info('Berhasil mengirim email checkout ke email: ' . $email);
        }
    }

    public function mailApprovedEVSubsidy($order_id)
    {
        $transactionQueries = new TransactionQueries();
        $order = $transactionQueries->getDetailTransaction($order_id);
        $data = [
            'destination_name' => $order->buyer->full_name ?? 'Pengguna Setia',
            'order' => $order,
        ];

        Mail::send('email.approvedEVSubsidy', $data, function ($mail) use ($order) {
            $mail->to($order->buyer->email, 'no-reply')
                ->subject("Pesananmu sudah disetujui");
            $mail->from(env('MAIL_FROM_ADDRESS'), 'PLN Marketplace');
        });

        if (Mail::failures()) {
            Log::error(
                'Gagal mengirim email approval ev subsidy ke email: ' . $order->buyer->email
            );
        } else {
            Log::info('Berhasil mengirim email approval ev subsidy ke email: ' . $order->buyer->email);
        }
    }

    public function mailRejectedEVSubsidy($order_id)
    {
        $transactionQueries = new TransactionQueries();
        $order = $transactionQueries->getDetailTransaction($order_id);
        $data = [
            'destination_name' => $order->buyer->full_name ?? 'Pengguna Setia',
            'order' => $order,
        ];

        Mail::send('email.rejectedEVSubsidy', $data, function ($mail) use ($order) {
            $mail->to($order->buyer->email, 'no-reply')
                ->subject("Pesananmu sudah disetujui");
            $mail->from(env('MAIL_FROM_ADDRESS'), 'PLN Marketplace');
        });

        if (Mail::failures()) {
            Log::error(
                'Gagal mengirim email approval ev subsidy ke email: ' . $order->buyer->email
            );
        } else {
            Log::info('Berhasil mengirim email approval ev subsidy ke email: ' . $order->buyer->email);
        }
    }
}

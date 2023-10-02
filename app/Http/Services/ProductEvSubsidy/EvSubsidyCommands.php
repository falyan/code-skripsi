<?php

namespace App\Http\Services\ProductEvSubsidy;

use App\Helpers\LogService;
use App\Http\Services\Installment\InstallmentQueries;
use App\Http\Services\Manager\MailSenderManager;
use App\Http\Services\Service;
use App\Models\CustomerEVSubsidy;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\ProductEvSubsidy;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;

class EvSubsidyCommands extends Service
{
    static $apiendpoint;
    static $appkey;
    static $curl;
    static $clientid;
    static $productid;
    static $appsource;
    static $header;
    static $timestamp;
    static $logService;

    public static function init()
    {
        self::$curl = new Client();
        self::$apiendpoint = config('credentials.iconpay.endpoint');
        self::$appkey = config('credentials.iconpay.app_key');
        self::$clientid = config('credentials.iconpay.client_id');
        self::$productid = config('credentials.iconpay.product_id');
        self::$appsource = config('credentials.iconpay.app_source');
        self::$header = [
            'client-id' => static::$clientid,
            'appsource' => static::$appsource,
        ];
        self::$timestamp = Carbon::now('Asia/Jakarta')->toIso8601String();

        // logService
        self::$logService = new LogService();
    }

    public function __construct()
    {
        static::init();
    }

    public function create($request)
    {
        $products = $request['products'];

        $ev_products = [];
        foreach ($products as $product) {
            $ev_products[] = [
                'product_id' => $product['product_id'],
                'merchant_id' => auth()->user()->merchant_id,
                'subsidy_amount' => $product['subsidy_amount'],
                'created_by' => auth()->user()->full_name,
                'created_at' => Carbon::now(),
            ];
        }

        try {
            DB::beginTransaction();
            ProductEVSubsidy::insert($ev_products);

            DB::commit();

            return [
                'status' => true,
                'message' => 'EV Subsidi berhasil dibuat',
                'data' => $ev_products,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'status' => false,
                'message' => 'EV Subsidi gagal dibuat',
                'errors' => $e->getMessage(),
            ];
        }
    }

    public function updateEvSubsidy($request, $id)
    {
        $subsidy_amount = $request['subsidy_amount'];

        $ev_product = ProductEvSubsidy::find($id);

        if (!$ev_product) {
            return [
                'status' => false,
                'message' => 'EV Subsidi tidak ditemukan',
            ];
        }

        $ev_product->subsidy_amount = $subsidy_amount;
        $ev_product->updated_by = auth()->user()->full_name;
        $ev_product->updated_at = Carbon::now();

        try {
            DB::beginTransaction();
            $ev_product->save();

            DB::commit();

            return [
                'status' => true,
                'message' => 'EV Subsidi berhasil diupdate',
                'data' => $ev_product,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'status' => false,
                'message' => 'EV Subsidi gagal diupdate',
                'errors' => $e->getMessage(),
            ];
        }
    }

    public function deleteEvSubsidy($request)
    {
        $ev_products = ProductEvSubsidy::whereIn('id', $request['ids']);

        if (!$ev_products->get()) {
            return [
                'status' => false,
                'message' => 'EV Subsidi tidak ditemukan',
            ];
        }

        try {
            DB::beginTransaction();
            $ev_products->delete();

            DB::commit();

            return [
                'status' => true,
                'message' => 'EV Subsidi berhasil dihapus',
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'status' => false,
                'message' => 'EV Subsidi gagal dihapus',
                'errors' => $e->getMessage(),
            ];
        }
    }

    public function updateStatus($request)
    {
        $data = CustomerEVSubsidy::findOrFail($request['ev_subsidy_id']);

        $trx_date = date('Y/m/d H:i:s', Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now('Asia/Jakarta'))->timestamp);
        $exp_date = date('Y/m/d H:i:s', Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now('Asia/Jakarta')->addDays(3))->timestamp);

        // if ($data->status_approval != null) {
        //     return [
        //         'status' => false,
        //         'message' => 'Status tidak dalam status menunggu',
        //         'errors' => 'Status approval telah ' . ($data->status_approval == 0 ? 'ditolak' : 'disetujui'),
        //     ];
        // }

        try {
            DB::beginTransaction();
            $data->status_approval = $request['status'];
            $data->save();

            $order = Order::with('detail', 'buyer', 'installment')->findOrFail($data->order_id);
            $payment = OrderPayment::where('id', $order->payment_id)->first();

            if ($request['status'] == 0) {

                $totalProductNormalPrice = 0;
                $totalProductPrice = 0;

                foreach ($order->detail as $detail) {
                    $product = Product::where([
                        'id' => $detail->product_id,
                        'status' => 1,
                    ])->first();

                    $totalProductNormalPrice += $product->strike_price;
                    $totalProductPrice += $detail->total_price;

                    $detail->price = $product->strike_price;
                    $detail->total_price = $product->strike_price * $detail->quantity;
                    $detail->total_amount = $detail->total_price;

                    // update detail->product_data with json_encode
                    $product_data = json_decode($detail->product_data);
                    $product_data->price = $product->strike_price;
                    $detail->product_data = json_encode($product_data);
                    $detail->save();
                }

                $order->total_amount = $order->total_amount - $totalProductPrice + $totalProductNormalPrice;
                $order->total_amount_iconcash = $order->total_amount_iconcash - $totalProductPrice + $totalProductNormalPrice;
                $order->save();

                $payment->payment_amount = $order->total_amount;
                $payment->date_expired = $exp_date;
                $payment->save();

                $recalculateInstallment = InstallmentQueries::calculateInstallment([
                    'provider_id' => $order->installment->pi_provider_id,
                    'tenor' => $order->installment->month_tenor,
                ], $order->total_amount);

                $order->installment->fee_tenor = $recalculateInstallment['installment_fee'];
                $order->installment->installment_tenor = $recalculateInstallment['installment_price'];
                $order->installment->markup_price_tenor = $recalculateInstallment['markup_price'];
                $order->installment->actual_price_tenor = $recalculateInstallment['price'];
                $order->installment->save();

                // $mailSender = new MailSenderManager();
                // $mailSender->mailRejectedEVSubsidy($data->order_id);

            } else if ($request['status'] == 1) {

                $payment->date_expired = $exp_date;
                $payment->save();

                $mailSender = new MailSenderManager();
                $mailSender->mailApprovedEVSubsidy($data->order_id);
            }

            $url = sprintf('%s/%s', static::$apiendpoint, 'booking');
            $body = [
                'no_reference' => $order->no_reference,
                'transaction_date' => $trx_date,
                'transaction_code' => '00',
                'partner_reference' => $order->no_reference,
                'product_id' => static::$productid,
                'amount' => $payment->payment_amount,
                'customer_id' => $order->no_reference,
                'customer_name' => $order->buyer->full_name,
                'email' => $order->buyer->email,
                'phone_number' => $order->buyer->phone,
                'expired_invoice' => $exp_date,
                'additional_info7' => $order->installment->provider_fee ?? null,
                'additional_info8' => $order->installment->month_tenor < 10 ? str_pad($order->installment->month_tenor, 2, '0', STR_PAD_LEFT) : $order->installment->month_tenor ?? null,
                'additional_info9' => $order->installment->actual_price_tenor ?? null,
                'additional_info10' => $order->installment->fee_tenor ?? null,
            ];

            $encode_body = json_encode($body, JSON_UNESCAPED_SLASHES);

            static::$header['timestamp'] = static::$timestamp;
            static::$header['signature'] = hash_hmac('sha256', $encode_body . static::$clientid . static::$timestamp, sha1(static::$appkey));
            static::$header['content-type'] = 'application/json';

            $response = static::$curl->request('POST', $url, [
                'headers' => static::$header,
                'http_errors' => false,
                'body' => $encode_body,
            ]);

            $response = json_decode($response->getBody());

            static::$logService->setUrl($url)->setRequest($body)->setResponse($response)->setServiceCode('iconpay')->setCategory('out')->log();

            throw_if(!$response, Exception::class, new Exception('Terjadi kesalahan: Data tidak dapat diperoleh', 500));

            if ($response->response_details[0]->response_code != 00) {
                throw new Exception($response->response_details[0]->response_message);
            }

            $response->response_details[0]->amount = $payment->payment_amount;
            $response->response_details[0]->customer_id = (int) $response->response_details[0]->customer_id;
            $response->response_details[0]->partner_reference = (int) $response->response_details[0]->partner_reference;

            DB::commit();

            return [
                'status' => true,
                'message' => 'Status berhasil diupdate',
                'data' => [
                    'subsidy' => $data,
                    'response' => $response,
                ],
            ];
        } catch (\Throwable $th) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'Status gagal diupdate',
                'errors' => $th->getMessage(),
            ];
        }
    }
}

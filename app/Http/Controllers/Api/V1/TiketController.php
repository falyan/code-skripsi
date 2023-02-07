<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Tiket\TiketQueries;
use App\Http\Services\Transaction\TransactionCommands;
use App\Http\Services\Transaction\TransactionQueries;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;
use App\Http\Services\Manager\MailSenderManager;
use App\Models\Order;

class TiketController extends Controller
{
    static $SUCCESS = '00';
    static $TICKET_NOT_FOUND = '01';
    static $TICKET_NOT_ACTIVED = '02';
    static $TICKET_HAS_USED = '03';
    static $TICKET_DATE_NOT_VALID = '04';
    static $TICKET_TIME_NOT_VALID = '05';
    static $TICKET_UPDATE_FAILED = '06';
    static $HEADER_KEY_ACCESS_REQUIRED = '07';
    static $HEADER_KEY_ACCESS_INVALID = '08';

    static $ORDER_NOT_FOUND = '09';

    protected $tiketQueries, $transactionQueries, $transactionCommand;

    public function __construct()
    {
        $this->tiketQueries = new TiketQueries();
        $this->transactionQueries = new TransactionQueries();
        $this->transactionCommand = new TransactionCommands();
    }

    public function cekOrder(Request $request)
    {
        if (!$keyAccess = $request->header('Key-Access')) {
            return $this->respondBadRequest('Header Key-Access diperlukan', static::$HEADER_KEY_ACCESS_REQUIRED);
        }

        if (config('credentials.tiket.api_hash') != md5($keyAccess)) {
            return $this->respondBadRequest('Key-Access tidak valid', static::$HEADER_KEY_ACCESS_INVALID);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'trx_id' => 'required',
            ],
            [
                'exists' => 'kode :attribute tidak ditemukan.',
                'required' => ':attribute diperlukan.',
            ]
        );

        if ($validator->fails()) {
            $errors = collect();
            foreach ($validator->errors()->getMessages() as $value) {
                foreach ($value as $error) {
                    $errors->push($error);
                }
            }
            return $this->respondValidationError($errors);
        }

        $tiket = $this->tiketQueries->getTiketByOrder($request->all());
        if (isset($tiket['status']) && $tiket['status'] == 'error') {
            return $this->respondBadRequest($tiket['message'], $tiket['error_code']);
        }

        return [
            'status_code' => static::$SUCCESS,
            'status' => 'success',
            'message' => 'Tiket ditemukan',
            'data' => [
                'user_name' => $tiket[0]->user_name,
                'user_email' => $tiket[0]->user_email,
                'tikets' => array_map(function ($item) {
                    return $this->respondDataMaping($item);
                }, $tiket),
            ],
        ];
    }

    public function scanQr(Request $request)
    {
        if (!$keyAccess = $request->header('Key-Access')) {
            return $this->respondBadRequest('Header Key-Access diperlukan', static::$HEADER_KEY_ACCESS_REQUIRED);
        }

        if (config('credentials.tiket.api_hash') != md5($keyAccess)) {
            return $this->respondBadRequest('Key-Access tidak valid', static::$HEADER_KEY_ACCESS_INVALID);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'qr' => 'required',
            ],
            [
                'exists' => 'kode :attribute tidak ditemukan.',
                'required' => ':attribute diperlukan.',
            ]
        );

        if ($validator->fails()) {
            $errors = collect();
            foreach ($validator->errors()->getMessages() as $value) {
                foreach ($value as $error) {
                    $errors->push($error);
                }
            }
            return $this->respondValidationError($errors);
        }

        $tiket = $this->tiketQueries->getTiket($request->get('qr'));
        if (isset($tiket['status']) && $tiket['status'] == 'error') {
            return $this->respondBadRequest($tiket['message'], $tiket['error_code'], isset($tiket['data']) ? $tiket['data'] : null);
        }

        try {
            DB::beginTransaction();

            UserTiket::find($tiket->id)->update([
                'status' => 2,
                'event_info' => $request->get('event_info') ?? null,
            ]);

            $tiket->status = 2;

            DB::commit();
            return [
                'status_code' => static::$SUCCESS,
                'status' => 'success',
                'message' => 'Tiket ditemukan',
                'data' => $this->respondDataMaping($tiket),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => 'error',
                'message' => 'Gagal memperbarui status tiket',
            ];
            return $this->respondBadRequest('Gagal memperbarui status tiket', static::$TICKET_UPDATE_FAILED);
        }
    }

    public function resendTicket(Request $request)
    {
        try {
            if (!is_numeric($request['order_id'])) {
                $response = [
                    'success' => false,
                    'message' => 'order id harus berupa angka',
                ];
                return $response;
            }
            DB::beginTransaction();

            // $data = $this->transactionQueries->getStatusOrder($request['order_id'], true)->load('merchant');
            $data = Order::where('id', $request['order_id'])->with('merchant')->first();
            // $status_codes = [];
            // foreach ($data->progress as $item) {
            //     if (in_array($item->status_code, ['01', '02'])) {
            //         $status_codes[] = $item;
            //     }
            // }

            $tiket = null;
            // $status_code = collect($status_codes)->where('status_code', '02')->first();
            // if (count($status_codes) == 2 && $status_code['status'] == 1) {
            if ($data->merchant->official_store_proliga) {
                $tiket = $this->transactionCommand->generateTicket($request['order_id']);
                if ($tiket['success'] == false) {
                    return $tiket;
                }
            }

            DB::commit();

            $mailSender = new MailSenderManager();
            if ($data->merchant->official_store_proliga) {
                // dispatch(new SendEmailTiketJob($request['order_id'], $tiket['data']));
                $mailSender->mailResendTicket($request['order_id'], $tiket['data']);
            }

            return $this->respondWithResult(true, 'Berhasil mengirim ulang tiket', 200);
            // } else {
            //     return $this->respondWithResult(false, 'Pesanan ' . $request['order_id'] . ' tidak dalam status siap dikirim!', 400);
            // }
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respondErrorException($e, request());
        }
    }

    private function respondBadRequest($message, $error_code, $data = null)
    {
        if ($data != null) {
            return response()->json([
                'status_code' => $error_code,
                'status' => 'error',
                'message' => $message,
                'data' => $data,
            ], 400);
        }

        return response()->json([
            'status_code' => $error_code,
            'status' => 'error',
            'message' => $message,
        ], 400);
    }

    private function respondDataMaping($data)
    {
        return [
            'number_tiket' => $data['number_tiket'],
            'name' => $data['master_tiket']['name'],
            'description' => $data['master_tiket']['description'],
            'terms_and_conditions' => $data['master_tiket']['tnc'],
            'event_address' => $data['master_tiket']['event_address'],
            'usage_date' => $data['usage_date'],
            'usage_time' => ($data['start_time_usage'] != null && $data['end_time_usage'] != null) ? $data['start_time_usage'] . ' - ' . $data['end_time_usage'] : null,
            'status' => $data['status'],
            'is_vip' => $data['is_vip'],
            'created_at' => Carbon::parse($data['created_at'])->format('d M Y H:i:s'),
            'updated_at' => Carbon::parse($data['updated_at'])->format('d M Y H:i:s'),
        ];
    }
}

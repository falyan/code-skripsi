<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Manager\MailSenderManager;
use App\Http\Services\Tiket\TiketQueries;
use App\Models\UserTiket;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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

    protected $tiketQueries;

    public function __construct()
    {
        $this->tiketQueries = new TiketQueries();
    }

    public function cekOrder(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'trx_id' => 'required|min:3',
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

        $tiket = $this->tiketQueries->getTiketByOrder($request->get('trx_id'));
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
                'trx_no' => $tiket[0]->trx_no,
                'order_date' => $tiket[0]->order_date,
                'tikets' => array_map(function ($item) {
                    return $this->respondDataMaping($item);
                }, $tiket),
            ],
        ];
    }

    public function getTiket()
    {
        $limit = request()->get('limit') ?? 10;
        $tiket = $this->tiketQueries->getTiketAll($limit);
        if (isset($tiket['status']) && $tiket['status'] == 'error') {
            return $this->respondBadRequest($tiket['message'], $tiket['error_code']);
        }

        $customDataTiket = collect($tiket);
        $customDataTiket['data'] = array_map(function ($item) {
            return $this->respondDataMaping($item);
        }, $customDataTiket['data']);

        return [
            'status_code' => static::$SUCCESS,
            'status' => 'success',
            'message' => 'Tiket ditemukan',
            'data' => $customDataTiket,
        ];
    }

    public function scanQr(Request $request)
    {
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
            return $this->respondBadRequest($tiket['message'], $tiket['error_code'], isset($tiket['data']) ? $this->respondDataMaping($tiket['data']) : null);
        }

        return [
            'status_code' => static::$SUCCESS,
            'status' => 'success',
            'message' => 'Tiket ditemukan',
            'data' => $this->respondDataMaping($tiket),
        ];
    }

    public function scanQrCheckIn(Request $request)
    {
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
            return $this->respondBadRequest($tiket['message'], $tiket['error_code'], isset($tiket['data']) ? $this->respondDataMaping($tiket['data']) : null);
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
        } catch (\Exception$e) {
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
        $validator = Validator::make(
            $request->all(),
            [
                'order_id' => 'required|numeric',
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

        try {
            $tiket = $this->tiketQueries->getTiketByOrder($request->get('order_id'), true);
            if (isset($tiket['status']) && $tiket['status'] == 'error') {
                return $this->respondBadRequest($tiket['message'], $tiket['error_code']);
            }

            $mailSender = new MailSenderManager();
            $mailSender->mailResendTicket($request->get('order_id'), $tiket);

            return $this->respondWithResult(true, 'Berhasil mengirim ulang tiket', 200);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }
    //==== End of Tiket Proliga ====//

    //==== Tiket PLN MUDIK 2023 ====//
    public function checkInvoice(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'trx_no' => 'required|exists:order,trx_no',
                'email' => 'required|email',
            ],
            [
                'required' => ':attribute diperlukan.',
                'exists' => 'Nomor invoice tidak ditemukan.',
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

        //check if trx_no and email is valid and exist
        $order = $this->tiketQueries->getOrder($request->get('trx_no'), $request->get('email'));

        if (isset($order['status']) && $order['status'] == 'error') {
            return $this->respondBadRequest($order['message'], $order['error_code']);
        }

        return $this->respondWithData($order);
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
            'customer_name' => $data['order']['buyer']['full_name'],
            'customer_email' => $data['order']['buyer']['email'],
            'customer_phone' => $data['order']['buyer']['phone'],
            'usage_date' => $data['usage_date'],
            'usage_time' => ($data['start_time_usage'] != null && $data['end_time_usage'] != null) ? $data['start_time_usage'] . ' - ' . $data['end_time_usage'] : null,
            'status' => $data['status'],
            'created_at' => Carbon::parse($data['created_at'])->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::parse($data['updated_at'])->format('Y-m-d H:i:s'),
        ];
    }

    private function respondDataMapingOrder($data)
    {
        return [
            'number_tiket' => $data['number_tiket'],
            'name' => $data['master_tiket']['name'],
            'description' => $data['master_tiket']['description'],
            'terms_and_conditions' => $data['master_tiket']['tnc'],
            'event_address' => $data['master_tiket']['event_address'],
            'customer_name' => $data['order']['buyer']['full_name'],
            'customer_email' => $data['order']['buyer']['email'],
            'customer_phone' => $data['order']['buyer']['phone'],
            'usage_date' => $data['usage_date'],
            'usage_time' => ($data['start_time_usage'] != null && $data['end_time_usage'] != null) ? $data['start_time_usage'] . ' - ' . $data['end_time_usage'] : null,
            'status' => $data['status'],
            // 'is_vip' => $data['is_vip'],
            'created_at' => Carbon::parse($data['created_at'])->format('d M Y H:i:s'),
            'updated_at' => Carbon::parse($data['updated_at'])->format('d M Y H:i:s'),
        ];
    }
}

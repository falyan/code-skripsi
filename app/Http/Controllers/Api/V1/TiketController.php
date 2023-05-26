<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Tiket\TiketResource;
use App\Http\Services\Manager\MailSenderManager;
use App\Http\Services\Tiket\TiketQueries;
use App\Models\CustomerTiket;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
    protected $tiketResource;

    public function __construct()
    {
        $this->tiketQueries = new TiketQueries();
        $this->tiketResource = new TiketResource();
    }

    //==== Tiket Proliga ====//
    public function getTiket()
    {
        $limit = request()->get('limit') ?? 10;
        $keyword = request()->get('keyword') ?? null;

        if (Auth::guard('tiket')->user()->role == 'officer') {
            return $this->tiketResource->respondBadRequest('Anda tidak memiliki akses', static::$TICKET_NOT_FOUND);
        }

        $tiket = $this->tiketQueries->getTiketAll($limit, $keyword);
        if (isset($tiket['status']) && $tiket['status'] == 'error') {
            return $this->tiketResource->respondBadRequest($tiket['message'], $tiket['error_code']);
        }

        $customDataTiket = collect($tiket);
        $customDataTiket['data'] = array_map(function ($item) {
            return $this->tiketResource->respondDataMaping($item);
        }, $customDataTiket['data']);

        return [
            'status_code' => static::$SUCCESS,
            'status' => 'success',
            'message' => 'Tiket ditemukan',
            'data' => $customDataTiket,
        ];
    }

    public function getDashboard()
    {
        $dashboard = $this->tiketQueries->getDashboard();
        if (isset($tiket['status']) && $tiket['status'] == 'error') {
            return $this->tiketResource->respondBadRequest($tiket['message'], $tiket['error_code']);
        }

        return [
            'status_code' => static::$SUCCESS,
            'status' => 'success',
            'message' => 'Tiket ditemukan',
            'data' => $dashboard,
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
            return $this->tiketResource->respondBadRequest($tiket['message'], $tiket['error_code'], isset($tiket['data']) ? $this->tiketResource->respondDataMaping($tiket['data']) : null);
        }

        return [
            'status_code' => static::$SUCCESS,
            'status' => 'success',
            'message' => 'Tiket ditemukan',
            'data' => $this->tiketResource->respondDataMaping($tiket),
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
            return $this->tiketResource->respondBadRequest($tiket['message'], $tiket['error_code'], isset($tiket['data']) ? $this->tiketResource->respondDataMaping($tiket['data']) : null);
        }

        try {
            DB::beginTransaction();

            CustomerTiket::find($tiket->id)->update([
                'status' => 2,
                'event_info' => $request->get('event_info') ?? null,
            ]);

            $tiket->status = 2;

            DB::commit();
            return [
                'status_code' => static::$SUCCESS,
                'status' => 'success',
                'message' => 'Tiket ditemukan',
                'data' => $this->tiketResource->respondDataMaping($tiket),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => 'error',
                'message' => 'Gagal memperbarui status tiket',
            ];
            return $this->tiketResource->respondBadRequest('Gagal memperbarui status tiket', static::$TICKET_UPDATE_FAILED);
        }
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
            return $this->tiketResource->respondBadRequest($tiket['message'], $tiket['error_code']);
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
                    return $this->tiketResource->respondDataMapingOrder($item);
                }, $tiket),
            ],
        ];
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
                return $this->tiketResource->respondBadRequest($tiket['message'], $tiket['error_code']);
            }

            $mailSender = new MailSenderManager();
            $mailSender->mailResendTicket($request->get('order_id'), $tiket);

            return $this->respondWithResult(true, 'Berhasil mengirim ulang tiket', 200);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }
    //==== End of Tiket Proliga ====//
}

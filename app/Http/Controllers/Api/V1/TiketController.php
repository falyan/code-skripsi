<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Tiket\TiketQueries;
use Carbon\Carbon;
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
                'tiket' => array_map(function ($item) {
                    return $this->respondDataMapingOrder($item);
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
            return $this->respondBadRequest($tiket['message'], $tiket['error_code']);
        }

        try {
            DB::beginTransaction();

            $tiket->status = 2;
            $tiket->event_info = $request->get('event_info') ?? null;
            $tiket->save();

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

    private function respondBadRequest($message, $error_code)
    {
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
            'created_at' => Carbon::parse($data['created_at'])->format('d M Y H:i:s'),
            'updated_at' => Carbon::parse($data['updated_at'])->format('d M Y H:i:s'),
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
            'usage_date' => $data['usage_date'],
            'usage_time' => ($data['start_time_usage'] != null && $data['end_time_usage'] != null) ? $data['start_time_usage'] . ' - ' . $data['end_time_usage'] : null,
            'status' => $data['status'],
            'is_vip' => $data['is_vip'],
            'created_at' => Carbon::parse($data['created_at'])->format('d M Y H:i:s'),
            'updated_at' => Carbon::parse($data['updated_at'])->format('d M Y H:i:s'),
        ];
    }
}

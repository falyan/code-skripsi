<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserTiket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TiketController extends Controller
{
    private function respondDataMaping($data)
    {
        return [
            'number_tiket' => $data->number_tiket,
            'name' => $data->master_tiket->name,
            'description' => $data->master_tiket->description,
            'terms_and_conditions' => $data->master_tiket->tnc,
            'event_address' => $data->master_tiket->event_address,
            'usage_date' => $data->usage_date,
            'usage_time' => $data->start_time_usage . ' - ' . $data->end_time_usage,
            'status' => $data->status,
            'created_at' => Carbon::parse($data->created_at)->format('d M Y H:i:s'),
            'updated_at' => Carbon::parse($data->updated_at)->format('d M Y H:i:s'),
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

        $validator = Validator::make($request->all(), [
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

        $tiket = $this->getTiket($request->get('qr'));
        if ($tiket['status'] == 'error') {
            return $this->respondBadRequest($tiket['message'], $tiket['error_code']);
        }

        try {
            DB::beginTransaction();

            $tiket->status = 2;
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

    private function getTiket($qr)
    {
        $tiket = UserTiket::where('number_tiket', $qr)->first();

        if (!$tiket) {
            return [
                'error_code' => static::$TICKET_NOT_FOUND,
                'status' => 'error',
                'message' => 'Tiket tidak ditemukan',
            ];
        }

        $tiket->load('master_tiket');

        if ($tiket->master_tiket->status == 0) {
            return [
                'error_code' => static::$TICKET_NOT_ACTIVED,
                'status' => 'error',
                'message' => 'Tiket sudah tidak aktif',
            ];
        }

        if ($tiket->status == 2) {
            return [
                'error_code' => static::$TICKET_HAS_USED,
                'status' => 'error',
                'message' => 'Tiket telah digunakan',
            ];
        }

        if ($tiket->usage_date != Carbon::now()->format('Y-m-d')) {
            return [
                'error_code' => static::$TICKET_DATE_NOT_VALID,
                'status' => 'error',
                'message' => 'Tiket hanya bisa digunakan pada tanggal ' . Carbon::parse($tiket->usage_date)->format('d M Y'),
            ];
        }

        $start_time_usage = Carbon::parse($tiket->usage_date . ' ' . $tiket->start_time_usage)->format('Y-m-d H:i:s');
        $end_time_usage = Carbon::parse($tiket->usage_date . ' ' . $tiket->end_time_usage)->format('Y-m-d H:i:s');
        $now = Carbon::now()->format('Y-m-d H:i:s');

        if (!Carbon::parse($now)->between($start_time_usage, $end_time_usage)) {
            return [
                'error_code' => static::$TICKET_TIME_NOT_VALID,
                'status' => 'error',
                'message' => 'Tiket hanya bisa digunakan pada jam ' . Carbon::parse($tiket->start_usage_date)->format('H:i') . ' - ' . Carbon::parse($tiket->end_usage_date)->format('H:i'),
            ];
        }

        return $tiket;
    }

    private function respondBadRequest($message, $error_code)
    {
        return response()->json([
            'status_code' => $error_code,
            'status' => 'error',
            'message' => $message,
        ], 400);
    }

    static $SUCCESS = '00';
    static $TICKET_NOT_FOUND = '01';
    static $TICKET_NOT_ACTIVED = '02';
    static $TICKET_HAS_USED = '03';
    static $TICKET_DATE_NOT_VALID = '04';
    static $TICKET_TIME_NOT_VALID = '05';
    static $TICKET_UPDATE_FAILED = '06';
    static $HEADER_KEY_ACCESS_REQUIRED = '07';
    static $HEADER_KEY_ACCESS_INVALID = '08';
}

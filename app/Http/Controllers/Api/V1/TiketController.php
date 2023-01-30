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
            'status' => $data->status,
            'created_at' => Carbon::parse($data->created_at)->format('d M Y H:i:s'),
            'updated_at' => Carbon::parse($data->updated_at)->format('d M Y H:i:s'),
        ];
    }

    public function scanQr(Request $request)
    {
        if (!$keyAccess = $request->header('Key-Access')) {
            return $this->respondBadRequest('Header Key-Access diperlukan');
        }

        if (config('credentials.tiket.api_hash') != md5($keyAccess)) {
            return $this->respondBadRequest('Key-Access tidak valid');
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
            return $this->respondBadRequest($tiket['message']);
        }

        try {
            DB::beginTransaction();

            $tiket->status = 2;
            $tiket->save();

            DB::commit();
            return [
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
            return $this->respondBadRequest('Gagal memperbarui status tiket');
        }
    }

    private function getTiket($qr)
    {
        $tiket = UserTiket::where('number_tiket', $qr)->first();

        if (!$tiket) {
            return [
                'status' => 'error',
                'message' => 'Tiket tidak ditemukan',
            ];
        }

        $tiket->load('master_tiket');

        if ($tiket->master_tiket->status == 0) {
            return [
                'status' => 'error',
                'message' => 'Tiket sudah tidak aktif',
            ];
        }

        if ($tiket->status == 2) {
            return [
                'status' => 'error',
                'message' => 'Tiket telah digunakan',
            ];
        }

        if ($tiket->usage_date != Carbon::now()->format('Y-m-d')) {
            return [
                'status' => 'error',
                'message' => 'Tiket hanya bisa digunakan pada tanggal ' . Carbon::parse($tiket->usage_date)->format('d M Y'),
            ];
        }

        return $tiket;
    }

    private function respondBadRequest($message)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
        ], 400);
    }
}

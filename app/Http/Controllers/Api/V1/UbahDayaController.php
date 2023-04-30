<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\UbahDaya\UbahDayaCommands;
use App\Http\Services\UbahDaya\UbahDayaQueries;
use App\Imports\VoucherImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class UbahDayaController extends Controller
{
    private $ubahDayaCommands, $ubahDayaQueries;

    public function __construct() {
        $this->ubahDayaCommands = new UbahDayaCommands();
        $this->ubahDayaQueries = new UbahDayaQueries();
    }

    public function getVoucher() {
        $limit = request()->get('limit', 10);

        $data = $this->ubahDayaQueries->getVoucher(null, $limit);

        $response = [
            'success' => true,
            'data' => $data
        ];

        return response()->json($response, 200);
    }

    public function getVoucherById($id) {
        $data = $this->ubahDayaQueries->getVoucherById($id);

        $response = [
            'success' => true,
            'data' => $data
        ];

        return response()->json($response, 200);
    }

    public function createVoucher(Request $request)
    {
        $request = $request->all();

        $rules = [
            'event_name' => 'required',
            'event_start_date' => 'required',
            'event_end_date' => 'required',
            'type' => 'required|in:generate,pregenerate',
        ];

        if ($request['type'] == 'generate') {
            $rules['quota'] = 'required|numeric';
        }

        if ($request['type'] == 'pregenerate') {
            $rules['file'] = 'required|mimes:xlsx';
        }

        $validate = Validator::make($request, $rules);

        if ($validate->fails()) {
            $response = [
                'success' => false,
                'message' => $validate->errors()->first()
            ];

            return response()->json($response, 400);
        }

        try {
            DB::beginTransaction();
            $voucher = $this->ubahDayaCommands->createVoucher($request);

            if ($request['type'] == 'pregenerate') {
                $import = new VoucherImport($voucher->id);
                Excel::import($import, request()->file('file'));
            }

            $data = $this->ubahDayaQueries->getVoucherById($voucher->id);

            DB::commit();

            $response = [
                'success' => true,
                'message' => 'Voucher berhasil dibuat',
                'data' => $data
            ];

            return response()->json($response, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            $response = [
                'success' => false,
                'message' => $e->getMessage()
            ];

            return response()->json($response, 500);
        }
    }
}

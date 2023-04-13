<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\UbahDaya\UbahDayaCommands;
use App\Http\Services\UbahDaya\UbahDayaQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
            $rules['file'] = 'required';
        }

        $validate = Validator::make($request, $rules);

        if ($validate->fails()) {
            $response = [
                'success' => false,
                'message' => $validate->errors()->first()
            ];

            return response()->json($response, 400);
        }

        // $data = $this->ubahDayaCommands->createVoucher($request);

        if ($request['file'] != null) {

        }

        $response = [
            'success' => true,
            // 'data' => $data
        ];

        return response()->json($response, 200);
    }
}

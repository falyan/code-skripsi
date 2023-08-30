<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\ProductEvSubsidy\EvSubsidyCommands;
use App\Http\Services\ProductEvSubsidy\EvSubsidyQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EvSubsidyController extends Controller
{
    protected $EvSubsidyQueries, $EvSubsidyCommands;

    public function __construct()
    {
        $this->EvSubsidyQueries = new EvSubsidyQueries();
        $this->EvSubsidyCommands = new EvSubsidyCommands();
    }

    public function list()
    {
        $keyword = request()->get('keyword');
        $limit = request()->get('limit', 10);

        $data = $this->EvSubsidyQueries->getData($keyword, $limit);

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengambil data EV Subsidi',
            'data' => $data,
        ]);
    }

    public function create(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:product,id',
            'products.*.subsidy_amount' => 'required|numeric',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'message' => 'Invalid request',
                'errors' => $validate->errors(),
            ], 400);
        }

        $data = $this->EvSubsidyCommands->create($validate->validated());

        if (isset($data['status']) && $data['status'] == false) {
            return response()->json([
                'status' => false,
                'message' => $data['message'],
                'errors' => $data['errors'],
            ], 400);
        }

        return response()->json([
            'status' => true,
            'message' => 'Berhasil membuat data EV Subsidi',
            'data' => $data['data'],
        ]);
    }

    public function update(Request $request, $id)
    {
        $validate = Validator::make($request->all(), [
            'subsidy_amount' => 'required|numeric',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'message' => 'Invalid request',
                'errors' => $validate->errors(),
            ], 400);
        }

        $data = $this->EvSubsidyCommands->updateEvSubsidy($validate->validated(), $id);

        if (isset($data['status']) && $data['status'] == false) {
            return response()->json([
                'status' => false,
                'message' => $data['message'],
                'errors' => $data['errors'],
            ], 400);
        }

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengupdate data EV Subsidi',
            'data' => $data['data'],
        ]);
    }

    public function delete(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'required|integer',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'message' => 'Invalid request',
                'errors' => $validate->errors(),
            ], 400);
        }

        $data = $this->EvSubsidyCommands->deleteEvSubsidy($validate->validated());

        if (isset($data['status']) && $data['status'] == false) {
            return response()->json([
                'status' => false,
                'message' => $data['message'],
                'errors' => $data['errors'],
            ], 400);
        }

        return response()->json([
            'status' => true,
            'message' => 'Berhasil menghapus data EV Subsidi',
        ]);
    }

    public function approve(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'ev_subsidy_id' => 'required|exists:customer_ev_subsidy,id',
            'status' => 'required|in:1,0',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'message' => 'Invalid request',
                'errors' => $validate->errors(),
            ], 400);
        }

        $data = $this->EvSubsidyCommands->updateStatus($validate->validated());

        if (isset($data['status']) && $data['status'] == false) {
            return response()->json([
                'status' => false,
                'message' => $data['message'],
                'errors' => $data['errors'],
            ], 400);
        }

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengupdate status EV Subsidi',
            'data' => $data['data'],
        ]);
    }

    // ================== Buyer
    public function checkIdentity(Request $request)
    {
        return response()->json(null, 200);

        $validate = Validator::make($request->all(), [
            "nik" => "required|string",
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'status_code' => '99',
                'message' => 'Invalid request',
                'errors' => $validate->errors(),
            ], 400);
        }

        $data = $this->EvSubsidyQueries->checkIdentity($validate->validated());

        if (isset($data['status']) && $data['status'] == false) {
            return response()->json([
                'status' => false,
                'status_code' => $data['status_code'],
                'message' => $data['message'],
                'errors' => $data['errors'],
            ], 400);
        }

        return response()->json([
            'status' => true,
            'status_code' => $data['status_code'],
            'message' => 'Berhasil melakukan pengecekan identitas',
            'data' => $data['data'],
        ]);
    }

    // webview
    public function webview()
    {
        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengambil data',
            'data' => $this->EvSubsidyQueries->getWebviewData(),
        ]);
    }
}

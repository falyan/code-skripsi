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

    function list() {
        $data = $this->EvSubsidyQueries->getData();

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengambil data EV Subsidi',
            'data' => $data,
        ]);
    }

    public function create(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'product_ids' => 'required|array',
            'product_ids.*' => 'required|integer',
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

    // checkIdentity
    public function checkIdentity(Request $request)
    {
        $validate = Validator::make($request->all(), [
            "nik" => "required|string",
            "id_pel" => "required|string",
        ]);

        if ($validate->fails()) {
            return response()->json([
                'message' => 'Invalid request',
                'errors' => $validate->errors(),
            ], 400);
        }

        $key_pln = $request->header('Key-PLN');
        if (is_null($key_pln) || $key_pln == '') {
            return response()->json([
                'status' => false,
                'message' => 'Key-PLN tidak boleh kosong',
            ], 400);
        }

        $data = $this->EvSubsidyQueries->checkIdentity($validate->validated(), $key_pln);

        if (isset($data['status']) && $data['status'] == false) {
            return response()->json([
                'status' => false,
                'message' => $data['message'],
                'errors' => $data['errors'],
            ], 400);
        }

        return response()->json([
            'status' => true,
            'message' => 'Berhasil melakukan pengecekan identitas',
            'data' => $data['data'],
        ]);
    }
}

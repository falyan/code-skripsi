<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\ProductEvSubsidy\ProductEvSubsidyCommands;
use App\Http\Services\ProductEvSubsidy\ProductEvSubsidyQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EvSubsidyController extends Controller
{
    protected $productEvSubsidyQueries, $productEvSubsidyCommands;

    public function __construct()
    {
        $this->productEvSubsidyQueries = new ProductEvSubsidyQueries();
        $this->productEvSubsidyCommands = new ProductEvSubsidyCommands();
    }

    function list() {
        $data = $this->productEvSubsidyQueries->getData();

        return response()->json([
            'status' => true,
            'message' => 'Successfully retrieved EV Subsidy',
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

        $data = $this->productEvSubsidyCommands->create($validate->validated());

        if (isset($data['status']) && $data['status'] == false) {
            return response()->json([
                'status' => false,
                'message' => $data['message'],
                'errors' => $data['errors'],
            ], 400);
        }

        return response()->json([
            'status' => true,
            'message' => 'Successfully created EV Subsidy',
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

        $data = $this->productEvSubsidyCommands->updateEvSubsidy($validate->validated(), $id);

        if (isset($data['status']) && $data['status'] == false) {
            return response()->json([
                'status' => false,
                'message' => $data['message'],
                'errors' => $data['errors'],
            ], 400);
        }

        return response()->json([
            'status' => true,
            'message' => 'Successfully updated EV Subsidy',
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

        $data = $this->productEvSubsidyCommands->deleteEvSubsidy($validate->validated());

        if (isset($data['status']) && $data['status'] == false) {
            return response()->json([
                'status' => false,
                'message' => $data['message'],
                'errors' => $data['errors'],
            ], 400);
        }

        return response()->json([
            'status' => true,
            'message' => 'Successfully deleted EV Subsidy',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Report\ReportCommands;
use App\Http\Services\Report\ReportQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    protected $reportCommands, $reportQueries;

    public function __construct()
    {
        $this->reportCommands = new ReportCommands();
        $this->reportQueries = new ReportQueries();
    }

    public function getMasterData(Request $request)
    {
        try {
            return $this->reportQueries->getMasterData();
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    public function createReport(Request $request)
    {
        try {
            $rules = [
                'product_id' => 'required|exists:product,id',
                'review_id' => 'nullable|exists:review,id',
                'product_discussion_master_id' => 'nullable|exists:product_discussion_master,id',
                'product_discussion_response_id' => 'nullable|exists:product_discussion_response,id',
                'reason' => 'required',
                'description' => 'nullable',
            ];

            $validator = Validator::make($request->all(), $rules, [
                'required' => ':attribute tidak boleh kosong',
                'exists' => ':attribute tidak ditemukan',
            ]);

            if ($validator->fails()) {
                $errors = collect();
                foreach ($validator->errors()->getMessages() as $key => $value) {
                    foreach ($value as $error) {
                        $errors->push($error);
                    }
                }
                return $this->respondValidationError($errors, 'Validation Error!');
            }

            return $this->reportCommands->createReport($request);

        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }
}

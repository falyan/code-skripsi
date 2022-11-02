<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Report\ReportCommands;
use App\Http\Services\Report\ReportQueries;
use Illuminate\Http\Request;

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
        //
    }
}

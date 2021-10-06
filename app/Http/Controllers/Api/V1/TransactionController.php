<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Transaction\TransactionCommands;
use App\Http\Services\Transaction\TransactionQueries;
use Exception, Input;

class TransactionController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->transactionQueries = new TransactionQueries();
        $this->transactionCommand = new TransactionCommands();
    }

    public function index()
    {
        return request()->all();
        try {
            $data = $this->transactionQueries->getTransaction();
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }
}

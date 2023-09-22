<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Installment\InstallmentCommands;
use App\Http\Services\Installment\InstallmentQueries;
use Illuminate\Http\Request;

class InstallmentController extends Controller
{
    protected $installmentQueries, $installmentCommands;

    public function __construct()
    {
        $this->installmentQueries = new InstallmentQueries();
        $this->installmentCommands = new InstallmentCommands();
    }

    public function getListInstallmentProvider(Request $request)
    {
        $price = $request->price ?? null;
        $type = $request->type ?? null;

        $installmentProvider = $this->installmentQueries->getListInstallmentProvider($price, $type);

        return $this->respondWithData($installmentProvider, 'Success get list installment provider');
    }

    public function getTenorInstallmentByProvider(Request $request)
    {
        $price = $request->price ?? null;
        $providerId = $request->provider_id ?? null;

        $installmentProvider = $this->installmentQueries->getTenorInstallmentByProvider($price, $providerId);

        return $this->respondWithData($installmentProvider, 'Success get list installment provider');
    }

    public function calculateInstallment(Request $request)
    {
        $providerId = $request->provider_id ?? null;
        $price = $request->price ?? null;
        $tenor = $request->tenor ?? null;

        $installmentProvider = $this->installmentQueries->calculateInstallment($providerId, $tenor, $price);

        return $this->respondWithData($installmentProvider, 'Success get tenor installment by provider');
    }
}

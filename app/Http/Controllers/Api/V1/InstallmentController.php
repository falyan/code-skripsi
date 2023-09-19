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

        $installmentProvider = $this->installmentQueries->getListInstallmentProvider($price);

        return $this->respondWithData($installmentProvider, 'Success get list installment provider');
    }

    public function getTenorInstallmentByProvider(Request $request)
    {
        $providerId = $request->provider_id ?? null;
        $price = $request->price ?? null;

        $installmentProvider = $this->installmentQueries->getTenorInstallmentByProvider($providerId, $price);

        return $this->respondWithData($installmentProvider, 'Success get tenor installment by provider');
    }
}

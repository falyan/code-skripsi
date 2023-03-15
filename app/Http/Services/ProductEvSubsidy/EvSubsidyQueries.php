<?php

namespace App\Http\Services\ProductEvSubsidy;

use App\Http\Services\Manager\EvSubsidyManager;
use App\Http\Services\Service;
use App\Models\CustomerEVSubsidy;
use App\Models\ProductEvSubsidy;

class EvSubsidyQueries extends Service
{
    protected $EvSubsidyCommands, $EvSubsidyManager;

    public function __construct()
    {
        $this->EvSubsidyCommands = new EvSubsidyCommands();
        $this->EvSubsidyManager = new EvSubsidyManager();
    }

    public function getData()
    {
        $ev_products = ProductEvSubsidy::where([
            'merchant_id' => auth()->user()->merchant_id,
        ])->with('product')->get();

        return $ev_products;
    }

    // checkIdentity
    public function checkIdentity($request, $token)
    {
        $nik = $request['nik'];
        $id_pel = $request['id_pel'];

        $customer = CustomerEVSubsidy::where([
            'customer_nik' => $nik,
        ])->first();

        if ($customer) {
            return [
                'status' => false,
                'message' => 'Customer Subsidi sudah ada',
                'errors' => $customer,
            ];
        }

        $checkNik = $this->EvSubsidyManager->checkNik($nik, $token);
        $checkIdPel = $this->EvSubsidyManager->checkIdPel($id_pel, $token);

        if (!isset($checkNik['data']) || !isset($checkIdPel['data'])) {
            return [
                'status' => false,
                'message' => 'Customer Subsidi tidak ditemukan',
                'errors' => [
                    'nik' => isset($checkNik['data']) ? '' : 'Nik tidak ditemukan',
                    'id_pel' => isset($checkIdPel['data']) ? '' : 'Id Pelanggan tidak ditemukan',
                ],
            ];
        }

        if ($checkIdPel['data']['energy'] < 450 || $checkIdPel['data']['energy'] > 900) {
            return [
                'status' => false,
                'message' => 'Customer Subsidi tidak ditemukan',
                'errors' => [
                    'id_pel' => 'Id Pelanggan di bawah 450 atau di atas 900',
                ],
            ];
        }

        return [
            'status' => true,
            'message' => 'Customer Subsidi ditemukan',
            'data' => [
                'nik' => $nik,
                'id_pel' => $checkIdPel['data']['meter_id'],
                'daya' => $checkIdPel['data']['energy'],
            ],
        ];
    }
}

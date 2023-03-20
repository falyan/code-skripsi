<?php

namespace App\Http\Services\ProductEvSubsidy;

use App\Http\Services\Manager\EvSubsidyManager;
use App\Http\Services\Service;
use App\Models\CustomerEVSubsidy;
use App\Models\MasterData;
use App\Models\ProductEvSubsidy;

class EvSubsidyQueries extends Service
{
    protected $EvSubsidyCommands, $EvSubsidyManager;

    public function __construct()
    {
        $this->EvSubsidyCommands = new EvSubsidyCommands();
        $this->EvSubsidyManager = new EvSubsidyManager();
    }

    public function getData($keyword = null, $limit = 10)
    {
        $ev_products = ProductEvSubsidy::where([
            'merchant_id' => auth()->user()->merchant_id,
        ])->with(['product', 'product.product_photo']);

        if ($keyword) {
            $ev_products->whereHas('product', function ($query) use ($keyword) {
                $query->where('name', 'ilike', '%' . $keyword . '%');
            });
        }

        $ev_products = $ev_products->paginate($limit);

        return $ev_products;
    }

    // checkIdentity
    public function checkIdentity($request)
    {
        $nik = $request['nik'];

        $customers = CustomerEVSubsidy::where([
            'customer_nik' => $nik,
        ])->get();

        if (count($customers) > 0) {
            foreach ($customers as $customer) {
                if ($customer->status_approval == 1 || is_null($customer->status_approval)) {
                    return [
                        'status' => false,
                        'status_code' => '01',
                        'message' => 'Nik Subsidi sudah terdaftar',
                        'errors' => [
                            'nik' => 'Nik sudah terdaftar',
                        ],
                    ];
                }
            }
        }

        $checkNik = $this->EvSubsidyManager->checkNik($nik);

        if (!isset($checkNik['response']) || $checkNik['response'] != 'OK') {
            return [
                'status' => false,
                'status_code' => '02',
                'message' => 'Nik Subsidi tidak ditemukan',
                'errors' => 'Nik tidak ditemukan',
            ];
        }

        return [
            'status' => true,
            'status_code' => '00',
            'message' => 'Customer Subsidi ditemukan',
            'data' => [
                'nik' => $nik,
            ],
        ];
    }

    public function getWebviewData()
    {
        $master_data = MasterData::select('type', 'key', 'value_type', 'value', 'photo_url')
            ->where([
                'key' => 'ev_subsidy_webview',
            ])
            ->first();

        return $master_data;
    }
}

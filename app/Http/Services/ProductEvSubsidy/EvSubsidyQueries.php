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
        $id_pel = $request['id_pel'];
        $token = $request['key_pln'];

        $customers = CustomerEVSubsidy::where([
            'customer_nik' => $nik,
        ])->get();

        if ($customers) {
            foreach ($customers as $customer) {
                if ($customer->status_approval == 1) {
                    return [
                        'status' => false,
                        'status_code' => '01',
                        'message' => 'Customer Subsidi sudah terdaftar',
                        'errors' => [
                            'nik' => 'Nik sudah terdaftar',
                        ],
                    ];
                }
            }
        }

        $checkNik = $this->EvSubsidyManager->checkNik($nik, $token);
        $checkIdPel = $this->EvSubsidyManager->checkIdPel($id_pel, $token);

        if (!isset($checkNik['data']) || !isset($checkIdPel['data'])) {
            return [
                'status' => false,
                'status_code' => '02',
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
                'status_code' => '03',
                'message' => 'Id Pelanggan tidak sesuai',
                'errors' => [
                    'id_pel' => 'Id Pelanggan di bawah 450 atau di atas 900',
                ],
            ];
        }

        return [
            'status' => true,
            'status_code' => '00',
            'message' => 'Customer Subsidi ditemukan',
            'data' => [
                'nik' => $nik,
                'id_pel' => $checkIdPel['data']['meter_id'],
                'daya' => $checkIdPel['data']['energy'],
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

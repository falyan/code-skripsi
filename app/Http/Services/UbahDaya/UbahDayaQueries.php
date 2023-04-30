<?php

namespace App\Http\Services\UbahDaya;

use App\Http\Services\Service;
use App\Models\UbahDayaMaster;

class UbahDayaQueries extends Service
{
    public function getVoucher($search, $limit)
    {
        $data = UbahDayaMaster::with(['pregenerates'])
        ->where([
            'status' => 1,
        ])
        ->paginate($limit);

        return $data;
    }

    public function getVoucherById($id)
    {
        $data = UbahDayaMaster::with(['pregenerates'])
        ->where([
            'id' => $id,
            'status' => 1,
        ])
        ->first();

        return $data;
    }
}

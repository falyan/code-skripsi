<?php

namespace App\Http\Services\UbahDaya;

use App\Http\Services\Service;
use App\Models\UbahDayaMaster;

class UbahDayaCommands extends Service
{
    public function createVoucher($data)
    {
        $voucher = UbahDayaMaster::create($data);

        return $voucher;
    }
}

<?php

namespace App\Http\Services\ManualTransfer;

use App\Http\Services\Service;
use App\Models\MarketplacePayment;

class ManualTransferQueries extends Service
{
    public function getGatheway()
    {
        $manual = MarketplacePayment::all();

        return $manual;
    }

    public function getGathewayByCode($code)
    {
        $manual = MarketplacePayment::where('code', $code)->first();

        return $manual;
    }
}

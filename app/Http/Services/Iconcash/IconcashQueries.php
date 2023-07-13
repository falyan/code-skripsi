<?php

namespace App\Http\Services\Iconcash;

use App\Http\Services\Service;
use App\Models\AgentMasterPsp;

class IconcashQueries extends Service
{
    public static function getDataExample()
    {
        //Queries here
    }

    public function getGatewayAgentPayment()
    {
        $data = AgentMasterPsp::where('status', 1)->get();

        return $data;
    }
}

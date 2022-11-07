<?php

namespace App\Http\Services\Report;

use App\Http\Services\Service;
use App\Models\MasterData;

class ReportQueries extends Service
{
    public function getMasterData()
    {
        $reason = MasterData::where('type', 'report')->select('id', 'key', 'value')->orderBy('created_at', 'asc')->get();

        if (!$reason) {
            $response['status'] = false;
            $response['message'] = 'Data tidak ditemukan';
            return $response;
        }

        $response['status'] = true;
        $response['message'] = 'Data berhasil ditemukan';
        $response['data'] = $reason;

        return $response;
    }

}

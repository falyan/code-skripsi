<?php

namespace App\Http\Services\Version;

use App\Models\Version;

class VersionQueries
{

    public function getVersionStatus()
    {
        $data = Version::where('status', 1)->first();

        if ($data == null) {
            $response['success'] = false;
            $response['message'] = 'Versi aplikasi tidak ditemukan.';
            $response['data'] = null;
            return $response;
        }
        $response['success'] = true;
        $response['message'] = 'Versi aplikasi ditemukan.';
        $response['data'] = $data;
        return $response;
    }
}

<?php

namespace App\Http\Services\Banner;

use App\Http\Services\Service;

class BannerQueries extends Service
{
    public function getFlashPopup()
    {
        $result = static::$banner_url;
        return [
            'sukses' => true,
            'message' => !empty($result) ? 'Berhasil mendapatkan data banner' : 'Data banner tidak ada',
            'data' => ['url_path' => $result ?? '']
        ];
    }
}

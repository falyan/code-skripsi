<?php

namespace App\Http\Services\Banner;

use App\Http\Services\Service;

class BannerQueries extends Service
{
    static $banner_url;

    static function init()
    {
        self::$banner_url = config('credentials.banner.url_flash_popup');
    }

    public function getFlashPopup()
    {
        $result = self::$banner_url ?? '';
        
        return [
            'sukses' => true,
            'message' => !empty($result) ? 'Berhasil mendapatkan data banner' : 'Data banner tidak ada',
            'data' => ['url_path' => $result]
        ];
    }
}

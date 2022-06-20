<?php

namespace App\Http\Services\Banner;

use App\Http\Services\Service;
use App\Models\Banner;

class BannerQueries extends Service
{
    public function getFlashPopup()
    {
        $result = static::$banner_url;

        return [
            'sukses' => true,
            'message' => !empty($result) ? 'Berhasil mendapatkan data banner' : 'Data banner tidak ada',
            'data' => [
                'url_path' => $result ?? '',
                'deeplink' => '/mkp'
            ]
        ];
    }

    public function getAllBanner()
    {
        $data = Banner::all();

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data banner';
        $response['data'] = $data;

        return $response;
    }

    public function getBannerByType($type)
    {
        $data = Banner::where('type', $type)
            ->where('status', 1)
            ->orderBy('id', 'desc')
            ->get();

        $response['success'] = true;
        $response['message'] ='Berhasil mendapatkan data banner';
        $response['data'] = $data;

        return $response;
    }

    // public function getBannerAgent()
    // {
    //     $data = Banner::where('type', 'agent')
    //         ->where('status', 1)
    //         ->where('is_video', false)
    //         ->orderBy('id', 'desc')
    //         ->get();

    //     $response['success'] = true;
    //     $response['message'] = !empty($data) ? 'Berhasil mendapatkan data banner' : 'Data banner tidak ada';
    //     $response['data'] = $data ?? '';

    //     return $response;
    // }

    // public function getBannerHomepage()
    // {
    //     $data = Banner::where('type', 'home_page')
    //         ->where('status', 1)
    //         ->where('is_video', false)
    //         ->orderBy('id', 'desc')
    //         ->get();

    //     $response['success'] = true;
    //     $response['message'] = !empty($data) ? 'Berhasil mendapatkan data banner' : 'Data banner tidak ada';
    //     $response['data'] = $data ?? '';

    //     return $response;
    // }
}

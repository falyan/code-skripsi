<?php

namespace App\Http\Services\Banner;

use App\Http\Services\Service;
use App\Models\Banner;
use App\Models\MasterData;

class BannerQueries extends Service
{

    // Query Get All Banner
    public function getAllBanner()
    {
        $data = Banner::where('status', 1)->get();

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data banner';
        $response['data'] = $data;

        return $response;
    }

    //Query Get Banner by Type
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

    public function getFlashPopup()
    {
        $master_data = MasterData::whereIn('key', ['banner_url_path', 'banner_deeplink'])->get();

        $url_path = collect($master_data)->where('key', 'banner_url_path')->first();
        $deeplink = collect($master_data)->where('key', 'banner_deeplink')->first();

        return [
            'sukses' => true,
            'message' => !empty($url_path) ? 'Berhasil mendapatkan data banner' : 'Data banner tidak ada',
            'data' => [
                'url_path' => $url_path->value ?? '',
                'deeplink' => $deeplink->value ?? ''
            ]
        ];
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

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
        $response['message'] = 'Berhasil mendapatkan data banner';
        $response['data'] = $data;

        return $response;
    }

    public function getHomeBannerByType($type)
    {
        $data = Banner::where('type', $type)
            ->where('status', 1)
            ->orderBy('id', 'desc')
            ->get();

        if ($data->isEmpty()) {
            $response['success'] = false;
            $response['message'] = 'Data banner tidak ada';
            $response['data'] = $data;
            return $response;
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data banner homepage';
        $response['data'] = $data;

        return $response;
    }

    public function getFlashPopup()
    {
        $master_data = MasterData::with('parent')->where('key', 'like', 'banner_url_path%')->inRandomOrder()->first();

        $url_path = $master_data;
        $deeplink = $master_data->parent;

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

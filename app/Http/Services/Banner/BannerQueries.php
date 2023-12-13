<?php

namespace App\Http\Services\Banner;

use App\Http\Services\Service;
use App\Models\Banner;
use App\Models\PopupBanners;

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
        $popup_banner = PopupBanners::where('status', true)->inRandomOrder()->first();

        return [
            'sukses' => true,
            'message' => !empty($url_path) ? 'Berhasil mendapatkan data banner' : 'Data banner tidak ada',
            'data' => [
                'url_path' => env('SAURON_CDN_ENDPOINT') . '/file/load/' . $popup_banner->image_url,
                'deeplink' => $popup_banner->content_url,
                'analytics_tracker' => $popup_banner->analytics_tracker,
            ],
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

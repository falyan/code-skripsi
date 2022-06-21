<?php

namespace App\Http\Services\Banner;

use App\Http\Services\Service;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BannerCommands extends Service
{
    // //Create or Store Banner Image
    // public static function createBanner($data)
    // {
    //     try {
    //         DB::beginTransaction();

    //         $banner_image = Banner::create([
    //             'url' => $data->url,
    //             'is_video' => false,
    //             'type' => $data->type,
    //             'status' => 1,
    //             'created_by' => $data->full_name,
    //             'updated_by' => $data->full_name,
    //         ]);

    //             $response['success'] = true;
    //             $response['message'] = 'Banner berhasil ditambahkan!';
    //             $response['data'] = $banner_image;

    //             DB::commit();
    //             return $response;
    //     } catch(Exception $e) {
    //         DB::rollBack();
    //         throw new Exception($e->getMessage(), $e->getCode());
    //     }
    // }

    // //Delete Banner Image
    // public static function deleteBanner($banner_id)
    // {
    //     try {
    //         DB::beginTransaction();

    //         $delete_banner = Banner::where('id', $banner_id)->delete();

    //         $response['success'] = true;
    //         $response['message'] = 'Banner berhasil dihapus!';
    //         $response['data'] = $delete_banner;

    //         DB::commit();
    //         return $response;
    //     } catch(Exception $e) {
    //         DB::rollBack();
    //         throw new Exception($e->getMessage(), $e->getCode());
    //     }
    // }
}

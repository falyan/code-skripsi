<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Banner\BannerCommands;
use App\Http\Services\Banner\BannerQueries;
use Illuminate\Support\Facades\Validator;
use App\Models\Banner;
use Exception;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    protected $bannerQueries, $bannerCommmands;
    public function __construct()
    {
        $this->bannerQueries = new BannerQueries();
        $this->bannerCommands = new BannerCommands();
    }

    // Get Semua Tipe Banner
    public function getAllBanner()
    {
        try {
            $data = $this->bannerQueries->getAllBanner();
            return $this->respondWithData($data['data'], $data['message']);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    //Get Banner berdasarkan tipe
    public function getBannerByType($type)
    {
        try {
            $data = $this->bannerQueries->getBannerByType($type);
            return $this->respondWithData($data['data'], $data['message']);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    // //Create Banner
    // public function createBanner(Request $request)
    // {
    //     try {
    //         $rules = [
    //             'url' => 'required',
    //             'type' => 'required',
    //         ];

    //         $validator = Validator::make(request()->all(), $rules, [
    //             'required' => ':attribute diperlukan.',
    //         ]);

    //         if ($validator->fails()) {
    //             $errors = collect();
    //             foreach ($validator->errors()->getMessages() as $key => $value) {
    //                 foreach ($value as $error) {
    //                     $errors->push($error);
    //                 }
    //             }

    //             return $this->respondValidationError($errors, 'Validation Error!');
    //         }

    //         $request['full_name'] = Auth::user()->full_name;

    //         return $this->bannerCommands->createBanner($request);

    //     } catch(Exception $e) {
    //         return $this->respondErrorException($e, request());
    //     }
    // }

    // //Delete Banner
    // public function deleteBanner($banner_id)
    // {
    //     try {
    //         return $this->bannerCommands->deleteBanner($banner_id);
    //     } catch(Exception $e) {
    //         return $this->respondErrorException($e, request());
    //     }
    // }

    // public function getFlashPopup()
    // {
    //     try {
    //         $data = $this->bannerQueries->getFlashPopup();
    //         return $this->respondWithData($data['data'], $data['message']);
    //     } catch (Exception $e) {
    //         return $this->respondErrorException($e, request());
    //     }
    // }

    // public function getBannerAgent()
    // {
    //     try {
    //         $data = $this->bannerQueries->getBannerAgent();
    //         return $this->respondWithData($data['data'], $data['message']);
    //     } catch (Exception $e) {
    //         return $this->respondErrorException($e, request());
    //     }
    // }

    // public function getBannerHomepage()
    // {
    //     try {
    //         $data = $this->bannerQueries->getBannerHomepage();
    //         return $this->respondWithData($data['data'], $data['message']);
    //     } catch (Exception $e) {
    //         return $this->respondErrorException($e, request());
    //     }
    // }
}

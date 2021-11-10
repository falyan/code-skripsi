<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Banner\BannerCommands;
use App\Http\Services\Banner\BannerQueries;
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
    public function getFlashPopup()
    {
        try {
            $data = $this->bannerQueries->getFlashPopup();
            return $this->respondWithData($data['data'], $data['message']);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }
}

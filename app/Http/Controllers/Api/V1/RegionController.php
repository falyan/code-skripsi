<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Region\RegionQueries;

class RegionController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->regionQueries = new RegionQueries();
    }

    //Search Region
    public function searchDistrict($keyword, $limit = 10){
        try {
            return $this->regionQueries->searchDistrict($keyword, $limit);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }
}

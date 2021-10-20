<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Region\RegionQueries;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
    public function searchDistrict(Request $request){
        try {
            $validator = Validator::make(request()->all(), [
                'keyword' => 'required|min:3',
                'limit' => 'nullable'
            ]);
    
            if ($validator->fails()) {
                return $this->respondValidationError($validator->messages()->get('*'), 'Validation Error!');
            }
            
            $keyword = $request->keyword;
            $limit = $request->limit ?? 10;

            return $this->regionQueries->searchDistrict($keyword, $limit);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }
}

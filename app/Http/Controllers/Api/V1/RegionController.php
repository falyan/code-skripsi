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
    public function searchDistrict(Request $request)
    {
        try {
            $validator = Validator::make(request()->all(), [
                'keyword' => 'required|min:3',
                'limit' => 'nullable'
            ], [
                'required' => ':attribute diperlukan.',
                'min' => 'panjang :attribute minimum :min karakter.',
            ]);

            if ($validator->fails()) {
                $errors = collect();
                foreach ($validator->errors()->getMessages() as $key => $value) {
                    foreach ($value as $error) {
                        $errors->push($error);
                    }
                }
                return $this->respondValidationError($errors, 'Validation Error!');
            }

            $keyword = $request->keyword;
            $limit = $request->limit ?? 10;

            return $this->regionQueries->searchDistrict($keyword, $limit);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }


    public function searchProvince(Request $request){
        try {
            $validator = Validator::make(request()->all(), [
                'keyword' => 'nullable|min:3',
                'limit' => 'nullable'
            ], [
                'required' => ':attribute diperlukan.',
                'min' => 'panjang :attribute minimum :min karakter.',
            ]);

            if ($validator->fails()) {
                $errors = collect();
                foreach ($validator->errors()->getMessages() as $key => $value) {
                    foreach ($value as $error) {
                        $errors->push($error);
                    }
                }
                return $this->respondValidationError($errors, 'Validation Error!');
            }

            $limit = $request['limit'] ?? 10;
            $keyword = $request['keyword'] ?? null;

            return $this->regionQueries->searchProvince($keyword, $limit);
        }catch (Exception $e){
            return $this->respondErrorException($e, request());
        }
    }

    public function searchCity(Request $request){
        try {
            $validator = Validator::make(request()->all(), [
                'province_id' => 'required',
                'keyword' => 'nullable|min:3',
                'limit' => 'nullable',
            ], [
                'required' => ':attribute diperlukan.',
                'min' => 'panjang :attribute minimum :min karakter.',
            ]);

            if ($validator->fails()) {
                $errors = collect();
                foreach ($validator->errors()->getMessages() as $key => $value) {
                    foreach ($value as $error) {
                        $errors->push($error);
                    }
                }
                return $this->respondValidationError($errors, 'Validation Error!');
            }

            $province_id = $request['province_id'];
            $limit = $request['limit'] ?? 10;
            $keyword = $request['keyword'] ?? null;

            return $this->regionQueries->searchCity($province_id, $keyword, $limit);
        }catch (Exception $e){
            return $this->respondErrorException($e, request());
        }
    }
}

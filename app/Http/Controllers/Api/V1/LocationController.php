<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\District;
use App\Models\Province;
use App\Models\Subdistrict;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{
    public function getProvince()
    {
        $provinces = Province::select('id', 'name')->get();
        return response()->json([
            'status' => 'success',
            'data' => $provinces
        ]);
    }

    public function getCity($id)
    {
        $cities = City::select('id', 'name')->where('province_id', $id)->get();
        return response()->json([
            'status' => 'success',
            'data' => $cities
        ]);
    }

    public function getDistrict($id)
    {
        $districts = District::select('id', 'name')->where('city_id', $id)->get();
        return response()->json([
            'status' => 'success',
            'data' => $districts
        ]);
    }

    public function getSubDistrict($id)
    {
        $subDistricts = Subdistrict::where('district_id', $id)->get();
        return response()->json([
            'status' => 'success',
            'data' => $subDistricts
        ]);
    }

    public function getSubDistrictWithLatLng()
    {
        $validator = Validator::make(request()->all(), [
            'latitude' => 'required',
            'longitude' => 'required'
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

        // SELECT
        //         v.id,
        //         v.name,
        //         v.latitude,
        //         v.longitude,
        //         d.id AS district_id,
        //         d.name AS district_name,
        //         c.id AS city_id,
        //         c.name AS city_name,
        //         p.id AS province_id,
        //         p.name AS province_name,
        //         ST_DISTANCE(POINT(${lng}, ${lat}), POINT(v.longitude, v.latitude)) AS distance
        //     FROM
        //         subdistrict v
        //     JOIN
        //         district d ON d.id = v.district_id
        //     JOIN
        //         city c ON c.id = d.city_id
        //     JOIN
        //         province p ON p.id = c.province_id
        //     ORDER BY
        //         distance
        //     LIMIT 1;

        $lat = request('latitude');
        $lng = request('longitude');

        // $data = DB::table('subdistrict AS v')
        //     ->select(
        //         'v.id',
        //         'v.name',
        //         'v.latitude',
        //         'v.longitude',
        //         DB::raw('ST_DISTANCE(POINT(?, ?), POINT(v.longitude, v.latitude)) AS distance')
        //     )
        //     ->orderBy('distance')
        //     ->limit(1)
        //     ->setBindings([$lng, $lat]) // Bind the values for ? placeholders
        //     ->get();

        $data = Subdistrict::selectRaw(
                'subdistrict.id,
                subdistrict.name,
                subdistrict.latitude,
                subdistrict.longitude,
                ST_DISTANCE(POINT(?, ?), POINT(subdistrict.longitude, subdistrict.latitude)) AS distance',
                [$lng, $lat]
            )
            ->orderBy('distance')
            ->limit(1)
            ->get();

        if (!$data) {
            return $this->respondValidationError(null, 'Kelurahan/Desa tidak ditemukan.');
        }

        return $this->respondWithData($data, 'Data berhasil didapatkan.');
    }
}

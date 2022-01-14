<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\TestDrive\TestDriveCommands;
use App\Http\Services\TestDrive\TestDriveQueries;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TestDriveController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $testDriveCommands, $testDriveQueries;
    public function __construct()
    {
        $this->testDriveCommands = new TestDriveCommands();
        $this->testDriveQueries = new TestDriveQueries();
    }

    public function getEVProducts(Request $request)
    {
        try {
            $page = $request->page ?? 1;
            $data = $this->testDriveQueries->getEVProducts($page);

            if ($data['total'] > 0) {
                return $this->respondWithData($data, 'Berhasil mendapatkan data EV Produk');
            } else {
                return $this->respondWithResult(false, 'Data EV Produk belum tersedia', 400);
            }
        } catch (Exception $e) {
            return $this->respondWithData($e, 'Error', 400);
        }
    }

    public function create(Request $request)
    {
        try {
            $rules = [
                'title' => 'required|min:3',
                'area_name' => 'required|min:3',
                'address' => 'required|min:5',
                'city_id' => 'required|exists:city,id',
                'latitude' => 'required',
                'longitude' => 'required',
                'start_date' => 'required',
                'end_date' => 'required',
                'start_time' => 'required',
                'end_time' => 'required',
                'pic_name' => 'required',
                'pic_phone' => 'required|digits_between:8,14',
                'pic_email' => 'required|email',
                'max_daily_quota' => 'required|integer',
                'product_ids' => 'required|array|min:1',
                'product_ids.*' => 'required|exists:product,id',
            ];

            $validator = Validator::make($request->all(), $rules, [
                'product_ids.required' => 'harus memilih minimal 1 produk.',
                'product_ids.*.required' => 'harus memilih minimal 1 produk.',
                'product_ids.*.exists' => 'produk yang dipilih tidak valid.',
                'latitude.required' => 'koordinat lokasi diperlukan (latitude).',
                'longitude.required' => 'koordinat lokasi diperlukan (longitude).',
                'required' => ':attribute diperlukan.',
                'digits_between' => 'panjang :attribute harus diantara :min dan :max karakter.',
                'min' => 'panjang :attribute minimum :min karakter.',
                'integer' => ':attribute harus menggunakan angka.',
                'email' => ':attribute harus menggunakan email valid.',
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

            DB::beginTransaction();
            $data = $this->testDriveCommands->createEvent($request);
            if ($data) {
                DB::commit();
                return $this->respondWithResult(true, 'Event Test Drive baru berhasil dibuat');
            } else {
                DB::rollBack();
                return $this->respondWithResult(false, 'Event Test Drive gagal dibuat', 400);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respondWithData($e, 'Error', 400);
        }
    }

    public function getDetail($id)
    {
        try {
            $data = $this->testDriveQueries->getDetailEvent($id);
            if ($data) {
                return $this->respondWithData($data, 'Berhasil mendapatkan detail event');
            }else {
                return $this->respondWithResult(false, 'Terjadi kesalahan saat memuat data', 400);;
            }
        } catch (Exception $e) {
            return $this->respondWithData($e, 'Error', 400);
        }
    }

    public function getHistoryBySeller(Request $request)
    {
        try {
            $filter = $request->filter ?? [];
            $sortby = $request->sortby ?? null;
            $page = $request->page ?? 1;

            $data = $this->testDriveQueries->getAllEvent(Auth::user()->merchant_id, $filter, $sortby, $page);
            if ($data['total'] > 0) {
                return $this->respondWithData($data, 'Berhasil mendapatkan data Event Test Drive');
            } else {
                return $this->respondWithResult(false, 'Data Event Test Drive belum tersedia', 400);
            }
        } catch (Exception $e) {
            return $this->respondWithData($e, 'Error', 400);
        }
    }
}

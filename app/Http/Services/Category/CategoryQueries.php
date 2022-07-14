<?php

namespace App\Http\Services\Category;

use App\Http\Services\Service;
use App\Models\MasterData;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CategoryQueries extends Service
{
    public function findById($id)
    {
        $category = MasterData::where('type', 'product_category')->find($id);
        if (empty($category)) {
            $response['success'] = false;
            $response['message'] = 'Gagal mendapatkan data kategori!';
            return $response;
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data kategori!';
        $response['data'] = $category;
        return $response;
    }

    public function getAllCategory(){
        $category = MasterData::with(['child' => function($j) {
            $j->with(['child']);
        }])->where('type', 'product_category')->where('parent_id', null)->orderBy('updated_at', 'DESC')->get();
        if ($category->isEmpty()){
            $response['success'] = false;
            $response['message'] = 'Gagal mendapatkan data kategori!';
            return $response;
        }
        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data kategori!';
        $response['data'] = $category;
        return $response;
    }

    public function getThreeRandomCategory(){
        $category = MasterData::where('type', 'product_category')->where('parent_id', null)->inRandomOrder()->limit(3)->get();
        if ($category->isEmpty()){
            $response['success'] = false;
            $response['message'] = 'Gagal mendapatkan data kategori!';
            return $response;
        }
        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data kategori!';
        $response['data'] = $category;
        return $response;
    }

    public function getBasicCategory()
    {
        Log::info("T00001", [
            'path_url' => "category.basic",
            'query' => [],
            'body' => Carbon::now('Asia/Jakarta'),
            'response' => ''
        ]);
        $category = MasterData::where('type', 'product_category')->where('parent_id', null)->orderBy('updated_at', 'DESC')->get();

        if ($category->isEmpty()){
            $response['success'] = false;
            $response['message'] = 'Gagal mendapatkan data kategori!';
            return $response;
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data kategori!';
        $response['data'] = $category;
        return $response;
    }

    public function getParentCategory()
    {
        $category = MasterData::where('type', 'product_category')->where('parent_id', null)
            ->where('key', 'prodcat_electric_vehicle')
            ->whereHas('child')->with(['child' => function ($query) {
                $query->whereNotIn('key', ['prodcat_mobil_listrik', 'prodcat_motor_listrik', 'prodcat_sepeds_listrik']);
            }])
            ->orderBy('value')->get()->pluck('child');

        $parents = [];
        foreach ($category as $c) {
            foreach ($c as $child) {
                $parents[] = $child;
            }
        }

        if (empty($parents)){
            $response['success'] = false;
            $response['message'] = 'Gagal mendapatkan data parent kategori!';
            return $response;
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data parent kategori!';
        $response['data'] = collect($parents);
        return $response;
    }

    public function getChildCategory()
    {
        // $category = MasterData::where('type', 'product_category')->where('parent_id', '!=', null)
        //     ->whereHas('child')->with(['child'])
        //     ->orderBy('value')->get()->pluck('child');
        $category = MasterData::where('type', 'product_category')->where('parent_id', null)
            ->where('key', 'prodcat_electric_vehicle')
            ->whereHas('child')->with(['child' => function ($query) {
                $query->whereNotIn('key', ['prodcat_mobil_listrik', 'prodcat_motor_listrik', 'prodcat_sepeds_listrik']);
            }])
            ->orderBy('value')->get()->pluck('child');

        $childs = [];
        foreach ($category as $p) {
            foreach ($p as $parent) {
                foreach ($parent->child as $child) {
                    $childs[] = $child;
                }
            }
        }

        // $childs = [];
        // foreach ($category as $c) {
        //     foreach ($c as $child) {
        //         $childs[] = $child;
        //     }
        // }

        if (empty($childs)){
            $response['success'] = false;
            $response['message'] = 'Gagal mendapatkan data child kategori!';
            return $response;
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data child kategori!';
        $response['data'] = collect($childs);
        return $response;
    }
}

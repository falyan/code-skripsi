<?php

namespace App\Http\Services\Category;

use App\Models\MasterData;

class CategoryQueries{
    public function getAllCategory(){
        $category = MasterData::with(['child' => function($j)
        {$j->with(['child']);}])->where('type', 'product_category')->where('parent_id', null)->get();
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
}

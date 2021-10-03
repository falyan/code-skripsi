<?php

namespace App\Http\Services\Category;

use App\Models\MasterData;

class CategoryQueries{
    public function getAllCategory(){
        $category = MasterData::where('type', 'product_category')->get();
        if (!$category){
            $response['success'] = false;
            $response['message'] = 'Gagal mendapatkan data kategori!';
        }
        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data kategori!';
        $response['data'] = $category;
        return $response;
    }
}

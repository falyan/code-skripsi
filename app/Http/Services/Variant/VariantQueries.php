<?php

namespace App\Http\Services\Variant;

use App\Models\Product;
use App\Models\Variant;

class VariantQueries
{
    public function find($id)
    {
        $variant = Variant::with(['category'])->find($id);

        if (empty($variant)){
            $response = [
                'success' => false,
                'message' => 'Varian tidak ditemukan',
            ];

            return $response;
        }

        $response = [
            'success' => true,
            'message' => 'Berhasil mendapatkan data varian!',
            'data' => $variant,
        ];

        return $response;
    }

    public function getByCategory($category_id)
    {
        $variants = Variant::where('category_id', $category_id)->with(['variant_suggestions'])->get();

        if ($variants->isEmpty()){
            $response = [
                'success' => false,
                'message' => 'Tidak ada varian dengan kategori ini!',
            ];

            return $response;
        }

        $response = [
            'success' => true,
            'message' => 'Berhasil mendapatkan data varian!',
            'data' => $variants,
        ];

        return $response;
    }
}

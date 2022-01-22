<?php

namespace App\Http\Services\Variant;

use App\Models\MasterData;
use App\Models\Product;
use App\Models\Variant;
use App\Models\VariantValueProduct;

class VariantQueries
{
    public function find($id)
    {
        $variant = Variant::with(['category'])->find($id);

        if (empty($variant)) {
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
        $category = MasterData::where('type', 'product_category')->where('id', $category_id)->first();
        $variants = Variant::where('category_id', $category_id)->with(['master_variant', 'master_variant.option_variants'])->get();

        if (empty($category)) {
            $response = [
                'success' => false,
                'message' => 'Category tidak ditemukan!',
            ];

            return $response;
        }

        if ($variants->isEmpty()) {
            $response = [
                'success' => false,
                'message' => 'Gagal mendapatkan data varian!',
            ];

            return $response;
        }

        $response = [
            'success' => true,
            'message' => 'Berhasil mendapatkan data varian!',
            'category' => $category,
            'data' => $variants,
        ];

        return $response;
    }

    public static function detailVariantByProduct(string $variant_value_id)
    {
        $variant = VariantValueProduct::with(['variant_value', 'product', 'variant_stock'])
            ->where('variant_value_id', $variant_value_id)->first();

        if(!$variant)
            $response = [
                'success' => false,
                'message' => 'Gagal mendapatkan data varian!'
            ];
        else
            $response = [
                'success' => true,
                'message' => 'Berhasil mendapatkan data detail variant produk!',
                'data' => $variant
            ];

        return $response;
    }
}

<?php

namespace App\Http\Services\Pages;

use App\Models\Pages;

class PagesQueries
{
    public static function getPageType($type)
    {
        $page = Pages::where('page_type', $type)->first();

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page type Not Found!',
                'data' => []
            ], 400);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Data page berhasil ditampilkan',
                'data' => $page
            ], 200);
        }
    }
}

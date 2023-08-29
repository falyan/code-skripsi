<?php

namespace App\Http\Services\Pages;

use App\Http\Services\Service;
use App\Models\Pages;

class PagesQueries extends Service
{
    public static function termConditionPage()
    {
        $data = Pages::where('page_type', 'term_condition')->first();

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Page type Not Found!',
                'data' => [],
            ], 400);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Data page berhasil ditampilkan',
                'data' => $data,
            ], 200);
        }
    }

    public static function contactUsPage()
    {
        $data = Pages::where('page_type', 'contact_us')->first();

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Page type Not Found!',
                'data' => [],
            ], 400);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Data page berhasil ditampilkan',
                'data' => $data,
            ], 200);
        }
    }

    public static function aboutUsPage()
    {
        $data = Pages::where('page_type', 'about_us')->first();

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Page type Not Found!',
                'data' => [],
            ], 400);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Data page berhasil ditampilkan',
                'data' => $data,
            ], 200);
        }
    }

    public static function privacyPolicyPage()
    {
        $data = Pages::where('page_type', 'privacy_policy')->first();

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Page type Not Found!',
                'data' => [],
            ], 400);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Data page berhasil ditampilkan',
                'data' => $data,
            ], 200);
        }
    }

    public static function termConditionAgentPage()
    {
        $data = Pages::where('page_type', 'agent_term_condition')->first();

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Page type Not Found!',
                'data' => [],
            ], 400);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Data page berhasil ditampilkan',
                'data' => $data,
            ], 200);
        }
    }

    public static function privacyPolicyAgentPage()
    {
        $data = Pages::where('page_type', 'agent_privacy_policy')->first();

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Page type Not Found!',
                'data' => [],
            ], 400);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Data page berhasil ditampilkan',
                'data' => $data,
            ], 200);
        }
    }

    public static function termConditionSellerPage()
    {
        $data = Pages::where('page_type', 'seller_term_condition')->select('id', 'page_type', 'title', 'body')->first();

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Page type Not Found!',
                'data' => [],
            ], 400);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Data page berhasil ditampilkan',
                'data' => $data,
            ], 200);
        }
    }

    public static function privacyPolicySellerPage()
    {
        $data = Pages::where('page_type', 'seller_privacy_policy')->select('id', 'page_type', 'title', 'body')->first();

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Page type Not Found!',
                'data' => [],
            ], 400);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Data page berhasil ditampilkan',
                'data' => $data,
            ], 200);
        }
    }
}

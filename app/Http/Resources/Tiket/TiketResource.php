<?php

namespace App\Http\Resources\Tiket;

class TiketResource
{
    public function respondDataMaping($resource)
    {
        return [
            'number_tiket' => $resource['number_tiket'],
            'name' => $resource['master_tiket']['name'],
            'description' => $resource['master_tiket']['description'],
            'terms_and_conditions' => $resource['master_tiket']['tnc'],
            'event_address' => $resource['master_tiket']['event_address'],
            'customer_name' => $resource['order']['buyer']['full_name'],
            'customer_email' => $resource['order']['buyer']['email'],
            'customer_phone' => $resource['order']['buyer']['phone'],
            'usage_date' => $resource['usage_date'],
            'usage_time' => ($resource['start_time_usage'] != null && $resource['end_time_usage'] != null) ? $resource['start_time_usage'] . ' - ' . $resource['end_time_usage'] : null,
            'status' => $resource['status'],
            // 'is_vip' => $request['is_vip'],
            'created_at' => \Carbon\Carbon::parse($resource['created_at'])->format('Y-m-d H:i:s'),
            'updated_at' => \Carbon\Carbon::parse($resource['updated_at'])->format('Y-m-d H:i:s'),
        ];
    }


    public function respondDataMapingOrder($resource)
    {
        return [
            'number_tiket' => $resource['number_tiket'],
            'name' => $resource['master_tiket']['name'],
            'description' => $resource['master_tiket']['description'],
            'terms_and_conditions' => $resource['master_tiket']['tnc'],
            'event_address' => $resource['master_tiket']['event_address'],
            'customer_name' => $resource['order']['buyer']['full_name'],
            'customer_email' => $resource['order']['buyer']['email'],
            'customer_phone' => $resource['order']['buyer']['phone'],
            'usage_date' => $resource['usage_date'],
            'usage_time' => ($resource['start_time_usage'] != null && $resource['end_time_usage'] != null) ? $resource['start_time_usage'] . ' - ' . $resource['end_time_usage'] : null,
            'status' => $resource['status'],
            // 'is_vip' => $resource['is_vip'],
            'created_at' => \Carbon\Carbon::parse($resource['created_at'])->format('d M Y H:i:s'),
            'updated_at' => \Carbon\Carbon::parse($resource['updated_at'])->format('d M Y H:i:s'),
        ];
    }

    public function respondBadRequest($message, $error_code, $data = null)
    {
        if ($data != null) {
            return response()->json([
                'status_code' => $error_code,
                'status' => 'error',
                'message' => $message,
                'data' => $data,
            ], 400);
        }

        return response()->json([
            'status_code' => $error_code,
            'status' => 'error',
            'message' => $message,
        ], 400);
    }
}

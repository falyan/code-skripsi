<?php

namespace App\Http\Services\Notification;

use App\Http\Services\Service;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationQueries extends Service
{
    public function getTotalNotification($column_name, $column_value)
    {
        $data = Notification::where([[$column_name, $column_value], ['status', 0]])->get();
        $total_data = count($data) ?? 0;

        return $total_data;
    }

    public function getAllNotification($column_name, $column_value, $limit = 10, $page = 1)
    {
        $data = Notification::where($column_name, $column_value)
        ->orderBy('created_at', 'DESC')
        ->get();
        
        $data = static::paginate($data->toArray(), $limit, $page);
        return $data;
    }

    public function getAllNotificationByType($column_name, $column_value, $type, $limit)
    {
        $data = Notification::where([[$column_name, $column_value], ['type', $type]])->orderBy('created_at', 'DESC')->get();
        $data = static::paginate($data->toArray(), $limit);

        return $data;
    }
}

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

    public function getAllNotification($column_name, $column_value)
    {
        $data = Notification::where($column_name, $column_value)->paginate(10);

        return $data;
    }

    public function getAllNotificationByType($column_name, $column_value, $type)
    {
        $data = Notification::where([[$column_name, $column_value], ['type', $type]])->paginate(10);

        return $data;
    }
}

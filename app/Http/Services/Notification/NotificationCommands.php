<?php

namespace App\Http\Services\Notification;

use App\Http\Services\Service;
use App\Models\Notification;

class NotificationCommands extends Service
{
    public function create($column_name, $column_value, $type, $title, $message, $url_path, $related_pln_mobile_customer_id = null, $created_by = null)
    {
        $new_notification = new Notification();
        $new_notification->customer_id = $column_name == 'customer_id' ? $column_value : null;
        $new_notification->merchant_id = $column_name == 'merchant_id' ? $column_value : null;
        $new_notification->user_bot_id = $column_name == 'user_bot_id' ? $column_value : null;
        $new_notification->type = $type;
        $new_notification->title = $title;
        $new_notification->message = $message;
        $new_notification->url_path = $url_path;
        $new_notification->status = 0;
        $new_notification->created_by = $created_by;
        $new_notification->save();

        return $new_notification;
    }

    public function updateRead($id, $updated_by = "system")
    {
        $data = Notification::findOrFail($id);
        $data->status = 1;
        $data->updated_by = $updated_by;
        if($data->save()){
            return true;
        }else {
            return false;
        }

    }

    public function destroy($id, $updated_by = "system")
    {
        $data = Notification::findOrFail($id);
        $data->status = 9;
        $data->updated_by = $updated_by;
        if ($data->save() && $data->delete()) {
            return true;
        }else {
            return false;
        }
    }
}

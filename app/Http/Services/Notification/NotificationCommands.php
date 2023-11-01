<?php

namespace App\Http\Services\Notification;

use App\Http\Services\Service;
use App\Models\Customer;
use App\Models\Notification;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class NotificationCommands extends Service
{
    static $apiendpointplnmobile;

    static function init()
    {
        self::$curl = new Client();
        self::$apiendpoint = config('credentials.radagast.endpoint');
        self::$apiendpointplnmobile = config('credentials.plnmobile.endpoint');
    }

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
        $new_notification->related_pln_mobile_customer_id = $related_pln_mobile_customer_id;
        $new_notification->created_by = $created_by ?? 'system';
        $new_notification->updated_by = $created_by ?? 'system';
        $new_notification->save();

        return $new_notification;
    }

    public function updateRead($id, $updated_by = "system")
    {
        $data = Notification::findOrFail($id);
        $data->status = 1;
        $data->updated_by = $updated_by;
        if ($data->save()) {
            return true;
        } else {
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
        } else {
            return false;
        }
    }

    public function sendPushNotification($id, $title, $body, $status)
    {
        $param = static::setParamAPI(['status' => $status]);

        $json_body = [
            'customer_id' => $id,
            'title' => $title,
            'body' => $body
        ];

        $url = sprintf('%s/%s', static::$apiendpoint, 'api/notify/' . $id . $param);

        try {
            $response = static::$curl->request('POST', $url, [
                'http_errors' => false,
                'timeout' => 10,
                'json' => $json_body
            ]);

            $status_code = $response->getStatusCode();
            $response = json_decode($response->getBody());

            Log::info("send.push_notif", [
                'url' => $url,
                'body' => $json_body,
                'response' => $response,
                'status' => $status_code,
                'time' => time(),
            ]);

            return $response;
        } catch (\Throwable $th) {
            Log::error("send.push_notif.customer", [
                'url' => $url,
                'body' => $json_body,
                'response' => $th->getMessage(),
                'status' => $th->getCode(),
                'time' => time(),
            ]);
            return false;
        }
    }

    public function sendPushNotificationCustomerPlnMobile($id, $title, $body)
    {
        $user = Customer::findOrFail($id);
        $signature = hash('sha256', $user->email . $user->phone);

        self::$header = [
            'signature' => $signature
        ];

        $param = static::setParamAPI([]);

        $json_body = [
            'email' => $user->email,
            'phone' => $user->phone,
            'title' => $title,
            'body' => $body
        ];

        $url = sprintf('%s/%s', static::$apiendpointplnmobile, 'beyondkwh/push-notif' . $param);

        try {
            $response = static::$curl->request('POST', $url, [
                'headers' => self::$header,
                'http_errors' => false,
                'timeout' => 10,
                'json' => $json_body
            ]);

            $status_code = $response->getStatusCode();
            $response = json_decode($response->getBody());

            Log::info("send.push_notif.customer", [
                'url' => $url,
                'body' => $json_body,
                'response' => $response,
                'status' => $status_code,
                'time' => time(),
            ]);

            return true;
        } catch (\Throwable $th) {
            Log::error("send.push_notif.customer", [
                'url' => $url,
                'body' => $json_body,
                'response' => $th->getMessage(),
                'status' => $th->getCode(),
                'time' => time(),
            ]);
            return false;
        }
    }

    static function setParamAPI($data = [])
    {
        $param = [];
        $index = 0;
        $len = count($data);

        foreach ($data as $key => $value) {
            $value = preg_replace('/\s+/', '+', $value);

            if ($index == 0) {
                $param[] = sprintf('?%s=%s', $key, $value);
            } else {
                $param[] = sprintf('&%s=%s', $key, $value);
            }

            $index++;
        }

        return implode('', $param);
    }
}

<?php

namespace App\Http\Services\TestDrive;

use App\Http\Services\Service;
use App\Models\TestDrive;
use App\Models\TestDriveProduct;
use Exception;
use Illuminate\Support\Facades\Auth;

class TestDriveCommands extends Service{
    public function generatePhone($param_phone)
    {
        try {
            $result = $param_phone;
            if (substr($result, 0, 3) == '+62') {
                $result = str_replace('+62', '62', $result);
            }

            if ($result[0] == '0') {
                $result = str_replace($result[0], '62', $result);
            }

            if (substr($result, 0, 2) != '62') {
                $result = '62' . $result;
            }

            return $result;
        } catch (Exception $ex) {
            return new Exception($ex->getMessage(), $ex->getCode());
        }
    }
    
    public function createEvent($data)
    {
        $new_event = new TestDrive();
        $new_event->merchant_id = Auth::user()->merchant_id;
        $new_event->title = $data->title;
        $new_event->area_name = $data->area_name;
        $new_event->address = $data->address;
        $new_event->city_id = $data->city_id;
        $new_event->latitude = $data->latitude;
        $new_event->longitude = $data->longitude;
        $new_event->start_date = $data->start_date;
        $new_event->end_date = $data->end_date;
        $new_event->start_time = $data->start_time;
        $new_event->end_time = $data->end_time;
        $new_event->max_daily_quota = $data->max_daily_quota;
        $new_event->pic_name = $data->pic_name;
        $new_event->pic_phone = $this->generatePhone($data->pic_phone);
        $new_event->pic_email = $data->pic_email;
        $new_event->status = 1;
        $new_event->created_by = Auth::user()->full_name;
        
        if ($new_event->save()) {
            $this->insertProductTestDrive($new_event->id, $data->product_ids);
            return true;
        }else{
            return false;
        }
    }

    public function insertProductTestDrive($event_id, $product_ids)
    {
        foreach ($product_ids as $product_id) {
            $test_drive_product = new TestDriveProduct();
            $test_drive_product->test_drive_id = $event_id;
            $test_drive_product->product_id = $product_id;            
            $test_drive_product->save();
        }
        return;
    }

    
}
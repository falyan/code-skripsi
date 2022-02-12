<?php

namespace App\Http\Services\TestDrive;

use App\Http\Services\Service;
use App\Models\TestDrive;
use App\Models\TestDriveBooking;
use App\Models\TestDriveProduct;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TestDriveCommands extends Service
{
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
        $new_event->map_link = $data->map_link;
        $new_event->city_id = $data->city_id;
        $new_event->latitude = $data->latitude ?? '';
        $new_event->longitude = $data->longitude ?? '';
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
        } else {
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

    public function cancelEvent($event_id, $reason)
    {
        $now = Carbon::now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
        $data = TestDrive::with(['visitor_booking' => function($booking){
            $booking->whereIn('status',[0,1]);
        }])->find($event_id);
        $data->cancelation_date = $now;
        $data->cancelation_reason = $reason;
        $data->status = 9;

        if ($data->save()) {
            return $data;
        } else {
            return false;
        }
    }

    public function booking($event_id, $data)
    {
        $new_booking = new TestDriveBooking();
        $new_booking->test_drive_id = $event_id;
        $new_booking->customer_id = Auth::user()->id;
        $new_booking->visit_date = $data->visit_date;
        $new_booking->pic_name = $data->pic_name;
        $new_booking->pic_phone = $this->generatePhone($data->pic_phone);
        $new_booking->pic_email = $data->pic_email;
        $new_booking->total_passanger = $data->total_passanger;
        $new_booking->booking_code = Str::random(8);
        $new_booking->status = 0;

        if ($new_booking->save()) {
            return $new_booking;
        } else {
            return false;
        }
    }

    public function updateStatusBooking($booking_id, $status)
    {
        $data = TestDriveBooking::find($booking_id);
        if ($data->status == 3 || $data->status == 9) {
            return false;
        }

        if ($data->status == 1 && $status == 3) {
            return false;
        }
        
        $data->status = $status;
        if ($data->save()) return $data;
        else return false;
    }
}

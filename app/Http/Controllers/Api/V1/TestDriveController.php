<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Manager\MailSenderManager;
use App\Http\Services\Notification\NotificationCommands;
use App\Http\Services\TestDrive\TestDriveCommands;
use App\Http\Services\TestDrive\TestDriveQueries;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TestDriveController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $testDriveCommands, $testDriveQueries;
    public function __construct()
    {
        $this->testDriveCommands = new TestDriveCommands();
        $this->testDriveQueries = new TestDriveQueries();
    }

    #region seller acti0n
    public function getEVProducts(Request $request)
    {
        try {
            $page = $request->page ?? 1;
            $data = $this->testDriveQueries->getEVProducts($page);

            if ($data['total'] > 0) {
                return $this->respondWithData($data, 'Berhasil mendapatkan data EV Produk');
            } else {
                return $this->respondWithResult(false, 'Data EV Produk belum tersedia', 400);
            }
        } catch (Exception $e) {
            return $this->respondWithData($e, 'Error', 400);
        }
    }

    public function create(Request $request)
    {
        try {
            $rules = [
                'title' => 'required|min:3',
                'area_name' => 'required|min:3',
                'address' => 'required|min:5',
                'city_id' => 'required|exists:city,id',
                'latitude' => 'required',
                'longitude' => 'required',
                'start_date' => 'required',
                'end_date' => 'required',
                'start_time' => 'required',
                'end_time' => 'required',
                'pic_name' => 'required',
                'pic_phone' => 'required|digits_between:8,14',
                'pic_email' => 'required|email',
                'max_daily_quota' => 'required|integer',
                'product_ids' => 'required|array|min:1',
                'product_ids.*' => 'required|exists:product,id',
            ];

            $validator = Validator::make($request->all(), $rules, [
                'product_ids.required' => 'harus memilih minimal 1 produk.',
                'product_ids.*.required' => 'harus memilih minimal 1 produk.',
                'product_ids.*.exists' => 'produk yang dipilih tidak valid.',
                'latitude.required' => 'koordinat lokasi diperlukan (latitude).',
                'longitude.required' => 'koordinat lokasi diperlukan (longitude).',
                'required' => ':attribute diperlukan.',
                'digits_between' => 'panjang :attribute harus diantara :min dan :max karakter.',
                'min' => 'panjang :attribute minimum :min karakter.',
                'integer' => ':attribute harus menggunakan angka.',
                'email' => ':attribute harus menggunakan email valid.',
            ]);
            if ($validator->fails()) {
                $errors = collect();
                foreach ($validator->errors()->getMessages() as $key => $value) {
                    foreach ($value as $error) {
                        $errors->push($error);
                    }
                }

                return $this->respondValidationError($errors, 'Validation Error!');
            }

            DB::beginTransaction();
            $data = $this->testDriveCommands->createEvent($request);
            if (!$data) {
                DB::rollBack();
                return $this->respondWithResult(false, 'Event Test Drive gagal dibuat', 400);
            }

            DB::commit();
            return $this->respondWithResult(true, 'Event Test Drive baru berhasil dibuat');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respondWithData($e, 'Error', 400);
        }
    }

    public function getDetail($id)
    {
        try {
            $event = $this->testDriveQueries->getDetailEvent($id);
            $visitor_booking_date = $this->testDriveQueries->getVisitorBookingDate($id);
            if ($event) {
                $data['detail'] = $event;
                $data['visitor_booking_date'] = $visitor_booking_date;
                return $this->respondWithData($data, 'Berhasil mendapatkan detail event');
            } else {
                return $this->respondWithResult(false, 'Terjadi kesalahan saat memuat data', 400);;
            }
        } catch (Exception $e) {
            return $this->respondWithData($e, 'Error', 400);
        }
    }

    public function getHistoryBySeller(Request $request)
    {
        try {
            $filter = $request->filter ?? [];
            $sortby = $request->sortby ?? null;
            $page = $request->page ?? 1;

            $data = $this->testDriveQueries->getAllEvent(Auth::user()->merchant_id, $filter, $sortby, $page);
            if ($data['total'] > 0) {
                return $this->respondWithData($data, 'Berhasil mendapatkan data Event Test Drive');
            } else {
                return $this->respondWithResult(false, 'Data Event Test Drive belum tersedia', 400);
            }
        } catch (Exception $e) {
            return $this->respondWithData($e, 'Error', 400);
        }
    }

    public function cancel(Request $request, $id)
    {
        try {
            $rules = [
                'reason' => 'required|min:5'
            ];

            $validator = Validator::make($request->all(), $rules, [
                'required' => ':attribute diperlukan.',
                'min' => 'panjang :attribute minimum :min karakter.',
            ]);
            if ($validator->fails()) {
                $errors = collect();
                foreach ($validator->errors()->getMessages() as $key => $value) {
                    foreach ($value as $error) {
                        $errors->push($error);
                    }
                }

                return $this->respondValidationError($errors, 'Validation Error!');
            }

            DB::beginTransaction();
            $data = $this->testDriveCommands->cancelEvent($id, $request->reason);
            if (!$data) {
                DB::rollBack();
                return $this->respondWithResult(false, 'Terjadi kesalahan! Silakan coba beberapa saat lagi.', 400);
            }

            $this->notifyCustomer($data);
            DB::commit();
            return $this->respondWithResult(true, "{$data->title} telah dibatalkan");
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respondWithData($e, 'Error', 400);
        }
    }

    public function notifyCustomer($event)
    {
        if (count($event->visitor_booking) > 0) {
            $notificationCommand = new NotificationCommands();
            $mailSenderManager = new MailSenderManager();
            $title = 'Notifikasi Event Test Drive.';
            $message = "Event {$event->title} telah dibatalkan pada tanggal {$event->cancelation_date} dengan alasan : {$event->cancelation_reason}.";
            $url_path = 'v1/buyer/query/testdrive/detail/' . $event->id;

            foreach ($event->visitor_booking as $booking) {
                $notificationCommand->create('customer_id', $booking->customer_id, 4, $title, $message, $url_path, null, Auth::user()->full_name);
                $mailSenderManager->mailTestDrive($booking->pic_name, $booking->pic_email, $message);
            }
        }
    }

    public function getBookingList(Request $request, $id)
    {
        try {
            $status = $request->status ?? 0;
            $data = $this->testDriveQueries->getBookingList($id, $status);

            return $this->respondWithData($data, 'berhasil mendapat data calon pengunjung');
        } catch (Exception $e) {
            return $this->respondWithData($e, 'Error', 400);
        }
    }

    public function approveBooking(Request $request)
    {
        try {
            $rules = [
                'booking_id' => 'required'
            ];

            $validator = Validator::make($request->all(), $rules, [
                'required' => ':attribute diperlukan.',
            ]);
            if ($validator->fails()) {
                $errors = collect();
                foreach ($validator->errors()->getMessages() as $key => $value) {
                    foreach ($value as $error) {
                        $errors->push($error);
                    }
                }

                return $this->respondValidationError($errors, 'Validation Error!');
            }

            $booking_id = explode(',', $request->booking_id);
            $total_id = count($booking_id);

            DB::beginTransaction();
            for ($i = 0; $i < $total_id; $i++) {
                if ($this->testDriveCommands->updateStatusBooking($booking_id[$i], 1) == false) {
                    DB::rollBack();
                    return $this->respondWithResult(false, 'Terjadi kesalahan! Silakan coba beberapa saat lagi.', 400);
                }
            }
            DB::commit();
            return $this->respondWithResult(true, 'Berhasil Approve calon pengunjung');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respondWithData($e, 'Error', 400);
        }
    }

    public function rejectBooking(Request $request)
    {
        try {
            $rules = [
                'booking_id' => 'required'
            ];

            $validator = Validator::make($request->all(), $rules, [
                'required' => ':attribute diperlukan.',
            ]);
            if ($validator->fails()) {
                $errors = collect();
                foreach ($validator->errors()->getMessages() as $key => $value) {
                    foreach ($value as $error) {
                        $errors->push($error);
                    }
                }

                return $this->respondValidationError($errors, 'Validation Error!');
            }

            $booking_id = explode(',', $request->booking_id);
            $total_id = count($booking_id);

            DB::beginTransaction();
            for ($i = 0; $i < $total_id; $i++) {
                if ($this->testDriveCommands->updateStatusBooking($booking_id[$i], 9) == false) {
                    DB::rollBack();
                    return $this->respondWithResult(false, 'Terjadi kesalahan! Silakan coba beberapa saat lagi.', 400);
                }
            }
            DB::commit();
            return $this->respondWithResult(true, 'Berhasil Reject calon pengunjung');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respondWithData($e, 'Error', 400);
        }
    }
    #end region seller acti0n

    #region Buyer action
    public function getAllActiveEvent(Request $request)
    {
        try {
            $filter = $request->filter ?? [];
            $sortby = $request->sortby ?? null;
            $page = $request->page ?? 1;

            $data = $this->testDriveQueries->getAllEvent(null, $filter, $sortby, $page, true);

            if ($data['total'] > 0) {
                return $this->respondWithData($data, 'Berhasil mendapatkan data Event Test Drive');
            } else {
                return $this->respondWithResult(false, 'Data Event Test Drive belum tersedia', 400);
            }
        } catch (Exception $e) {
            return $this->respondWithData($e, 'Error', 400);
        }
    }

    public function booking(Request $request, $id)
    {
        try {
            $rules = [
                'visit_date' => 'required',
                'total_passanger' => 'required|integer|max:5|min:1',
                'pic_name' => 'required',
                'pic_phone' => 'required|digits_between:8,14',
                'pic_email' => 'required|email',
            ];

            $validator = Validator::make($request->all(), $rules, [
                'required' => ':attribute diperlukan.',
                'digits_between' => 'panjang :attribute harus diantara :min dan :max karakter.',
                'min' => 'panjang :attribute minimum :min karakter.',
                'total_passanger.max' => ':attribute tidak boleh lebih besar dari :max.',
                'total_passanger.min' => ':attribute tidak boleh lebih kecil dari :min.',
                'integer' => ':attribute harus menggunakan angka.',
                'email' => ':attribute harus menggunakan email valid.',
            ]);

            if ($validator->fails()) {
                $errors = collect();
                foreach ($validator->errors()->getMessages() as $key => $value) {
                    foreach ($value as $error) {
                        $errors->push($error);
                    }
                }

                return $this->respondValidationError($errors, 'Validation Error!');
            }

            // validate booking
            $validate_booking = $this->testDriveQueries->validateBooking($id, $request->visit_date, Auth::user()->id);
            if ($validate_booking['status'] == false) {
                return $this->respondWithResult(false, $validate_booking['message'], 400);
            }

            DB::beginTransaction();
            $data = $this->testDriveCommands->booking($id, $request);

            if (!$data) {
                DB::rollBack();
                return $this->respondWithResult(false, 'Terjadi kesalahan! Silakan coba beberapa saat lagi.', 400);
            }

            DB::commit();
            return $this->respondWithResult(true, "Berhasil booking event Test Drive");;
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respondWithData($e, 'Error', 400);
        }
    }

    public function getHistoryByCustomer(Request $request)
    {
        try {
            $status = $request->status ?? 0;
            $page = $request->page ?? 0;
            $data = $this->testDriveQueries->getHistoryBooking(Auth::user()->id, $status, $page);

            if ($data['total'] > 0) {
                return $this->respondWithData($data, 'Berhasil mendapatkan data History Booking');
            } else {
                return $this->respondWithResult(false, 'Data belum tersedia', 400);
            }
        } catch (Exception $e) {
            return $this->respondWithData($e, 'Error', 400);
        }
    }

    public function attendance(Request $request)
    {
        try {

            $rules = [
                'testd_drive_id' => 'required',
                'booking_code' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules, [
                'required' => ':attribute diperlukan.',
            ]);

            if ($validator->fails()) {
                $errors = collect();
                foreach ($validator->errors()->getMessages() as $key => $value) {
                    foreach ($value as $error) {
                        $errors->push($error);
                    }
                }

                return $this->respondValidationError($errors, 'Validation Error!');
            }

            $validate = $this->testDriveQueries->validateAttendance($request->test_drive_id, $request->booking_code);
            if ($validate['status'] == false) {
                return $this->respondWithResult(false, $validate['message'], 400);
            }

            DB::beginTransaction();
            if ($this->testDriveCommands->updateStatusBooking($validate['booking_id'], 2) == false) {
                DB::rollback();
                return $this->respondWithResult(false, 'Terjadi kesalahan! Silakan coba beberapa saat lagi.');
            }

            DB::commit();
            return $this->respondWithResult(true, 'Selamat datang');
        } catch (Exception $e) {
            DB::rollback();
            return $this->respondWithData($e, 'Error', 400);
        }
    }
    #End region Buyer action
}

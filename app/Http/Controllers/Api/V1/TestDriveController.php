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
    protected $notificationCommand, $mailSenderManager;
    public function __construct()
    {
        $this->testDriveCommands = new TestDriveCommands();
        $this->testDriveQueries = new TestDriveQueries();
        $this->notificationCommand = new NotificationCommands();
        $this->mailSenderManager = new MailSenderManager();
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
            return $this->respondErrorException($e, request());
        }
    }

    public function create(Request $request)
    {
        try {
            $rules = [
                'title' => 'required|min:3',
                'area_name' => 'required|min:3',
                'address' => 'required|min:5',
                'map_link' => 'sometimes',
                'city_id' => 'required|exists:city,id',
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
            return $this->respondErrorException($e, request());
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
            return $this->respondErrorException($e, request());
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
            return $this->respondErrorException($e, request());
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

            $this->notifyCancelCustomer($data);
            DB::commit();
            return $this->respondWithResult(true, "{$data->title} telah dibatalkan");
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respondErrorException($e, request());
        }
    }

    public function notifyCancelCustomer($event)
    {
        if (count($event->visitor_booking) > 0) {
            $title = 'Notifikasi Event Test Drive.';
            $message = "Event {$event->title} telah dibatalkan pada tanggal {$event->cancelation_date} dengan alasan : {$event->cancelation_reason}.";
            $url_path = 'v1/buyer/query/testdrive/detail/' . $event->id;

            foreach ($event->visitor_booking as $booking) {
                $this->notificationCommand->create('customer_id', $booking->customer_id, 4, $title, $message, $url_path, null, Auth::user()->full_name);
                $this->mailSenderManager->mailTestDrive($booking->pic_name, $booking->pic_email, $message);
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
            return $this->respondErrorException($e, request());
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
                if (!$data = $this->testDriveCommands->updateStatusBooking($booking_id[$i], 1)) {
                    DB::rollBack();
                    return $this->respondWithResult(false, 'Terjadi kesalahan! Silakan coba beberapa saat lagi.', 400);
                }
                $this->notificationCommand->create('customer_id', $data->customer_id, 4, 'Notifikasi Event Test Drive.', 'Permintaan booking EV Test Drive anda telah Disetujui. Klik untuk membuka halaman History Booking', '/v1/buyer/query/testdrive/history?status=1&page=1', null, Auth::user()->full_name);
                $this->notificationCommand->sendPushNotification($data->customer_id, 'Notifikasi Event Test Drive.', 'Permintaan booking EV Test Drive anda telah Disetujui. Klik untuk membuka halaman History Booking', 'active');
            }
            DB::commit();
            return $this->respondWithResult(true, 'Berhasil Approve calon pengunjung');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respondErrorException($e, request());
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
                if (!$data = $this->testDriveCommands->updateStatusBooking($booking_id[$i], 3)) {
                    DB::rollBack();
                    return $this->respondWithResult(false, 'Terjadi kesalahan! Silakan coba beberapa saat lagi.', 400);
                }
                $this->notificationCommand->create('customer_id', $data->customer_id, 4, 'Notifikasi Event Test Drive.', 'Permintaan booking EV Test Drive anda Dotolak. Klik untuk membuka halaman History Booking', '/v1/buyer/query/testdrive/history?status=9&page=1', null, Auth::user()->full_name);
                $this->notificationCommand->sendPushNotification($data->customer_id, 'Notifikasi Event Test Drive.', 'Permintaan booking EV Test Drive anda Dotolak. Klik untuk membuka halaman History Booking', 'active');
            }
            DB::commit();
            return $this->respondWithResult(true, 'Berhasil Reject calon pengunjung');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respondErrorException($e, request());
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

            if (!empty($filter['keyword']) && strlen($filter['keyword']) < 3) {
                return $this->respondWithResult(false, 'Panjang kata kunci minimal 3 karakter.', 400);
            }

            $data = $this->testDriveQueries->getAllEvent(null, $filter, $sortby, $page, true);

            if ($data['total'] > 0) {
                return $this->respondWithData($data, 'Berhasil mendapatkan data Event Test Drive');
            } else {
                if (!empty($filter['keyword'])) {
                    return $this->respondWithResult(false, "Event Test Drive dengan kata kunci {$filter['keyword']} Tidak ditemukan", 400);
                }
                return $this->respondWithResult(false, 'Data Event Test Drive belum tersedia', 400);
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
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
            return $this->respondErrorException($e, request());
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
            return $this->respondErrorException($e, request());
        }
    }

    public function getDetailBooking($id)
    {
        try {
            $data = $this->testDriveQueries->getDetailBooking($id);

            if ($data) {
                return $this->respondWithData($data, 'Berhasil mendapatkan detail History Booking');
            } else {
                return $this->respondWithResult(false, 'Terjadi kesalahan saat memuat data', 400);;
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function attendance(Request $request)
    {
        try {

            $rules = [
                'test_drive_id' => 'required',
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
            if (!$data = $this->testDriveCommands->updateStatusBooking($validate['booking_id'], 2)) {
                DB::rollback();
                return $this->respondWithResult(false, 'Terjadi kesalahan! Silakan coba beberapa saat lagi.');
            }

            DB::commit();
            return $this->respondWithResult(true, 'Selamat datang');
        } catch (Exception $e) {
            DB::rollback();
            return $this->respondErrorException($e, request());
        }
    }

    public function cancelBooking(Request $request)
    {
        try {
            $rules = [
                'test_drive_id' => 'required',
                'booking_id' => 'required',
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

            $is_active = $this->testDriveQueries->checkActiveEvent($request->test_drive_id);
            if (!$is_active['status']) {
                return $this->respondWithResult(false, 'Event Test Drive sudah tidak tersedia.');    
            }
            DB::beginTransaction();
            if (!$data = $this->testDriveCommands->updateStatusBooking($request->booking_id, 2)) {
                DB::rollback();
                return $this->respondWithResult(false, 'Terjadi kesalahan! Silakan coba beberapa saat lagi.');
            }

            DB::commit();
            return $this->respondWithResult(true, 'Pembatalan berhasil');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }
    #End region Buyer action
}

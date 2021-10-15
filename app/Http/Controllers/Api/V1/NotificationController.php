<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Notification\NotificationCommands;
use App\Http\Services\Notification\NotificationQueries;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public $notification_type = [
        '1' => 'Informasi',
        '2' => 'Transaksi',
        '3' => 'Promo',
    ];

    protected $notificationQueries, $notificationCommand;
    public function __construct()
    {
        $this->notificationQueries = new NotificationQueries();
        $this->notificationCommand = new NotificationCommands();
    }
    # Region Buyer
    public function buyerIndex($rlc_id)
    {
        try {
            $data = null;
            if (Auth::check()) {
                $data = $this->notificationQueries->getTotalNotification('buyer_id', Auth::user()->id);
            } else {
                $data = $this->notificationQueries->getTotalNotification('related_pln_mobile_customer_id', $rlc_id);
            }

            return $this->respondWithData(['total_notification' => $data], 'sukses get data notifikasi');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function buyerNotificationList($rlc_id)
    {
        try {
            $data = null;
            if (Auth::check()) {
                $data = $this->notificationQueries->getAllNotification('buyer_id', Auth::user()->id);
            } else {
                $data = $this->notificationQueries->getAllNotification('related_pln_mobile_customer_id', $rlc_id);
            }

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data notifikasi');
            } else {
                return $this->respondWithResult(true, 'belum ada notifikasi');
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function buyerNotificationByType($type, $rlc_id)
    {
        try {
            if (!in_array($type, [1, 2, 3])) {
                return $this->respondValidationError(['notification_type' => $type, 'error_message' => 'type yang diinput salah.']);
            }

            $data = null;
            if (Auth::check()) {
                $data = $this->notificationQueries->getAllNotificationByType('buyer_id', Auth::user()->id, $type);
            } else {
                $data = $this->notificationQueries->getAllNotificationByType('related_pln_mobile_customer_id', $rlc_id, $type);
            }

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data notifikasi ' . $this->notification_type[$type]);
            } else {
                return $this->respondWithResult(true, 'belum ada notifikasi . ' . $this->notification_type[$type]);
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function buyerReadNotification($id, $rlc_id)
    {
        try {
            $data = $this->notificationCommand->updateRead($id);

            if ($data) {
                return $this->respondWithResult(true, 'sukses update status notifikasi', 200);
            } else {
                return $this->respondWithResult(false, 'gagal update status notifikasi', 400);
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function buyerDeleteNotification($id, $rlc_id)
    {
        try {
            $data = $this->notificationCommand->destroy($id);

            if ($data) {
                return $this->respondWithResult(true, 'sukses hapus notifikasi', 200);
            } else {
                return $this->respondWithResult(false, 'gagal hapus notifikasi', 400);
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }
    # End Region

    # Region Seller
    public function sellerIndex()
    {
        try {
            $user = Auth::user();
            $data = $this->notificationQueries->getTotalNotification('merchant_id', $user->merchant_id);

            return $this->respondWithData(['total_notification' => $data], 'sukses get data notifikasi');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function sellerNotificationByType($type)
    {
        try {
            if (!in_array($type, [1, 2, 3])) {
                return $this->respondValidationError(['notification_type' => $type, 'error_message' => 'type yang diinput salah.']);
            }

            $data = $this->notificationQueries->getAllNotificationByType('merchant_id', Auth::user()->id, $type);

            if ($data->total() > 0) {
                return $this->respondWithData($data, 'sukses get data notifikasi ' . $this->notification_type[$type]);
            } else {
                return $this->respondWithResult(true, 'belum ada notifikasi . ' . $this->notification_type[$type]);
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function sellerReadNotification($id)
    {
        try {
            $data = $this->notificationCommand->updateRead($id);

            if ($data) {
                return $this->respondWithResult(true, 'sukses update status notifikasi', 200);
            } else {
                return $this->respondWithResult(false, 'gagal update status notifikasi', 400);
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function sellerDeleteNotification($id)
    {
        try {
            $data = $this->notificationCommand->destroy($id);

            if ($data) {
                return $this->respondWithResult(true, 'sukses hapus notifikasi', 200);
            } else {
                return $this->respondWithResult(false, 'gagal hapus notifikasi', 400);
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }
    # End Region
}

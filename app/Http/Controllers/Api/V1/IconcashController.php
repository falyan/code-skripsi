<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Iconcash\IconcashCommands;
use App\Http\Services\Manager\IconcashManager;
use App\Models\IconcashInquiry;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class IconcashController extends Controller
{
    protected $corporate_id = 10;

    public function activation()
    {
        if (!$pin = request()->get('pin')) {
            return $this->respondWithResult(false, 'pin tidak boleh kosong!', 400);
        }

        if (strlen($pin) != 64 || preg_match("/[g-zG-Z\'^£$%&*()}{@#~?><>,:|=_+¬-]/", $pin)) {
            return $this->respondWithResult(false, 'pin yang diberikan tidak valid!', 400);
        }

        if (!Auth::user()->phone) {
            return $this->respondWithResult(false, 'user harus mengisi nomor telepon terlebih dahulu!', 400);
        }

        try {
            $user = Auth::user();
            $this->register($user, $pin);

            return $this->respondWithResult(true, 'Registrasi Iconcash Berhasil!');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function register(User $user, $pin)
    {
        try {
            if ($user->iconcash) {
                throw new Exception('user sudah pernah melakukan registrasi iconcash');
            }

            $name = $user->full_name;

            if (preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $name)) {
                $name = preg_replace('/[^A-Za-z0-9\- ]/', '', $name);
            }

            if (!preg_match("/^[0-9]{9,13}\z/", $user->phone)) {
                throw new Exception('nomor telepon user tidak valid!', 400);
            }

            $response = IconcashManager::register($name, $user->phone, $pin, $this->corporate_id, $user->email); //TODO temporarily using self function for hashing pin, till api public to fe

            IconcashCommands::register($user);

            return $response;
        } catch (Exception $e) {
            if (in_array($e->getCode(), $this->error_codes)) {
                throw new Exception($e->getMessage(), $e->getCode());
            }
            throw new Exception($e->getMessage(), 404);
        }
    }

    public function requestOTP()
    {
        try {
            $user = Auth::user();

            $response = IconcashManager::requestOTP($this->corporate_id, $user->phone);

            if (isset($response->code)) {
                if ($response->code == 5000) {
                    return response()->json(["success" => $response->success, "code" => $response->code, "message" => $response->message], 404);
                }
            }
            
            if (!$user->iconcash) {
                IconcashCommands::register($user);
            }

            return $this->respondWithData([
                "success"   => true,
                "phone"     => $response->phoneNumber,
                "status"    => $response->status
            ], 'Request OTP Berhasil Dikirim!');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function validateOTP()
    {
        if (!$otp = request()->get('otp')) {
            return $this->respondWithResult(false, 'OTP harus diisi');
        }

        try {
            $user = Auth::user();

            IconcashManager::validateOTP($this->corporate_id, $user->phone, $otp);

            return $this->respondWithResult(true, 'Validasi OTP Sukses');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function login()
    {
        if (!$pin = request()->get('pin')) {
            return $this->respondWithResult(false, 'PIN harus diisi');
        }

        try {
            $user = Auth::user();
            
            $response = IconcashManager::login($this->corporate_id, $user->phone, $pin);
            IconcashCommands::login($user, $response);

            return $this->respondWithData([
                "success"       => true,
                "iconcash_username"      => $response->username,
                "iconcash_customer_id"   => $response->customerId,
                "iconcash_customer_name" => $response->customerName
            ], 'Berhasil login!');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function logout()
    {
        try {
            $iconcash = Auth::user()->iconcash;
            
            if (!isset($iconcash->token)) {
                return response()->json(['success' => false, 'code' => 2021, 'message' => 'user belum aktivasi iconcash / token expired'], 200);
            }

            $response = IconcashManager::logout($iconcash->token);
            IconcashCommands::logout(Auth::user());

            return $this->respondWithResult(true, $response->data);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getCustomerAllBalance()
    {
        try {
            $iconcash = Auth::user()->iconcash;

            if (!isset($iconcash->token)) {
                return response()->json(['success' => false, 'code' => 2021, 'message' => 'user belum aktivasi iconcash / token expired'], 200);
            }

            $response = IconcashManager::getCustomerAllBalance($iconcash->token);

            return $this->respondWithCollection($response, function ($item) {
                return [
                    'id'                        => $item->id,
                    'name'                      => $item->name,
                    'account_type'              => $item->accountType,
                    'account_type_alias_name'   => $item->acountTypeAliasName,
                    'balance'                   => $item->balance
                ];
            });
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function withdrawalInquiry()
    {
        if (!$bank_account_name = request()->get('bank_account_name')) {
            return $this->respondWithResult(false, 'field bank_account_name kosong', 400);
        }
        if (!$bank_account_no = request()->get('bank_account_no')) {
            return $this->respondWithResult(false, 'field bank_account_no kosong', 400);
        }
        if (!$bank_id = request()->get('bank_id')) {
            return $this->respondWithResult(false, 'field bank_id kosong', 400);
        }
        if (!$nominal = request()->get('nominal')) {
            return $this->respondWithResult(false, 'field nominal kosong', 400);
        }
        if (!$source_account_id = request()->get('source_account_id')) {
            return $this->respondWithResult(false, 'field source_account_id kosong', 400);
        }

        try {
            $iconcash = Auth::user()->iconcash;
            
            if (!isset($iconcash->token)) {
                return response()->json(['success' => false, 'code' => 2021, 'message' => 'user belum aktivasi iconcash / token expired'], 200);
            }

            $response = IconcashInquiry::createWithdrawalInquiry($iconcash, $bank_account_name, $bank_account_no, $bank_id, $nominal, $source_account_id);

            return $this->respondWithData([
                "data" => $response
            ], 'Proses Inquiry Berhasil!');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function topupInquiry()
    {
        if (!$account_type_id = request()->get('account_type_id')) {
            return $this->respondWithResult(false, 'field account_type_id kosong', 400);
        }

        if (!$amount = request()->get('amount')) {
            return $this->respondWithResult(false, 'field amount kosong', 400);
        }

        try {
            $iconcash = Auth::user()->iconcash;

            if (!isset($iconcash->token)) {
                return response()->json(['success' => false, 'code' => 2021, 'message' => 'user belum aktivasi iconcash / token expired'], 200);
            }

            $client_ref = $this->unique_code($iconcash->token);

            $response = IconcashInquiry::createTopupInquiry($iconcash, $account_type_id, $amount, $client_ref, $this->corporate_id);

            return $this->respondWithData([
                'order_id' => $response->orderId,
                'account_id' => $response->accountId,
                'phone_number' => $response->phoneNumber,
                'account_name' => $response->accountName,
                'corporate_name'    => $response->corporateName,
                'amount'            => $response->amount,
                'fee_topup'         => $response->feeTopup,
                'fee_agent'         => $response->feeAgent,
                'amount_fee'        => $response->amountFee,
                'status'            => $response->status
            ], 'Berhasil melakukan inquiry topup!');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function topupConfirm()
    {
        if (!$amount = request()->get('amount')) {
            return $this->respondWithResult(false, 'field amount kosong', 400);
        }

        if (!$order_id = request()->get('order_id')) {
            return $this->respondWithResult(false, 'field order_id kosong', 400);
        }

        try {
            $iconcash = Auth::user()->iconcash;

            if (!isset($iconcash->token)) {
                return response()->json(['success' => false, 'code' => 2021, 'message' => 'user belum aktivasi iconcash / token expired'], 200);
            }

            $response = IconcashManager::topupConfirm($order_id, $amount);

            return $this->respondWithData([
                'order_id' => $response->orderId,
                'account_id' => $response->accountId,
                'phone_number' => $response->phoneNumber,
                'account_name' => $response->accountName,
                'corporate_name'    => $response->corporateName,
                'amount'            => $response->amount,
                'fee_topup'         => $response->feeTopup,
                'fee_agent'         => $response->feeAgent,
                'amount_fee'        => $response->amountFee,
                'status'            => $response->status
            ], 'Berhasil Konfirmasi Topup!');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function unique_code($value)
    {
        return substr(base_convert(sha1(uniqid($value)), 16, 36), 0, 25);
    }

    public function hash_salt_sha256($pin = null, $return = "response")
    {
        if (!$pin) {
            if($return == "response") {
                return $this->respondWithResult(false, 'parameter pin not found', 404);
            }

            throw new Exception('pin is not defined', 400);
        }

        $user =Auth::user();
        $salted_pin = $user->phone . $pin;

        if($return == "response") {
            return response()->json(['hashed_pin' => hash('sha256', $salted_pin)], 200);
        }

        return hash('sha256', $salted_pin);
    }
}

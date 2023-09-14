<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Iconcash\IconcashCommands;
use App\Http\Services\Iconcash\IconcashQueries;
use App\Http\Services\Manager\IconcashManager;
use App\Models\AgentMasterPsp;
use App\Models\IconcashInquiry;
use App\Models\Order;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class IconcashController extends Controller
{
    protected $corporate_id = 10;
    protected $queries;

    public function __construct()
    {
        $this->queries = new IconcashQueries();
    }

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

            if (!preg_match("/^[0-9]{8,15}\z/", $user->phone)) {
                throw new Exception('nomor telepon user tidak valid!', 400);
            }

            $response = IconcashManager::register($name, $user->phone, $pin, $this->corporate_id, $user->email);

            if (isset($response->code)) {
                if ($response->code == 5006) {
                    return response()->json(["success" => $response->success, "code" => $response->code, "message" => $response->message], 404);
                }
            }

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
                if ($response->code == 5000 || $response->code == 5006) {
                    return response()->json(["success" => $response->success, "code" => $response->code, "message" => $response->message], 404);
                }
            }

            if (!$user->iconcash) {
                IconcashCommands::register($user);
            }

            return $this->respondWithData([
                "success" => true,
                "phone" => $response->phoneNumber,
                "status" => $response->status,
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

            if (isset($response->code)) {
                if ($response->code == 5001 || $response->code == 5002 || $response->code == 5003 || $response->code == 5004 || $response->code == 5006) {
                    return response()->json(["success" => $response->success, "code" => $response->code, "message" => $response->message], 200);
                }
            }

            IconcashCommands::login($user, $response);

            return $this->respondWithData([
                "success" => true,
                "iconcash_username" => $response->username,
                "iconcash_customer_id" => $response->customerId,
                "iconcash_customer_name" => $response->customerName,
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

            // return $this->respondWithCollection($response, function ($item) {
            //     return [
            //         'id' => $item->id,
            //         'name' => $item->name,
            //         'account_type' => $item->accountType,
            //         'account_type_alias_name' => $item->acountTypeAliasName,
            //         'balance' => $item->balance,
            //     ];
            // });

            $data = [];
            foreach ($response as $key => $value) {
                $data[] = [
                    'id' => $value->id,
                    'name' => $value->name,
                    'account_type' => $value->accountType,
                    'account_type_alias_name' => $value->acountTypeAliasName,
                    'balance' => $value->balance,
                ];
            }

            return $this->respondWithData($data, 'Berhasil mendapatkan data balance!');
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
                'order_id' => $response->orderId,
                'invoice_id' => $response->invoiceId,
                'source_account_id' => $response->sourceAccountId,
                'source_account_name' => $response->sourceAccountName,
                'nominal' => $response->nominal,
                'fee' => $response->fee,
                'total' => $response->total,
                'bank_id' => $response->bankId,
                'bank_code' => $response->bankCode,
                'bank_name' => $response->bankName,
                'bank_account_no' => $response->bankAccountNo,
                'bank_account_name' => $response->bankAccountName,
            ], 'Proses Inquiry Berhasil!');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function withdrawalInquiryV2()
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

            $response = IconcashInquiry::createWithdrawalInquiryV2($iconcash, $bank_account_name, $bank_account_no, $bank_id, $nominal, $source_account_id);

            return $this->respondWithData([
                'order_id' => data_get($response, 'orderId') ?? '',
                'invoice_id' => data_get($response, 'invoiceId') ?? '',
                'source_account_id' => data_get($response, 'sourceAccountId') ?? '',
                'source_account_name' => data_get($response, 'sourceAccountName') ?? '',
                'nominal' => data_get($response, 'nominal') ?? '',
                'fee' => data_get($response, 'fee') ?? '',
                'admin_fee' => data_get($response, 'adminFee') ?? '',
                'total' => data_get($response, 'total') ?? '',
                'bank_id' => data_get($response, 'bankId') ?? '',
                'bank_code' => data_get($response, 'bankCode') ?? '',
                'bank_name' => data_get($response, 'bankName') ?? '',
                'bank_account_no' => data_get($response, 'bankAccountNo') ?? '',
                'bank_account_name' => data_get($response, 'bankAccountName') ?? '',
                'expired_date' => data_get($response, 'expiredDate') ?? '',
            ], 'Proses Inquiry Berhasil!');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function withdrawal()
    {
        if (!$pin = request()->get('pin')) {
            return $this->respondWithResult(false, 'field PIN kosong');
        }

        if (!$order_id = request()->get('order_id')) {
            return $this->respondWithResult(false, 'field order_id kosong');
        }

        try {
            $iconcash = Auth::user()->iconcash;

            if (!isset($iconcash->token)) {
                return response()->json(['success' => false, 'code' => 2021, 'message' => 'user belum aktivasi / token expired'], 200);
            }

            $response = IconcashManager::withdrawal($iconcash->token, $pin, $order_id);

            if ($response) {
                $iconcash_inquiry = IconcashInquiry::where('iconcash_order_id', $order_id)->first();
                $iconcash_inquiry->confirm_res_json = json_encode(data_get($response, 'data'));
                $iconcash_inquiry->confirm_status = data_get($response, 'success');
                $iconcash_inquiry->save();
            }

            if (data_get($response, 'code') != null) {
                if (data_get($response, 'code') == 5001 || data_get($response, 'code') == 5002 || data_get($response, 'code') == 5003 || data_get($response, 'code') == 5004 || data_get($response, 'code') == 5006) {
                    return response()->json(["success" => data_get($response, 'success'), "code" => data_get($response, 'code'), "message" => data_get($response, 'success')], 200);
                }
            }

            return $this->respondWithData([
                'order_id' => data_get($response, 'data.orderId') ?? '',
                'invoice_id' => data_get($response, 'data.invoiceId') ?? '',
                'source_account_id' => data_get($response, 'data.sourceAccountId') ?? '',
                'source_account_name' => data_get($response, 'data.sourceAccountName') ?? '',
                'nominal' => data_get($response, 'data.nominal') ?? '',
                'fee' => data_get($response, 'data.fee') ?? '',
                'total' => data_get($response, 'data.total') ?? '',
                'bank_id' => data_get($response, 'data.bankId') ?? '',
                'bank_code' => data_get($response, 'data.bankCode') ?? '',
                'bank_name' => data_get($response, 'data.bankName') ?? '',
                'bank_account_no' => data_get($response, 'data.bankAccountNo') ?? '',
                'bank_account_name' => data_get($response, 'data.bankAccountName') ?? '',
            ], 'Withdrawal Berhasil!');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function withdrawalV2()
    {
        if (!$pin = request()->get('pin')) {
            return $this->respondWithResult(false, 'field PIN kosong');
        }

        if (!$order_id = request()->get('order_id')) {
            return $this->respondWithResult(false, 'field order_id kosong');
        }

        try {
            $iconcash = Auth::user()->iconcash;

            if (!isset($iconcash->token)) {
                return response()->json(['success' => false, 'code' => 2021, 'message' => 'user belum aktivasi / token expired'], 200);
            }

            $response = IconcashManager::withdrawalV2($iconcash->token, $pin, $order_id);

            if (data_get($response, 'success') == false) {
                return response()->json(["success" => data_get($response, 'success'), "code" => data_get($response, 'code'), "message" => data_get($response, 'message')], 200);
            }

            if ($response) {
                $iconcash_inquiry = IconcashInquiry::where('iconcash_order_id', $order_id)->first();
                $iconcash_inquiry->confirm_res_json = json_encode(data_get($response, 'data'));
                $iconcash_inquiry->confirm_status = data_get($response, 'success');
                $iconcash_inquiry->save();
            }

            if (data_get($response, 'code') != null) {
                if (data_get($response, 'code') == 5001 || data_get($response, 'code') == 5002 || data_get($response, 'code') == 5003 || data_get($response, 'code') == 5004 || data_get($response, 'code') == 5006) {
                    return response()->json(["success" => data_get($response, 'success'), "code" => data_get($response, 'code'), "message" => data_get($response, 'success')], 200);
                }
            }

            return $this->respondWithData([
                'order_id' => data_get($response, 'data.orderId'),
                'invoice_id' => data_get($response, 'data.invoiceId'),
                'source_account_id' => data_get($response, 'data.sourceAccountId'),
                'source_account_name' => data_get($response, 'data.sourceAccountName'),
                'nominal' => data_get($response, 'data.nominal'),
                'fee' => data_get($response, 'data.fee'),
                'total' => data_get($response, 'data.total'),
                'bank_id' => data_get($response, 'data.bankId'),
                'bank_code' => data_get($response, 'data.bankCode'),
                'bank_name' => data_get($response, 'data.bankName'),
                'bank_account_no' => data_get($response, 'data.bankAccountNo'),
                'bank_account_name' => data_get($response, 'data.bankAccountName'),
            ], 'Withdrawal Berhasil!');
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

            $response = IconcashInquiry::createTopupInquiry($iconcash, $account_type_id, $amount, $client_ref, $this->corporate_id, null);

            return $this->respondWithData([
                'order_id' => $response->orderId,
                'account_id' => $response->accountId,
                'phone_number' => $response->phoneNumber,
                'account_name' => $response->accountName,
                'corporate_name' => $response->corporateName,
                'amount' => $response->amount,
                'fee_topup' => $response->feeTopup,
                'fee_agent' => $response->feeAgent,
                'amount_fee' => $response->amountFee,
                'status' => $response->status,
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
                'corporate_name' => $response->corporateName,
                'amount' => $response->amount,
                'fee_topup' => $response->feeTopup,
                'fee_agent' => $response->feeAgent,
                'amount_fee' => $response->amountFee,
                'status' => $response->status,
            ], 'Berhasil Konfirmasi Topup!');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getRefBank()
    {
        try {
            $iconcash = Auth::user()->iconcash;

            if (!isset($iconcash->token)) {
                return response()->json(['success' => false, 'code' => 2021, 'message' => 'user belum aktivasi iconcash / token expired'], 200);
            }

            $response = IconcashManager::getRefBank($iconcash->token);
            // return $this->respondWithCollection($response, function ($bank) {
            //     return [
            //         'id' => $bank->id,
            //         'code' => $bank->code,
            //         'name' => $bank->name,
            //         'va_prefix' => $bank->vaPrefix,
            //     ];
            // });

            // change to new format
            $data = [];
            foreach ($response as $value) {
                $data[] = [
                    'id' => $value->id ?? '',
                    'code' => $value->code ?? '',
                    'name' => $value->name ?? '',
                    'va_prefix' => $value->vaPrefix ?? '',
                    'fee_withdrawal' => $value->feeWithdrawal ?? '',
                    'fee_wuthdrawal_type' => $value->feeWithdrawalType ?? '',
                ];
            }

            return $this->respondWithData($data, 'Berhasil mendapatkan data bank!');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function addCustomerBank()
    {
        if (!$account_name = request()->get('account_name')) {
            return $this->respondWithResult(false, 'field account_name kosong', 400);
        }
        if (!$account_number = request()->get('account_number')) {
            return $this->respondWithResult(false, 'field account_number kosong', 400);
        }
        if (!$bank_id = request()->get('bank_id')) {
            return $this->respondWithResult(false, 'field bank_id kosong', 400);
        }

        try {
            $iconcash = Auth::user()->iconcash;

            if (!isset($iconcash->token)) {
                return response()->json(['success' => false, 'code' => 2021, 'message' => 'user belum aktivasi iconcash / token expired'], 200);
            }

            $response = IconcashManager::addCustomerBank($iconcash->token, $account_name, $account_number, $bank_id);

            return $this->respondWithData([
                'id' => $response->id,
                'bank' => $response->bank,
                'customer_name' => $response->customerName,
                'account_number' => $response->accountNumber,
                'account_name' => $response->accountName,
            ], 'Berhasil menyimpan customer bank!');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function addCustomerBankV2()
    {
        if (!$account_name = request()->get('account_name')) {
            return $this->respondWithResult(false, 'field account_name kosong', 400);
        }
        if (!$account_number = request()->get('account_number')) {
            return $this->respondWithResult(false, 'field account_number kosong', 400);
        }
        if (!$bank_id = request()->get('bank_id')) {
            return $this->respondWithResult(false, 'field bank_id kosong', 400);
        }

        try {
            $iconcash = Auth::user()->iconcash;

            if (!isset($iconcash->token)) {
                return response()->json(['success' => false, 'code' => 2021, 'message' => 'user belum aktivasi iconcash / token expired'], 200);
            }

            $response = IconcashManager::addCustomerBankV2($iconcash->token, $account_name, $account_number, $bank_id);

            return $this->respondWithData([
                'id' => $response->id,
                'bank' => $response->bank,
                'customer_name' => $response->customerName,
                'account_number' => $response->accountNumber,
                'account_name' => $response->accountName,
            ], 'Berhasil menyimpan customer bank!');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function searchCustomerBank()
    {
        try {
            $iconcash = Auth::user()->iconcash;

            if (!isset($iconcash->token)) {
                return response()->json(['success' => false, 'code' => 2021, 'message' => 'user belum aktivasi iconcash / token expired'], 200);
            }

            $query = request()->input('keyword');

            $response = IconcashManager::searchCustomerBank($iconcash->token, $query);

            // return $this->respondWithCollection(data_get($response, 'content'), function ($bank) {
            //     return [
            //         'id' => $bank->id,
            //         'bank' => $bank->bank,
            //         'account_name' => $bank->accountName,
            //         'account_number' => $bank->accountNumber,
            //         'customer_name' => $bank->customerName,
            //     ];
            // });

            // change to new format
            $data = [];
            foreach ($response->content as $key => $value) {
                $data[] = [
                    'id' => $value->id,
                    'bank' => $value->bank,
                    'account_name' => $value->accountName,
                    'account_number' => $value->accountNumber,
                    'customer_name' => $value->customerName,
                ];
            }

            return $this->respondWithData($data, 'Berhasil mendapatkan data customer bank!');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getCustomerBankById($id)
    {
        try {
            $iconcash = Auth::user()->iconcash;

            if (!isset($iconcash->token)) {
                return response()->json(['success' => false, 'code' => 2021, 'message' => 'user belum aktivasi iconcash / token expired'], 200);
            }

            $response = IconcashManager::getCustomerBankById($iconcash->token, $id);

            // return $this->respondWithItem($response, function ($bank) {
            //     return [
            //         'id' => $bank->id,
            //         'bank' => $bank->bank,
            //         'account_name' => $bank->accountName,
            //         'account_number' => $bank->accountNumber,
            //         'customer_name' => $bank->customerName,
            //     ];
            // });

            return $this->respondWithData([
                'id' => $response->id,
                'bank' => $response->bank,
                'customer_name' => $response->customerName,
                'account_number' => $response->accountNumber,
                'account_name' => $response->accountName,
            ], 'Berhasil mendapatkan data customer bank!');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function deleteCustomerBank($id)
    {
        try {
            $iconcash = Auth::user()->iconcash;

            if (!isset($iconcash->token)) {
                return response()->json(['success' => false, 'code' => 2021, 'message' => 'user belum aktivasi iconcash / token expired'], 200);
            }

            $response = IconcashManager::deleteCustomerBank($iconcash->token, $id);

            // return $this->respondWithItem($response, function () {
            //     return [
            //         'success' => true,
            //         'message' => "Customer Bank Berhasil Dihapus!",
            //     ];
            // });

            return $this->respondWithResult(true, 'Customer Bank Berhasil Dihapus!');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function updateCustomerBank($id)
    {
        try {
            $iconcash = Auth::user()->iconcash;

            $account_name = request()->get('account_name');
            $account_number = request()->get('account_number');
            $bank_id = request()->get('bank_id');

            if (!isset($iconcash->token)) {
                return response()->json(['success' => false, 'code' => 2021, 'message' => 'user belum aktivasi iconcash / token expired'], 200);
            }

            $response = IconcashManager::updateCustomerBank($iconcash->token, $id, $account_name, $account_number, $bank_id);

            // return $this->respondWithItem($response, function ($bank) {
            //     return [
            //         'id' => $bank->id,
            //         'bank' => $bank->bank,
            //         'account_name' => $bank->accountName,
            //         'account_number' => $bank->accountNumber,
            //         'customer_name' => $bank->customerName,
            //     ];
            // });

            return $this->respondWithData([
                'id' => $response->id,
                'bank' => $response->bank,
                'customer_name' => $response->customerName,
                'account_number' => $response->accountNumber,
                'account_name' => $response->accountName,
            ], 'Berhasil menyimpan customer bank!');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function updateCustomerBankV2($id)
    {
        try {
            $iconcash = Auth::user()->iconcash;

            $account_name = request()->get('account_name');
            $account_number = request()->get('account_number');
            $bank_id = request()->get('bank_id');

            if (!isset($iconcash->token)) {
                return response()->json(['success' => false, 'code' => 2021, 'message' => 'user belum aktivasi iconcash / token expired'], 200);
            }

            $response = IconcashManager::updateCustomerBankV2($iconcash->token, $id, $account_name, $account_number, $bank_id);

            return $this->respondWithData([
                'id' => $response->id,
                'bank' => $response->bank,
                'customer_name' => $response->customerName,
                'account_number' => $response->accountNumber,
                'account_name' => $response->accountName,
            ], 'Berhasil menyimpan customer bank!');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function changePin()
    {
        if (!$old_pin = request()->get('old_pin')) {
            return $this->respondWithResult(false, 'field old_pin kosong', 400);
        }

        if (!$new_pin = request()->get('new_pin')) {
            return $this->respondWithResult(false, 'field new_pin kosong', 400);
        }

        if (!$confirm_new_pin = request()->get('confirm_new_pin')) {
            return $this->respondWithResult(false, 'field confirm_new_pin kosong', 400);
        }

        if ($confirm_new_pin != $new_pin) {
            return $this->respondWithResult(false, 'field confirmation_pin tidak sesuai', 400);
        }

        try {
            $iconcash = Auth::user()->iconcash;

            if (!isset($iconcash->token)) {
                return response()->json(['success' => false, 'code' => 2021, 'message' => 'user belum aktivasi / token expired'], 200);
            }

            $response = IconcashManager::changePin($iconcash->token, $old_pin, $new_pin, $confirm_new_pin);

            return $this->respondWithResult(data_get($response, 'success'), data_get($response, 'message'), data_get($response, 'success') ? 200 : 400);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function forgotPin()
    {
        if (!$otp = request()->get('otp')) {
            return $this->respondWithResult(false, 'field otp kosong', 400);
        }

        if (!$new_pin = request()->get('new_pin')) {
            return $this->respondWithResult(false, 'field new_pin kosong', 400);
        }

        if (!$confirm_new_pin = request()->get('confirm_new_pin')) {
            return $this->respondWithResult(false, 'field confirm_new_pin kosong', 400);
        }

        if ($confirm_new_pin != $new_pin) {
            return $this->respondWithResult(false, 'field confirmation_pin tidak sesuai', 400);
        }

        try {
            $iconcash = Auth::user()->iconcash;

            if (!isset($iconcash->token)) {
                return response()->json(['success' => false, 'code' => 2021, 'message' => 'user belum aktivasi / token expired'], 200);
            }

            $response = IconcashManager::forgotPin($iconcash->token, $otp, $new_pin, $confirm_new_pin, $iconcash->phone);

            // return $this->respondWithItem($response, function ($item) {
            //     return [
            //         'success' => $item->success,
            //         'message' => $item->message,
            //     ];
            // });

            return $this->respondWithResult(data_get($response, 'success'), data_get($response, 'message'), data_get($response, 'success') ? 200 : 400);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function historySaldoPendapatan(Request $request)
    {
        try {
            $iconcash = Auth::user()->iconcash;

            $page = $request->input('page') - 1 ?? 1;
            $limit = $request->input('limit') ?? 10;

            if (!isset($iconcash->token)) {
                return response()->json(['success' => false, 'code' => 2021, 'message' => 'user belum aktivasi / token expired'], 200);
            }

            if (env('APP_ENV') == 'staging') {
                $account_type_id = 13;
            } elseif (env('APP_ENV') == 'production') {
                $account_type_id = 50;
            } else {
                $account_type_id = 13;
            }

            $response = IconcashManager::historySaldo($iconcash->token, $account_type_id, $page, $limit);
            // return $this->respondWithCollection($response, function ($item) {
            //     $order_id = IconcashInquiry::select('order_id')->where('client_ref', $item->clientRef)->get()->toArray();
            //     if (count($order_id)) {
            //         $order_id = $order_id[0]['order_id'];
            //         $order = Order::with('delivery', 'detail', 'detail.product', 'detail.product.product_photo', 'buyer', 'payment')->find($order_id)->toArray();
            //     } else {
            //         $order = null;
            //     }
            //     return [
            //         'order_id' => $item->orderId,
            //         'client_ref' => $item->clientRef,
            //         'status' => $item->status,
            //         'transaction_type_name' => $item->transactionTypeName,
            //         'source_account_name' => $item->sourceAccountName,
            //         'receiver_account_name' => $item->receiverAccountName,
            //         'receiver_account_type' => $item->receiverAccountType,
            //         'corporate_name' => $item->corporateName,
            //         'amount' => $item->amount,
            //         'fee' => $item->fee,
            //         'amount_fee' => $item->amountFee,
            //         'transaction_date' => $item->transactionDate,
            //         'description' => $item->description,
            //         'beneficiary_account' => $item->beneficiaryAccount,
            //         'beneficiary_name' => $item->beneficiaryName,
            //         'bank_name' => $item->bankName,
            //         'remarks' => $item->remarks,
            //         'additional_info' => $item->additionalInfo,
            //         'order' => $order,
            //     ];
            // });

            // change to new format response
            $data = [];
            foreach ($response as $key) {
                $order_id = IconcashInquiry::select('order_id')->where('client_ref', $key->clientRef)->get()->toArray();
                if (count($order_id)) {
                    $order_id = $order_id[0]['order_id'];
                    $order = Order::with('delivery', 'detail', 'detail.product', 'detail.product.product_photo', 'buyer', 'payment')->find($order_id);
                    if ($order) {
                        $order = $order->toArray();
                    }
                } else {
                    $order = null;
                }

                $data[] = [
                    'order_id' => $key->orderId ?? '',
                    'client_ref' => $key->clientRef ?? '',
                    'status' => $key->status ?? '',
                    'transaction_type_name' => $key->transactionTypeName ?? '',
                    'source_account_name' => $key->sourceAccountName ?? '',
                    'source_account_type' => $key->sourceAccountType ?? '',
                    'receiver_account_name' => $key->receiverAccountName ?? '',
                    'receiver_account_type' => $key->receiverAccountType ?? '',
                    'initiator_name' => $key->initiatorName ?? '',
                    'corporate_name' => $key->corporateName ?? '',
                    'amount' => $key->amount ?? '',
                    'fee' => $key->fee ?? '',
                    'amount_fee' => $key->amountFee ?? '',
                    'mdr_value' => $key->mdrValue ?? 0,
                    'transaction_date' => $key->transactionDate ?? '',
                    'invoice_id' => $key->invoiceId ?? '',
                    'description' => $key->description ?? '',
                    'merchant_name' => $key->merchantName ?? '',
                    'beneficiary_account' => $key->beneficiaryAccount ?? '',
                    'beneficiary_name' => $key->beneficiaryName ?? '',
                    'bank_name' => $key->bankName ?? '',
                    'split_payment' => $key->splitPayment ?? '',
                    'remarks' => $key->remarks ?? '',
                    'additional_info' => $key->additionalInfo ?? '',
                    'order' => $order,
                ];
            }

            return $this->respondWithData($data, 'Berhasil mendapatkan data history saldo pendapatan!');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    // Agent Iconcash Services

    public function createOrder()
    {
        $validator = Validator::make(request()->all(), [
            'transaction_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithResult(false, $validator->errors()->first(), 400);
        }

        $iconcash = Auth::user()->iconcash;

        if (!isset($iconcash->token)) {
            return response()->json(['success' => false, 'code' => 2021, 'message' => 'user belum aktivasi / token expired'], 200);
        }

        // $client_ref = $this->unique_code($iconcash->token);
        $request = request()->all();

        try {
            $response = IconcashCommands::createOrder($request, $iconcash->token);

            if ($response['status'] == 'success') {
                return response()->json($response, 200);
            } else {
                return [
                    'status' => 'failed',
                    'message' => $response['message'],
                ];
            }
        } catch (\Throwable $th) {
            return $th;
        }
    }

    public function orderConfirm()
    {
        $validator = Validator::make(request()->all(), [
            'transaction_id' => 'required',
            'client_ref' => 'required',
            'source_account_id' => 'required',
            'account_pin' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithResult(false, $validator->errors()->first(), 400);
        }

        $iconcash = Auth::user()->iconcash;

        if (!isset($iconcash->token)) {
            return response()->json(['success' => false, 'code' => 2021, 'message' => 'user belum aktivasi / token expired'], 200);
        }

        // $client_ref = $this->unique_code($iconcash->token);
        $request = request()->all();

        try {
            return $response = IconcashCommands::orderConfirm($request, $iconcash->token);

            if ($response['status'] == 'success') {
                return response()->json($response, 200);
            } else {
                return [
                    'status' => 'failed',
                    'message' => $response['message'],
                ];
            }
        } catch (\Throwable $th) {
            return $th;
        }
    }

    public function orderRefund()
    {
        $validator = Validator::make(request()->all(), [
            'transaction_id' => 'required',
            'client_ref' => 'required',
            'source_account_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithResult(false, $validator->errors()->first(), 400);
        }

        $iconcash = Auth::user()->iconcash;

        if (!isset($iconcash->token)) {
            return response()->json(['success' => false, 'code' => 2021, 'message' => 'user belum aktivasi / token expired'], 200);
        }

        // $client_ref = $this->unique_code($iconcash->token);
        $request = request()->all();

        try {
            return $response = IconcashCommands::orderRefund($request['transaction_id'], $iconcash->token, $request['client_ref'], $request['source_account_id']);

            if ($response['status'] == 'success') {
                return response()->json($response, 200);
            } else {
                return [
                    'status' => 'failed',
                    'message' => $response['message'],
                ];
            }
        } catch (\Throwable $th) {
            return $th;
        }
    }

    public function manualOrderRefund()
    {
        $validator = Validator::make(request()->all(), [
            'transaction_id' => 'required',
            'client_ref' => 'required',
            'source_account_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithResult(false, $validator->errors()->first(), 400);
        }

        $iconcash = Auth::user()->iconcash;

        if (!isset($iconcash->token)) {
            return response()->json(['success' => false, 'code' => 2021, 'message' => 'user belum aktivasi / token expired'], 200);
        }

        // $client_ref = $this->unique_code($iconcash->token);
        $request = request()->all();

        try {
            return $response = IconcashCommands::manualOrderRefund($request['transaction_id'], $iconcash->token, $request['client_ref'], $request['source_account_id']);

            if ($response['status'] == 'success') {
                return response()->json($response, 200);
            } else {
                return [
                    'status' => 'failed',
                    'message' => $response['message'],
                ];
            }
        } catch (\Throwable $th) {
            return $th;
        }
    }

    public function registerDeposit()
    {
        $iconcash = Auth::user()->iconcash;

        if (!isset($iconcash->token)) {
            return response()->json(['success' => false, 'code' => 2021, 'message' => 'user belum aktivasi / token expired'], 200);
        }

        try {
            $response = IconcashManager::registerDeposit($iconcash->token);

            if ($response['success'] == true) {
                return response()->json([
                    'success' => true,
                    'message' => 'Berhasil aktivasi saldo deposit',
                    'data' => $response['data'],
                ]);
            } else {
                return [
                    'success' => false,
                    'message' => $response['message'],
                ];
            }
        } catch (\Throwable $th) {
            return $th;
        }
    }

    public function getVADeposit()
    {
        $iconcash = Auth::user()->iconcash;

        if (!isset($iconcash->token)) {
            return response()->json(['success' => false, 'code' => 2021, 'message' => 'user belum aktivasi / token expired'], 200);
        }

        try {
            $response = IconcashManager::getVADeposit($iconcash->token);

            if ($response['success'] == true) {
                return response()->json([
                    'success' => true,
                    'message' => 'Berhasil mendapatkan VA deposit',
                    'data' => $response['data'],
                ]);
            } else {
                return [
                    'success' => false,
                    'message' => $response['message'],
                ];
            }
        } catch (\Throwable $th) {
            return $th;
        }
    }

    public function agentHistorySaldo(Request $request)
    {
        try {
            $iconcash = Auth::user()->iconcash;

            if (!isset($iconcash->token)) {
                return response()->json(['success' => false, 'code' => 2021, 'message' => 'user belum aktivasi / token expired'], 200);
            }

            $limit = $request->limit ?? 10;
            $page = $request->page ?? 0;

            $response = IconcashManager::agentHistorySaldo($limit, $page, $iconcash->token);

            return $this->respondWithData($response, 'success');
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    public function getGatewayAgentPayment()
    {
        try {

            $data = $this->queries->getGatewayAgentPayment();

            return $this->respondWithData($data, 'success');

        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getGatewayAgentPaymentByCode(Request $request)
    {
        try {

            $data = AgentMasterPsp::where([
                'status' => 1,
                'code' => $request->code,
            ])->get();

            return $this->respondWithData($data, 'success');

        } catch (\Throwable $th) {
            return $th;
        }
    }

    public function topupDeposit()
    {
        $iconcash = Auth::user()->iconcash;

        if (!$amount = request()->get('amount')) {
            return $this->respondWithResult(false, 'field amount kosong', 400);
        }

        if ($amount < 10000) {
            $response['success'] = false;
            $response['message'] = 'Minimal Topup Deposit Rp. 10.000';
            return $response;
        }

        try {
            $client_ref = $this->unique_code($iconcash->token);

            if (!isset($iconcash->token)) {
                return response()->json(['success' => false, 'code' => 2021, 'message' => 'user belum aktivasi / token expired'], 200);
            }

            $response = IconcashManager::topupDeposit($iconcash->token, $amount, $client_ref);

            return $this->respondWithData($response, 'success');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function checkFeeTopupDeposit()
    {
        $iconcash = Auth::user()->iconcash;

        if (!$order_id = request()->get('order_id')) {
            return $this->respondWithResult(false, 'field order_id kosong', 400);
        }

        if (!$psp_id = request()->get('psp_id')) {
            return $this->respondWithResult(false, 'field psp_id kosong', 400);
        }

        if (!$total_amount = request()->get('total_amount')) {
            return $this->respondWithResult(false, 'field amount kosong', 400);
        }

        try {

            if (!isset($iconcash->token)) {
                return response()->json(['success' => false, 'code' => 2021, 'message' => 'user belum aktivasi / token expired'], 200);
            }

            $response = IconcashManager::checkFeeTopupDeposit($iconcash->token, $order_id, $psp_id, $total_amount);

            return $this->respondWithData($response, 'success');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function confirmTopupDeposit()
    {
        $iconcash = Auth::user()->iconcash;

        if (!$order_id = request()->get('order_id')) {
            return $this->respondWithResult(false, 'field order_id kosong', 400);
        }

        if (!$psp_id = request()->get('psp_id')) {
            return $this->respondWithResult(false, 'field psp_id kosong', 400);
        }

        if (!$total_amount = request()->get('total_amount')) {
            return $this->respondWithResult(false, 'field amount kosong', 400);
        }

        try {

            if (!isset($iconcash->token)) {
                return response()->json(['success' => false, 'code' => 2021, 'message' => 'user belum aktivasi / token expired'], 200);
            }

            $psp = AgentMasterPsp::where('code', $psp_id)->first();

            $response = IconcashManager::confirmTopupDeposit($iconcash->token, $order_id, $psp_id, $total_amount);

            // add new data to response
            $response->psp_descriptions = json_decode($psp->description);
            $response->psp_photo_url = $psp->photo_url;

            return $this->respondWithData($response, 'success');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    // End of Agent Iconcash Services

    public function unique_code($value)
    {
        return substr(base_convert(sha1(uniqid($value)), 16, 36), 0, 25);
    }

    public function hash_salt_sha256($pin = null, $return = "response")
    {
        if (!$pin) {
            if ($return == "response") {
                return $this->respondWithResult(false, 'parameter pin not found', 404);
            }

            throw new Exception('pin is not defined', 400);
        }

        $user = Auth::user();
        $salted_pin = $user->phone . $pin;

        if ($return == "response") {
            return response()->json(['hashed_pin' => hash('sha256', $salted_pin)], 200);
        }

        return hash('sha256', $salted_pin);
    }
}

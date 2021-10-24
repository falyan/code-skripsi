<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\AuthHelper;
use App\Http\Controllers\Controller;
use App\Http\Services\Profile\ProfileCommands;
use App\Http\Services\Profile\ProfileQueries;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SettingProfileController extends Controller
{
    protected $profileCommands, $profileQueries;
    public function __construct()
    {
        $this->profileCommands = new ProfileCommands();
        $this->profileQueries = new ProfileQueries();
    }

    public function index()
    {
        try {
            return $this->respondWithData([
                'user' => $this->profileQueries->getUser(),
                'toko' => $this->profileQueries->getMerchant()
            ], 'Success get your profile information', 200);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function logout(Request $request)
    {
        try {
            Auth::logout();
            $authReq = new AuthHelper();
            $body = $authReq->privateService('logout', [], $request->header());

            return $this->respondWithResult(true, 'Berhasil Logout');
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    public function changePassword(Request $request)
    {
        try {
            $rules = [
                'old_password' => 'required',
                'password' => 'required|confirmed',
            ];
    
            $validator = Validator::make($request->all(), $rules, [
                'required' => ':attribute wajib diisi.',
                'min' => 'panjang :attribute minimum :min karakter.',
                'confirmed' => 'konfirmasi password tidak sesuai.'
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
    
            $password_validate = $this->profileQueries->validatePassword($request->password);
            if (count($password_validate) > 0) {
                return $this->respondValidationError($password_validate);
            }
            DB::beginTransaction();
            $this->profileCommands->changePassword($request);
            DB::commit();

            return $this->respondWithResult(true, 'Kata sandi berhasil diperbarui.');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respondErrorException($e, $request);
        }
    }
}

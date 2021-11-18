<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Customer\CustomerCommands;
use App\Http\Services\Customer\CustomerQueries;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->customerQueries = new CustomerQueries();
        $this->customerCommands = new CustomerCommands();
    }

    public function getListCustomerAddress(){
        try {
            $customer_id = Auth::id();
            return $this->respondWithData($this->customerQueries->getListCustomerAddress($customer_id), 'Data berhasil didapatkan.');
        }catch (Exception $e){
            return $this->respondErrorException($e, request());
        }
    }

    public function getDefaultCustomerAddress(){
        try {
            $customer_id = Auth::id();
            return $this->respondWithData($this->customerQueries->getDefaultCustomerAddress($customer_id), 'Data berhasil didapatkan.');
        }catch (Exception $e){
            return $this->respondErrorException($e, request());
        }
    }

    public function createCustomerAddress(){
        $validator = Validator::make(request()->all(), [
            'address' => 'required',
            'district_id' => 'required',
            'city_id' => 'required',
            'province_id' => 'required',
            'postal_code' => 'required',
            'receiver_name' => 'required',
            'receiver_phone' => 'required',
            'title' => 'required',
            'is_default' => 'required|boolean'
        ], [
            'required' => ':attribute diperlukan.'
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

        try {
            $customer_id = Auth::id();
            return $this->customerCommands->createCustomerAddress($customer_id, request()->all());
        }catch (Exception $e){
            return $this->respondErrorException($e, request());
        }
    }

    public function updateCustomerAddress($id){
        $validator = Validator::make(request()->all(), [
            'address' => 'required',
            'district_id' => 'required',
            'city_id' => 'required',
            'province_id' => 'required',
            'postal_code' => 'required',
            'receiver_name' => 'required',
            'receiver_phone' => 'required',
            'title' => 'required',
            'is_default' => 'required|boolean'
        ], [
            'required' => ':attribute diperlukan.'
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

        try {
            $customer_id = Auth::id();
            return $this->customerCommands->updateCustomerAddress($id ,$customer_id, request()->all());
        }catch (Exception $e){
            return $this->respondErrorException($e, request());
        }
    }

    public function setDefaultCustomerAddress($id){
        try {
            $customer_id = Auth::id();
            return $this->customerCommands->setDefaultCustomerAddress($id ,$customer_id);
        }catch (Exception $e){
            return $this->respondErrorException($e, request());
        }
    }
}

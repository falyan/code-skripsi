<?php

namespace App\Http\Services\Customer;

use App\Models\CustomerAddress;

class CustomerCommands{
    public function __construct()
    {
        $this->customerQueries = new CustomerQueries();
    }

    public function createCustomerAddress($customer_id, $data){
        $customer_address = new CustomerAddress();
        $customer_address->customer_id = $customer_id;
        $customer_address->address = $data['address'];
        $customer_address->district_id = $data['district_id'];
        $customer_address->city_id = $data['city_id'];
        $customer_address->province_id = $data['province_id'];
        $customer_address->postal_code = $data['postal_code'];
        $customer_address->longitude = $data['longitude'] ?? null;
        $customer_address->latitude = $data['latitude'] ?? null;
        $customer_address->receiver_name = $data['receiver_name'];
        $customer_address->receiver_phone = $data['receiver_phone'];
        $customer_address->title = $data['title'];
        if ($data['is_default'] == true){
            $change_isdefault = $this->changeIsDefaultToFalse($customer_id);
            if ($change_isdefault == false){
                $response['success'] = false;
                $response['message'] = 'Gagal merubah alamat utama';
                return $response;
            }
        }
        $customer_address->is_default = $data['is_default'];

        if (!$customer_address->save()){
            $response['success'] = false;
            $response['message'] = 'Gagal menyimpan alamat customer';
            return $response;
        }
        $response['success'] = true;
        $response['message'] = 'Berhasil menyimpan alamat customer';
        return $response;
    }

    public function updateCustomerAddress($id, $customer_id, $data){
        $customer_address = CustomerAddress::findOrFail($id);
        $customer_address->address = $data['address'] == null ? ($customer_address->address) : ($data['address']);
        $customer_address->district_id = $data['district_id'] == null ? ($customer_address->district_id) : ($data['district_id']);
        $customer_address->city_id = $data['city_id'] == null ? ($customer_address->city_id) : ($data['city_id']);
        $customer_address->province_id = $data['province_id'] == null ? ($customer_address->province_id) : ($data['province_id']);
        $customer_address->postal_code = $data['postal_code'] == null ? ($customer_address->postal_code) : ($data['postal_code']);
        $customer_address->longitude = $data['longitude'] ?? null;
        $customer_address->latitude = $data['latitude'] ?? null;
        $customer_address->receiver_name = $data['receiver_name'] == null ? ($customer_address->receiver_name) : ($data['receiver_name']);
        $customer_address->receiver_phone = $data['receiver_phone'] == null ? ($customer_address->receiver_phone) : ($data['receiver_phone']);
        $customer_address->title = $data['title'] == null ? ($customer_address->title) : ($data['title']);
        if ($data['is_default'] == true){
            $change_isdefault = $this->changeIsDefaultToFalse($customer_id);
            if ($change_isdefault == false){
                $response['success'] = false;
                $response['message'] = 'Gagal merubah alamat utama';
                return $response;
            }
        }
        $customer_address->is_default = $data['is_default'] == null ? ($customer_address->is_default) : ($data['is_default']);

        if (!$customer_address->save()){
            $response['success'] = false;
            $response['message'] = 'Gagal menyimpan alamat customer';
            return $response;
        }
        $response['success'] = true;
        $response['message'] = 'Berhasil menyimpan alamat customer';
        return $response;
    }

    private function changeIsDefaultToFalse($customer_id){
        $data = $this->customerQueries->getDefaultCustomerAddress($customer_id);
        if ($data == null){
            return true;
        }
        $data->is_default = false;
        if (!$data->save()){
            return false;
        }
        return true;
    }
}

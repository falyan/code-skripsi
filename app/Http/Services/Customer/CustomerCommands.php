<?php

namespace App\Http\Services\Customer;

use App\Models\Customer;
use App\Models\CustomerAddress;

class CustomerCommands
{
    private $customerQueries;

    public function __construct()
    {
        $this->customerQueries = new CustomerQueries();
    }

    public function createCustomerAddress($customer_id, $data)
    {
        $customer_address = new CustomerAddress();
        $customer_address->customer_id = $customer_id;
        $customer_address->address = $data['address'];
        $customer_address->location_name = $data['location_name'] ?? null;
        $customer_address->district_id = $data['district_id'] ?? null;
        $customer_address->city_id = $data['city_id'] ?? null;
        $customer_address->province_id = $data['province_id'] ?? null;
        $customer_address->postal_code = $data['postal_code'];
        $customer_address->longitude = $data['longitude'] ?? null;
        $customer_address->latitude = $data['latitude'] ?? null;
        $customer_address->receiver_name = $data['receiver_name'];
        $customer_address->receiver_phone = $data['receiver_phone'];
        $customer_address->title = $data['title'];
        if ($data['is_default'] == true){
            $change_isdefault = $this->changeIsDefaultToFalse($customer_id, $customer_address->id);
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

        $getAddress = CustomerAddress::where('customer_id', $customer_id)->get();
        if (collect($getAddress)->count() == 1) {
            $getAddress[0]->is_default = true;
            $getAddress[0]->save();
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil menyimpan alamat customer';
        return $response;
    }

    public function updateCustomerAddress($id, $customer_id, $data)
    {
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
        $customer_address->is_default = $data['is_default'] ?? false;

        if ($data['is_default'] == true) {
            $change_isdefault = $this->changeIsDefaultToFalse($customer_id, $id);
            if ($change_isdefault == false) {
                $response['success'] = false;
                $response['message'] = 'Gagal merubah alamat utama';
                return $response;
            }
        }

        if (!$customer_address->save()) {
            $response['success'] = false;
            $response['message'] = 'Gagal menyimpan alamat customer';
            return $response;
        }
        $response['success'] = true;
        $response['message'] = 'Berhasil menyimpan alamat customer';
        return $response;
    }

    public function setDefaultCustomerAddress($id, $customer_id)
    {
        $customer_address = CustomerAddress::findOrFail($id);

        $change_isdefault = $this->changeIsDefaultToFalse($customer_id, $id);
        if ($change_isdefault == false) {
            $response['success'] = false;
            $response['message'] = 'Gagal merubah alamat utama';
            return $response;
        }

        $customer_address->is_default = true;

        if (!$customer_address->save()) {
            $response['success'] = false;
            $response['message'] = 'Gagal menyimpan alamat utama';
            return $response;
        }
        $response['success'] = true;
        $response['message'] = 'Berhasil menyimpan alamat utama';
        return $response;
    }

    private function changeIsDefaultToFalse($customer_id, $customer_address_id)
    {
        $data = $this->customerQueries->getDefaultCustomerAddress($customer_id);
        if ($data == null) {
            return true;
        }

        if ($data->id != $customer_address_id) {
            $data->is_default = false;
            if (!$data->save()) {
                return false;
            }
        }
        return true;
    }

    public function deleteCustomerAddress($id, $customer_id)
    {
        if (!CustomerAddress::destroy($id)) {
            $response['success'] = false;
            $response['message'] = 'Gagal hapus alamat';
            return $response;
        }
        $response['success'] = true;
        $response['message'] = 'Berhasil hapus alamat';
        return $response;
    }

    public function updateCustomerProfile($customer_id, $data)
    {
        $customer = Customer::findOrFail($customer_id);
        $customer->image_url = ($data->image_url == null) ? ($customer->image_url) : ($data->image_url);
        $customer->full_name = ($data->full_name == null) ? ($customer->full_name) : ($data->full_name);

        if (!$customer->save()) {
            $response['success'] = false;
            $response['message'] = 'Gagal mengubah profil customer';
            $response['data'] = $customer;

            return $response;
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil mengubah profil customer';
        $response['data'] = $customer;
        return $response;
    }

    public function createCustomerAddressV2($customer_id, $data)
    {
        $customer_address = new CustomerAddress();
        $customer_address->customer_id = $customer_id;
        $customer_address->title = $data['title'];
        $customer_address->address = $data['address'];
        $customer_address->province_id = $data['province_id'] ?? null;
        $customer_address->city_id = $data['city_id'] ?? null;
        $customer_address->district_id = $data['district_id'] ?? null;
        $customer_address->subdistrict_id = $data['subdistrict_id'] ?? null;
        $customer_address->postal_code = $data['postal_code'];
        $customer_address->longitude = $data['longitude'] ?? null;
        $customer_address->latitude = $data['latitude'] ?? null;
        $customer_address->receiver_name = $data['receiver_name'];
        $customer_address->receiver_phone = $data['receiver_phone'];

        if ($data['is_default'] == true) {
            $change_isdefault = $this->changeIsDefaultToFalse($customer_id, $customer_address->id);
            if ($change_isdefault == false) {
                $response['success'] = false;
                $response['message'] = 'Gagal merubah alamat utama';
                return $response;
            }
        }

        if (!$customer_address->save()) {
            $response['success'] = false;
            $response['message'] = 'Gagal menyimpan alamat customer';
            return $response;
        }

        $getAddress = CustomerAddress::where('customer_id', $customer_id)->get();
        if (collect($getAddress)->count() == 1) {
            $getAddress[0]->is_default = true;
            $getAddress[0]->save();
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil menyimpan alamat customer';
        return $response;
    }

    public function updateCustomerAddressV2($id, $customer_id, $data)
    {
        $customer_address = CustomerAddress::findOrFail($id);
        $customer_address->title = $data['title'] == null ? ($customer_address->title) : ($data['title']);
        $customer_address->address = $data['address'] == null ? ($customer_address->address) : ($data['address']);
        $customer_address->location_name = $data['location_name'] == null ? ($customer_address->location_name) : ($data['location_name']);
        $customer_address->province_id = !isset($data['province_id']) ? ($customer_address->province_id) : ($data['province_id']);
        $customer_address->city_id = !isset($data['city_id']) ? ($customer_address->city_id) : ($data['city_id']);
        $customer_address->district_id = !isset($data['district_id']) ? ($customer_address->district_id) : ($data['district_id']);
        $customer_address->subdistrict_id = !isset($data['subdistrict_id']) ? ($customer_address->subdistrict_id) : ($data['subdistrict_id']);
        $customer_address->postal_code = $data['postal_code'] == null ? ($customer_address->postal_code) : ($data['postal_code']);
        $customer_address->longitude = $data['longitude'] ?? null;
        $customer_address->latitude = $data['latitude'] ?? null;
        $customer_address->receiver_name = $data['receiver_name'] == null ? ($customer_address->receiver_name) : ($data['receiver_name']);
        $customer_address->receiver_phone = $data['receiver_phone'] == null ? ($customer_address->receiver_phone) : ($data['receiver_phone']);
        $customer_address->is_default = $data['is_default'] ?? false;

        if ($data['is_default'] == true) {
            $change_isdefault = $this->changeIsDefaultToFalse($customer_id, $id);
            if ($change_isdefault == false) {
                $response['success'] = false;
                $response['message'] = 'Gagal merubah alamat utama';
                return $response;
            }
        }

        if (!$customer_address->save()) {
            $response['success'] = false;
            $response['message'] = 'Gagal merubah alamat customer';
            return $response;
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil merubah alamat customer';
        return $response;
    }
}

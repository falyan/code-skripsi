<?php

namespace App\Http\Services\Iconcash;

use App\Http\Services\Service;
use App\Models\IconcashCredential;
use Exception;
use Illuminate\Support\Facades\DB;

class IconcashCommands extends Service
{
    public static function register($user){
        try {
            DB::beginTransaction();
            IconcashCredential::create([
                'customer_id'   => $user->id,
                'phone'         => $user->phone,
                'key'           => 'authorization',
                'token'         => null,
                'status'        => 'Requested'
            ]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public static function login($user, $data)
    {
        try {
            DB::beginTransaction();
            IconcashCredential::where('id', $user->iconcash->id)
            ->where('customer_id', $user->id)
            ->update([
                'status'            => 'Activated',
                'token'             => $data->token,
                'iconcash_username' => $data->username,
                'iconcash_session_id' => $data->sessionId,
                'iconcash_customer_id' => $data->customerId,
                'iconcash_customer_name' => $data->customerName,
            ]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public static function logout($user)
    {
        try {
            DB::beginTransaction();
            IconcashCredential::where('id', $user->iconcash->id)
            ->where('customer_id', $user->id)
            ->update([
                'status'            => 'Inactivated',
                'token'             => null,
            ]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }
}

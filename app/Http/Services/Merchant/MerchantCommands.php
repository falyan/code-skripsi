<?php

namespace App\Http\Services\Merchant;

use App\Http\Services\Service;
use App\Models\Etalase;
use App\Models\Merchant;
use App\Models\MerchantExpedition;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MerchantCommands extends Service
{
    public static function aturToko($request, $merchant_id){
        try {
            DB::beginTransaction();
            $merchant = Merchant::find($merchant_id);
            $merchant->update([
                'slogan' => data_get($request, 'slogan'),
                'description' => data_get($request, 'description')
            ]);
            
            $merchant->operationals()->delete();
            
            foreach (data_get($request, 'operational') as $key) {
                if (array_key_exists('day_id', $key)) {
                    $key['master_data_id'] = $key['day_id'];
                    unset($key['day_id']);
                }
                $key['open_time'] = data_get($request, 'open_time');
                $key['closed_time'] = data_get($request, 'closed_time');
                $merchant->operationals()->create($key);
            }
            DB::commit();

            return [
                'merchant' => $merchant,
                'operationals' => $merchant->operationals()->get()
            ];
        } catch (\Throwable $th) {
            DB::rollBack();
            if (in_array($th->getCode(), self::$error_codes)) {
                throw new Exception($th->getMessage(), $th->getCode());
            }
            throw new Exception($th->getMessage(), 500);
        }
    }

    public static function createOrUpdateExpedition($list_expeditions)
    {
        try {
            DB::beginTransaction();
            
            if (!$merchant = Auth::user()->merchant) {
                throw new Exception('User tidak memiliki merchant.');
            }
            
            MerchantExpedition::updateOrCreate(
                ['merchant_id' => $merchant->id],
                ['merchant_id' => $merchant->id, 'list_expeditions' => $list_expeditions]
            );
            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            if (in_array($th->getCode(), self::$error_codes)) {
                throw new Exception($th->getMessage(), $th->getCode());
            }
            throw new Exception($th->getMessage(), 500);
        }
    }
}
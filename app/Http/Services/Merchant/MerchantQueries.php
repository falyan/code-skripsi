<?php

namespace App\Http\Services\Merchant;

use App\Http\Resources\Etalase\EtalaseCollection;
use App\Http\Resources\Etalase\EtalaseResource;
use App\Http\Services\Service;
use App\Models\Etalase;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\OrderProgress;
use Exception;
use stdClass;

class MerchantQueries extends Service
{
    static $value;

    public static function homepageProfile($merchant_id)
    {
        try {
            $merchant = Merchant::with(['operationals', 'district', 'city', 'province', 'expedition'])->find($merchant_id);
            $orders = [];
            
            $orders['success'] = static::getTotalTrx($merchant_id, 88)->toArray();
            $orders['canceled'] = static::getTotalTrx($merchant_id, 99)->toArray();
            
            return [
                'data' => [
                    'merchant' => $merchant,
                    'transactions' => [
                        'total_transaction' => count(array_merge($orders['success'], $orders['canceled'])),
                        'total_success' => count($orders['success']),
                        'total_canceled' => count($orders['canceled'])
                    ]
                ]
            ];
        } catch (Exception $th) {
            if (in_array($th->getCode(), self::$error_codes)) {
                throw new Exception($th->getMessage(), $th->getCode());
            }
            throw new Exception($th->getMessage(), 500);
        }
    }

    public static function publicProfile($merchant_id)
    {
        try {
            $merchant = Merchant::where('id', (int) $merchant_id)->first(['id', 'name', 'photo_url', 'slogan', 'description', 'city_id']);

            $mc = $merchant->toArray();
            $cityname = $merchant->city->toArray();

            $merged_data = array_merge($mc, ['city_name' => $cityname['name']]);
            $total_product = $merchant->products()->count();

            $total_trx = static::getTotalTrx($merchant_id, 88)->count();

            return [
                'merchant' => $merged_data,
                'meta_data' => [
                    'total_product' => (string) static::format_number((int) $total_product),
                    'total_transaction' => (string) static::format_number((int) $total_trx),
                    'operational_hour' => $merchant->operationals()->first(['open_time', 'closed_time', 'timezone']),
                ]
            ];
        } catch (Exception $th) {
            if (in_array($th->getCode(), self::$error_codes)) {
                throw new Exception($th->getMessage(), $th->getCode());
            }
            throw new Exception($th->getMessage(), 500);
        }
    }

    public static function unsetValue(array $array, $value, $strict = TRUE)
    {
        if(($key = array_search($value, $array, $strict)) !== FALSE) {
            unset($array[$key]);
        }
        return $array;
    }

    public static function getTotalTrx($merchant_id, $status_code)
    {
        return OrderProgress::with(['order' => function($orders_query) use ($merchant_id){
            $orders_query->where('merchant_id', $merchant_id);
        }])->where('status_code', $status_code)->get();
    }

    static function format_number($number) {
        if($number >= 1000 && $number <= 999999) {
           return $number/1000 . ' ribu';   // NB: you will want to round this
        }
        if($number >= 1000000) {
            return $number/1000000 . ' juta';   // NB: you will want to round this
        }
        else {
            return $number;
        }
    }
}

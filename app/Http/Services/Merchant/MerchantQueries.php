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

    public static function homepageProfile($merchant_id, $date = [])
    {
        try {
            $merchant = Merchant::with(['operationals', 'district', 'city', 'province', 'expedition'])->find($merchant_id);
            $orders = [];
            $order_before = [];

            $orders['success'] = static::getTotalTrx($merchant_id, '88', $date['daterange']);
            $orders['canceled'] = static::getTotalTrx($merchant_id, '09', $date['daterange']);
            $orders['total'] = array_merge($orders['success'], $orders['canceled']);

            $order_before['success'] = static::getTotalTrx($merchant_id, '88', $date['before']);
            $order_before['canceled'] = static::getTotalTrx($merchant_id, '09', $date['before']);
            $order_before['total'] = array_merge($order_before['success'], $order_before['canceled']);

            if (count($orders['success']) == 0) {
                $percentage_success = ['percent' => 0 . "%", 'status' => ''];
            } elseif (count($orders['success']) > count($order_before['success']) || count($order_before['success']) == 0) {
                $percentage_success = [
                    'percent' => round(((count($orders['success']) - count($order_before['success'])) / count($orders['success'])) * 100) . "%",
                    'status' => 'up'
                ];
            } else {
                $percentage_success = [
                    'percent' => round(((count($order_before['success']) - count($orders['success'])) / count($order_before['success'])) * 100) . "%",
                    'status' => 'down'
                ];
            }

            if (count($orders['canceled']) == 0) {
                $percentage_canceled = ['percent' => 0 . "%", 'status' => ''];
            } elseif (count($orders['canceled']) > count($order_before['canceled']) || count($order_before['canceled']) == 0) {
                $percentage_canceled = [
                    'percent' => round(((count($orders['canceled']) - count($order_before['canceled'])) / count($orders['canceled'])) * 100) . "%",
                    'status' => "up"
                ];
            } else {
                $percentage_canceled = [
                    'percent' => round(((count($order_before['canceled']) - count($orders['canceled'])) / count($order_before['canceled'])) * 100) . "%",
                    'status' => "down"
                ];
            }

            if (count($orders['total']) == 0) {
                $percentage_total = ['percent' => 0 . "%", 'status' => ''];
            } elseif (count($orders['total']) > count($order_before['total']) || count($order_before['total']) == 0) {
                $percentage_total = [
                    'percent' => round(((count($orders['total']) - count($order_before['total'])) / count($orders['total'])) * 100) . "%",
                    'status' => "up"
                ];
            } else {
                $percentage_total = [
                    'percent' => round(((count($order_before['total']) - count($orders['total'])) / count($order_before['total'])) * 100) . "%",
                    'status' => "down"
                ];
            }

            return [
                'data' => [
                    'merchant' => $merchant,
                    'transactions' => [
                        'total_transaction' => count($orders['total']),
                        'total_success' => count($orders['success']),
                        'total_canceled' => count($orders['canceled']),
                        // 'total_transaction_before' => count($order_before['total']),
                        // 'total_success_before' => count($order_before['success']),
                        // 'total_canceled_before' => count($order_before['canceled']),
                        'percentage_transaction' => $percentage_total,
                        'percentage_success' => $percentage_success,
                        'percentage_canceled' => $percentage_canceled,
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
            $merchant = Merchant::where('id', (int) $merchant_id)->first(['id', 'name', 'photo_url', 'slogan', 'description', 'city_id', 'whatsapp_number']);

            $mc = $merchant->toArray();
            $cityname = $merchant->city->toArray();

            $merged_data = array_merge($mc, ['city_name' => $cityname['name']]);
            $total_product = $merchant->products()->count();

            $total_trx = count(static::getTotalTrx($merchant_id, 88));

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
        if (($key = array_search($value, $array, $strict)) !== FALSE) {
            unset($array[$key]);
        }
        return $array;
    }

    public static function getTotalTrx($merchant_id, $status_code, $daterange = [])
    {
        $data = Order::withCount(['progress' => function ($progress) use ($status_code){
            $progress->where('status', 1)->where('status_code', $status_code);
        }])->where('merchant_id', $merchant_id);

        if (count($daterange) == 2) {
            $data = $data->whereBetween('created_at', $daterange);
        }

        $data = $data->get()->toArray();
        return array_filter($data, function ($order){
            return $order['progress_count'] != 0;
        });
    }

    static function format_number($number)
    {
        if ($number >= 1000 && $number <= 999999) {
            return $number / 1000 . ' ribu';   // NB: you will want to round this
        }
        if ($number >= 1000000) {
            return $number / 1000000 . ' juta';   // NB: you will want to round this
        } else {
            return $number;
        }
    }

    public static function getListMerchant($limit = 10, $page = 1)
    {
        $data = Merchant::with(['province:id,name', 'city:id,name', 'district:id,name'])->get(['id', 'name', 'address', 'province_id', 'city_id', 'district_id', 'postal_code', 'photo_url'])->forget(['province_id', 'city_id', 'district_id']);
        foreach ($data as $merchant) {
            $merchant['url_deeplink'] = 'https://plnmarketplace.page.link/?link=https://plnmarketplace.page.link/profile-toko-seller?id=' . $merchant->id;
        }

        $result = static::paginate($data->toArray(), $limit, $page);
        return $result;
    }
}

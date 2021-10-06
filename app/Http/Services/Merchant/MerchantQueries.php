<?php

namespace App\Http\Services\Merchant;

use App\Http\Resources\Etalase\EtalaseCollection;
use App\Http\Resources\Etalase\EtalaseResource;
use App\Models\Etalase;
use App\Models\Merchant;
use App\Models\Order;
use Exception;
use stdClass;

class MerchantQueries{
    static $error_codes = [400, 401, 402, 403, 404, 405, 406, 407, 408, 409, 410, 411, 412, 413, 414, 415, 416, 417, 418, 421, 422, 423, 424, 425, 426, 428, 429, 431, 451, 500, 501, 502, 503, 504, 505, 506, 507, 508, 510, 511];

    public static function homepageProfile($merchant_id)
    {
        try {
            $merchant = Merchant::find($merchant_id);
            $orders = [];
            $orders['success'] = [];
            $orders['canceled'] = [];
            // dd($merchant->orders->progress);
            foreach ($merchant->orders as $order) {
                if ($order->progress->status == 1 && $order->progress->status_code == 88) {
                    array_push($orders['success'], $order);
                    // $orders['success'][] = $order;
                }
                if ($order->progress->status == 1 && $order->progress->status_code == 99) {
                    array_push($orders['canceled'], $order);
                    // $orders['canceled'][] = $order;
                }
            }

            // $iconcash

            // dd($orders);

            return [
                'data' => [
                    'merchant' => [
                        'id' => $merchant->id,
                        'name' => $merchant->name,
                        'image_url' => $merchant->photo_url,
                    ],
                    'transactions' => [
                        'total_transaction' => count(array_merge($orders['success'], $orders['canceled'])),
                        'total_success' => count($orders['success']),
                        'total_canceled' => count($orders['canceled'])
                    ]
                ]
            ];
            // dd(Order::where('id', 2)->first()->progress->toArray());
            // dd(collect($merchant->orders->toArray()));

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
            $merchant = Merchant::find($merchant_id);
            $base_data = $merchant->first(['id', 'name', 'photo_url', 'slogan', 'description', 'city_id']);
            dd($base_data);
            return [
                'merchant' => null,
                'meta_data' => [
                    'total_product' => null,
                    'total_transactions' => null,
                    'operational_hour' => null
                ]
            ];
        } catch (Exception $th) {
            if (in_array($th->getCode(), self::$error_codes)) {
                throw new Exception($th->getMessage(), $th->getCode());
            }
            throw new Exception($th->getMessage(), 500);
        }
    }
}

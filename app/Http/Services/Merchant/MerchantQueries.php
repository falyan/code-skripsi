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

            // dd($orders);

            return [
                'data' => [
                    'total_success' => count($orders['success']),
                    'total_canceled' => count($orders['canceled'])
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
}

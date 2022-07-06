<?php

namespace App\Http\Services\Merchant;

use App\Http\Resources\Etalase\EtalaseCollection;
use App\Http\Resources\Etalase\EtalaseResource;
use App\Http\Services\Discussion\DiscussionQueries;
use App\Http\Services\Review\ReviewQueries;
use App\Http\Services\Service;
use App\Models\Etalase;
use App\Models\Merchant;
use App\Models\MerchantBanner;
use App\Models\Order;
use App\Models\OrderProgress;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            $orders['total'] = $orders['success'] + $orders['canceled'];
            $orders['charts'] = [];

            // $date_from_daterange = array_map(function ($d) {
            //     return Carbon::parse($d)->toDateString();
            // }, CarbonPeriod::createFromArray($date['daterange'])->toArray());

            // foreach ($date_from_daterange as $d) {
            //     $total_trx = 0;
            //     $total_trx_amount = 0;
            //     foreach ($orders['success'] as $order) {
            //         foreach ($order['progress'] as $progress) {
            //             $order_date = Carbon::parse($progress['created_at'])->toDateString();
            //             if ($d == $order_date) {
            //                 $total_trx += 1;
            //                 $total_trx_amount += $order['total_amount'];
            //             }
            //         }
            //     }
            //     $orders['charts'][] = [
            //         'date' => $d,
            //         'total_trx' => $total_trx,
            //         'total_trx_amount' => $total_trx_amount
            //     ];
            // }

            // $order_before['success'] = static::getTotalTrx($merchant_id, '88', $date['before']);
            // $order_before['canceled'] = static::getTotalTrx($merchant_id, '09', $date['before']);
            // $order_before['total'] = array_merge($order_before['success'], $order_before['canceled']);

            // if (count($orders['success']) == 0) {
            //     $percentage_success = ['percent' => 0 . "%", 'status' => ''];
            // } elseif (count($orders['success']) > count($order_before['success']) || count($order_before['success']) == 0) {
            //     $percentage_success = [
            //         'percent' => round(((count($orders['success']) - count($order_before['success'])) / count($orders['success'])) * 100) . "%",
            //         'status' => 'up'
            //     ];
            // } else {
            //     $percentage_success = [
            //         'percent' => round(((count($order_before['success']) - count($orders['success'])) / count($order_before['success'])) * 100) . "%",
            //         'status' => 'down'
            //     ];
            // }

            // if (count($orders['canceled']) == 0) {
            //     $percentage_canceled = ['percent' => 0 . "%", 'status' => ''];
            // } elseif (count($orders['canceled']) > count($order_before['canceled']) || count($order_before['canceled']) == 0) {
            //     $percentage_canceled = [
            //         'percent' => round(((count($orders['canceled']) - count($order_before['canceled'])) / count($orders['canceled'])) * 100) . "%",
            //         'status' => "up"
            //     ];
            // } else {
            //     $percentage_canceled = [
            //         'percent' => round(((count($order_before['canceled']) - count($orders['canceled'])) / count($order_before['canceled'])) * 100) . "%",
            //         'status' => "down"
            //     ];
            // }

            // if (count($orders['total']) == 0) {
            //     $percentage_total = ['percent' => 0 . "%", 'status' => ''];
            // } elseif (count($orders['total']) > count($order_before['total']) || count($order_before['total']) == 0) {
            //     $percentage_total = [
            //         'percent' => round(((count($orders['total']) - count($order_before['total'])) / count($orders['total'])) * 100) . "%",
            //         'status' => "up"
            //     ];
            // } else {
            //     $percentage_total = [
            //         'percent' => round(((count($order_before['total']) - count($orders['total'])) / count($order_before['total'])) * 100) . "%",
            //         'status' => "down"
            //     ];
            // }

            return [
                'data' => [
                    'merchant' => $merchant,
                    'transactions' => [
                        'total_transaction' => $orders['total'],
                        'total_success' => $orders['success'],
                        'total_canceled' => $orders['canceled'],
                        // 'total_transaction_before' => count($order_before['total']),
                        // 'total_success_before' => count($order_before['success']),
                        // 'total_canceled_before' => count($order_before['canceled']),
                        'percentage_transaction' => ['percent' => 0 . "%", 'status' => ''],
                        'percentage_success' => ['percent' => 0 . "%", 'status' => ''],
                        'percentage_canceled' => ['percent' => 0 . "%", 'status' => ''],
                        'charts' => $orders['charts'],
                    ],
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
            $merchant = Merchant::where('id', (int) $merchant_id)
            ->with('banner')
            ->first(['id', 'name', 'photo_url', 'slogan', 'description', 'city_id', 'whatsapp_number']);

            $mc = $merchant->toArray();
            $cityname = $merchant->city->toArray();

            $merged_data = array_merge($mc, ['city_name' => $cityname['name']]);
            unset($merged_data['banner']);
            $total_product = $merchant->products->count();

            $total_trx = static::getTotalTrx($merchant_id, 88);

            $banner = $merchant->banner->map(function ($item) {
                return $item->url;
            });

            return [
                'merchant' => $merged_data,
                'banner' => $banner,
                'meta_data' => [
                    'total_product' =>(string) static::format_number((int) $total_product),
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

    public static function getActivity($merchant_id, $daterange = [])
    {
        Log::info("T00001", [
            'path_url' => "merchant.activity",
            'query' => [],
            'body' => Carbon::now('Asia/Jakarta'),
            'response' => ''
        ]);
        try {
            $merchant = Merchant::with(['city'])->find($merchant_id);
            Log::info("T00001", [
                'path_url' => "select.merchant",
                'query' => [],
                'body' => Carbon::now('Asia/Jakarta'),
                'response' => $merchant
            ]);
            $reviewQueries = new ReviewQueries();
            $discussionQueries = new DiscussionQueries();
            $data = [];

            $data['new_order'] = (static::getTotalTrx($merchant_id, '01', $daterange));
            $data['on_progress'] = (static::getTotalTrx($merchant_id, '03', $daterange));
            $data['ready_to_deliver'] = (static::getTotalTrx($merchant_id, '02', $daterange));
            $data['complained_order'] = $reviewQueries->getCountReviewDoneByRate('merchant_id', $merchant_id, 2, '<=', $daterange);
            $data['new_review'] = $reviewQueries->getCountReviewDoneByRate('merchant_id', $merchant_id, null, null, $daterange);
            $data['new_discussion'] = count($discussionQueries->getListDiscussionDoneByUnread($merchant_id, 'unread', $daterange)['data']);
            $data['success_order'] = static::getTotalTrx($merchant_id, '88', $daterange);
            $data['canceled_order'] = static::getTotalTrx($merchant_id, '09', $daterange);

            return [
                'data' => [
                    'merchant' => $merchant,
                    'transactions' => $data
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

        $data = Order::with('progress_active')
        ->whereHas('progress_active', function ($query) use ($status_code, $daterange) {
            $query->where('status_code', $status_code);
            $query->when(count($daterange) == 2, function($q) use ($daterange) {
                return $q->whereBetween('created_at', $daterange);
            });
            return $query;
        })
        ->where('merchant_id', $merchant_id);

        $data = $data->count();
        
        Log::info("T00001", [
            'path_url' => "count.order",
            'query' => [],
            'body' => Carbon::now('Asia/Jakarta'),
            'response' => $data
        ]);

        return $data;
    }

    // public function getTotalReview($merchant_id, $rate)
    // {
    //     $order = Order::with([
    //         'detail' => function ($product) {
    //             $product->with(['product' => function ($j) {
    //                 $j->with(['product_photo']);
    //             }]);
    //         }, 'progress_active', 'merchant', 'delivery', 'buyer'
    //     ])->where([
    //         [$column_name, $related_id],
    //     ])->whereHas('progress_active', function ($j) {
    //         $j->whereIn('status_code', [88]);
    //     })->whereHas('review')->orderBy('created_at', 'desc')->get();
    // }

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
    
    public static function getBanner($merchant_id)
    {
        $data = MerchantBanner::where('merchant_id', $merchant_id)->get();

        if ($data->count() < 1) {
            return [
                'success' => false,
                'message' => 'Data Banner masih kosong',
                'data' => null
            ];
        }

        $result = [
            'data' => $data
        ];
        return $result;
    }
}

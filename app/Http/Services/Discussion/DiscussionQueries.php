<?php

namespace App\Http\Services\Discussion;

use App\Models\DiscussionMaster;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class DiscussionQueries
{
    public function getListDiscussion($customer_id = null, $merchant_id = null, $status = null, $limit = 10, $page = 1)
    {
        $discussion_list = null;
        if ($customer_id != null) {
            if ($status == null) {
                $discussion_list = DiscussionMaster::with(['customer', 'merchant', 'product' => function ($product) {
                    $product->with(['product_photo']);
                }, 'discussion_response' => function ($response) {
                    $response->with(['customer', 'merchant']);
                }])->where('customer_id', $customer_id)->orderBy('updated_at', 'desc')->get();
            }

            if ($status == 'unread') {
                $discussion_list = DiscussionMaster::with(['customer', 'merchant', 'product' => function ($product) {
                    $product->with(['product_photo']);
                }, 'discussion_response' => function ($response) {
                    $response->with(['customer', 'merchant']);
                }])->where('customer_id', $customer_id)
                    ->whereHas('discussion_response', function ($response) {
                        $response->where('is_read_customer', false);
                    })->orderBy('updated_at', 'desc')->get();
            }

            if ($status == 'read') {
                $discussion_list = DiscussionMaster::with(['customer', 'merchant', 'product' => function ($product) {
                    $product->with(['product_photo']);
                }, 'discussion_response' => function ($response) {
                    $response->with(['customer', 'merchant']);
                }])->where('customer_id', $customer_id)
                    ->whereDoesntHave('discussion_response', function ($response) {
                        $response->where('is_read_customer', false);
                    })->orderBy('updated_at', 'desc')->get();
            }
        }

        $data = static::paginate($discussion_list->toArray(), $limit, $page);
        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data diskusi';
        $response['data'] = $data;

        return $response;
    }

    public function getListDiscussionSeller($merchant_id = null, $status = null, $limit = 10, $page = 1)
    {
        $discussion_list = null;
        if ($merchant_id != null) {
            if ($status == null) {
                $discussion_list = DiscussionMaster::with(['customer', 'merchant', 'product' => function ($product) {
                    $product->with(['product_photo']);
                }, 'discussion_response' => function ($response) {
                    $response->with(['customer', 'merchant']);
                }])->where('merchant_id', $merchant_id)->orderBy('updated_at', 'desc')->get();
            }

            if ($status == 'unread') {
                $discussion_list = DiscussionMaster::with(['customer', 'merchant', 'product' => function ($product) {
                    $product->with(['product_photo']);
                }, 'discussion_response' => function ($response) {
                    $response->with(['customer', 'merchant']);
                }])->where([['merchant_id', $merchant_id], ['is_read_merchant', false]])->orderBy('updated_at', 'desc')->get();
            }

            if ($status == 'read') {
                $discussion_list = DiscussionMaster::with(['customer', 'merchant', 'product' => function ($product) {
                    $product->with(['product_photo']);
                }, 'discussion_response' => function ($response) {
                    $response->with(['customer', 'merchant']);
                }])->where([['merchant_id', $merchant_id], ['is_read_merchant', true]])->orderBy('updated_at', 'desc')->get();
            }
        }

        $data = static::paginate($discussion_list->toArray(), $limit, $page);
        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data diskusi';
        $response['data'] = $data;

        return $response;
    }

    public function getListDiscussionDoneByUnread($merchant_id = null, $status = null, $daterange = [])
    {
        // Default daterange
        if (empty($daterange)) {
            $from = Carbon::now()->timezone('Asia/Jakarta')->subMonth();
            $to = Carbon::now()->timezone('Asia/Jakarta');
            $daterange = [$from->format('Y-m-d 00:00:00'), $to->toDateTimeString()];
        }

        $discussions = null;
        if ($merchant_id != null) {
            if ($status == null) {
                $discussions = DiscussionMaster::with(['customer', 'merchant', 'product' => function ($product) {
                    $product->with(['product_photo']);
                }, 'discussion_response' => function ($response) {
                    $response->with(['customer', 'merchant']);
                }])->when(count($daterange) == 2, function ($q) use ($daterange) {
                    $q->whereBetween('created_at', $daterange);
                })->where('merchant_id', $merchant_id)->get();
            }

            if ($status == 'unread') {
                $discussions = DiscussionMaster::with(['customer', 'merchant', 'product' => function ($product) {
                    $product->with(['product_photo']);
                }, 'discussion_response' => function ($response) {
                    $response->with(['customer', 'merchant']);
                }])->when(count($daterange) == 2, function ($q) use ($daterange) {
                    $q->whereBetween('created_at', $daterange);
                })->where([['merchant_id', $merchant_id], ['is_read_merchant', false]])->get();
            }

            if ($status == 'read') {
                $discussions = DiscussionMaster::with(['customer', 'merchant', 'product' => function ($product) {
                    $product->with(['product_photo']);
                }, 'discussion_response' => function ($response) {
                    $response->with(['customer', 'merchant']);
                }])->when(count($daterange) == 2, function ($q) use ($daterange) {
                    $q->whereBetween('created_at', $daterange);
                })->where([['merchant_id', $merchant_id], ['is_read_merchant', true]])->get();
            }
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data diskusi';
        $response['data'] = $discussions;

        return $response;
    }

    public function getListDiscussionByProduct($product_id, $limit = 10, $page = 1)
    {
        $discussion_list = DiscussionMaster::with(['customer', 'merchant', 'product' => function ($product) {
            $product->with(['product_photo']);
        }, 'discussion_response' => function ($response) {
            $response->with(['customer', 'merchant']);
        }])->where('product_id', $product_id)->orderBy('updated_at', 'desc')->get();

        $data = static::paginate($discussion_list->toArray(), $limit, $page);
        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan data diskusi';
        $response['data'] = $data;

        return $response;
    }

    public function getDiscussionByMasterId($id){
        $discussion = DiscussionMaster::with(['customer', 'merchant', 'product' => function ($product) {
            $product->with(['product_photo']);
        }, 'discussion_response' => function ($response) {
            $response->with(['customer', 'merchant']);
        }])->where('id', $id)->first();

        if (empty($discussion)){
            $response['success'] = false;
            $response['message'] = 'Data diskusi dengan id '. $id . ' tidak ditemukan';
            $response['data'] = $discussion;

            return $response;
        }

        $response['success'] = true;
        $response['message'] = 'Data diskusi berhasil didapatkan';
        $response['data'] = $discussion;

        return $response;
    }

    public function countUnreadDiscussion($customer_id){
        $count_discussion = DiscussionMaster::with(['discussion_response'])
            ->where('customer_id', $customer_id)
            ->whereHas('discussion_response', function ($response) {
                $response->where('is_read_customer', false);
            })->orderBy('updated_at', 'desc')->count();

        $response['success'] = true;
        $response['message'] = 'Berhasil mendapatkan jumlah diskusi';
        $response['data'] = [
            'count_discussion' => $count_discussion
        ];

        return $response;
    }

    static function paginate(array $items, $perPage = 10, $page = 1, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);

        $paginated = new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
        $modified = [];
        foreach ($paginated->items() as $key) {
            array_push($modified, $key);
        }

        return [
            'current_page'    => $paginated->currentPage(),
            'data'            => $modified,
            'first_page_url'  => "/?page=1",
            'from'            => $paginated->firstItem(),
            'last_page'       => $paginated->lastPage(),
            'last_page_url'   => "/?page=" . $paginated->lastPage(),
            'links'           => $paginated->linkCollection(),
            'next_page_url'   => $paginated->nextPageUrl(),
            'path'            => $paginated->path(),
            'per_page'        => $paginated->perPage(),
            'prev_page_url'   => $paginated->previousPageUrl(),
            'to'              => count($modified),
            'total'           => $paginated->total()
        ];
    }
}

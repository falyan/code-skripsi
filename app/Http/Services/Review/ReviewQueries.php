<?php

namespace App\Http\Services\Review;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Review;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class ReviewQueries{
    public function getListReview($column_name, $related_id){
        $order = Order::with([
            'detail' => function ($product) {
                $product->with(['product' => function ($j) {
                    $j->with(['product_photo']);
                }]);
            }, 'progress_active', 'merchant', 'delivery', 'buyer'
        ])->where([
            [$column_name, $related_id],
        ])->whereHas('progress_active', function ($j) {
            $j->whereIn('status_code', [88]);
        })->orderBy('created_at', 'desc')->paginate(10);

        $detail_array = [];
        foreach ($order as $o){
            foreach ($o->detail as $detail){
                $review = Review::with(['review_photo', 'customer'])->where('order_id', $o->id)->where('customer_id', $o->buyer_id)
                    ->where('merchant_id', $o->merchant_id)->where('product_id', $detail->product_id)->first();
                $detail->merchant = $o->merchant;
                $detail->review = $review;
                array_push($detail_array, $detail);
            }
        }

        $response['success'] = true;
        $response['message'] = 'Data review berhasil didapatkan!';
        $response['data'] = static::paginate($detail_array, 10, 1);

        return $response;
    }

    public function getDetailReview($review_id){
        $review = Review::with(['review_photo', 'merchant', 'customer', 'product' => function ($product){
            $product->with(['product_photo']);
        }, 'order' => function ($order){
            $order->with(['detail']);
        }])->findOrFail($review_id);

        $response['success'] = true;
        $response['message'] = 'Detail review berhasil didapatkan!';
        $response['data'] = $review;

        return $response;
    }

    public function getListReviewDone($column_name, $related_id){
        $order = Order::with([
            'detail' => function ($product) {
                $product->with(['product' => function ($j) {
                    $j->with(['product_photo']);
                }]);
            }, 'progress_active', 'merchant', 'delivery', 'buyer'
        ])->where([
            [$column_name, $related_id],
        ])->whereHas('progress_active', function ($j) {
            $j->whereIn('status_code', [88]);
        })->whereHas('review')->orderBy('created_at', 'desc')->paginate(10);

        $detail_array = [];
        foreach ($order as $o){
            foreach ($o->detail as $detail){
                $review = Review::with(['review_photo', 'customer'])->where('order_id', $o->id)->where('customer_id', $o->buyer_id)
                    ->where('merchant_id', $o->merchant_id)->where('product_id', $detail->product_id)->first();
                if ($review != null){
                    $detail->merchant = $o->merchant;
                    $detail->review = $review;
                    array_push($detail_array, $detail);
                }
            }
        }

        $response['success'] = true;
        $response['message'] = 'Data review berhasil didapatkan!';
        $response['data'] = static::paginate($detail_array, 10, 1);

        return $response;
    }

    public function getListReviewUndone($column_name, $related_id){
        $order = Order::with([
            'detail' => function ($product) {
                $product->with(['product' => function ($j) {
                    $j->with(['product_photo']);
                }]);
            }, 'progress_active', 'merchant', 'delivery', 'buyer'
        ])->where([
            [$column_name, $related_id],
        ])->whereHas('progress_active', function ($j) {
            $j->whereIn('status_code', [88]);
        })->whereDoesntHave('review')->orderBy('created_at', 'desc')->paginate(10);

        $detail_array = [];
        foreach ($order as $o){
            foreach ($o->detail as $detail){
                $review = Review::with(['review_photo', 'customer'])->where('order_id', $o->id)->where('customer_id', $o->buyer_id)
                    ->where('merchant_id', $o->merchant_id)->where('product_id', $detail->product_id)->first();
                if ($review == null){
                    $detail->merchant = $o->merchant;
                    $detail->review = $review;
                    array_push($detail_array, $detail);
                }
            }
        }

        $response['success'] = true;
        $response['message'] = 'Data review berhasil didapatkan!';
        $response['data'] = static::paginate($detail_array, 10, 1);

        return $response;
    }

    public function getListReviewDoneReply($column_name, $related_id){
        $order = Order::with([
            'detail' => function ($product) {
                $product->with(['product' => function ($j) {
                    $j->with(['product_photo']);
                }]);
            }, 'progress_active', 'merchant', 'delivery', 'buyer'
        ])->where([
            [$column_name, $related_id],
        ])->whereHas('progress_active', function ($j) {
            $j->whereIn('status_code', [88]);
        })->whereHas('review', function ($r){
            $r->whereNotNull('reply_message');
        })->orderBy('created_at', 'desc')->paginate(10);

        $detail_array = [];
        foreach ($order as $o){
            foreach ($o->detail as $detail){
                $review = Review::with(['review_photo', 'customer'])->where('order_id', $o->id)->where('customer_id', $o->buyer_id)
                    ->where('merchant_id', $o->merchant_id)->where('product_id', $detail->product_id)->first();
                if ($review['reply_message'] != null){
                    $detail->merchant = $o->merchant;
                    $detail->review = $review;
                    array_push($detail_array, $detail);
                }
            }
        }

        $response['success'] = true;
        $response['message'] = 'Data review berhasil didapatkan!';
        $response['data'] = static::paginate($detail_array, 10, 1);

        return $response;
    }

    public function getListReviewDoneUnreply($column_name, $related_id){
        $order = Order::with([
            'detail' => function ($product) {
                $product->with(['product' => function ($j) {
                    $j->with(['product_photo']);
                }]);
            }, 'progress_active', 'merchant', 'delivery', 'buyer'
        ])->where([
            [$column_name, $related_id],
        ])->whereHas('progress_active', function ($j) {
            $j->whereIn('status_code', [88]);
        })->whereHas('review', function ($r){
            $r->where('reply_message', null);
        })->orderBy('created_at', 'desc')->paginate(10);

        $detail_array = [];
        foreach ($order as $o){
            foreach ($o->detail as $detail){
                $review = Review::with(['review_photo', 'customer'])->where('order_id', $o->id)->where('customer_id', $o->buyer_id)
                    ->where('merchant_id', $o->merchant_id)->where('product_id', $detail->product_id)->first();
                if ($review != null && $review['reply_message'] == null){
                    $detail->merchant = $o->merchant;
                    $detail->review = $review;
                    array_push($detail_array, $detail);
                }
            }
        }

        $response['success'] = true;
        $response['message'] = 'Data review berhasil didapatkan!';
        $response['data'] = static::paginate($detail_array, 10, 1);

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

<?php

namespace App\Http\Services\Review;

use App\Models\Order;
use App\Models\Review;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class ReviewQueries
{
    public function getListReview($column_name, $related_id, $limit = 10, $page = 1)
    {
        if ($column_name == 'merchant_id') {
            $review = Review::with([
                'product', 'order', 'order.delivery', 'review_photo',
                'customer', 'merchant', 'product.product_photo',
            ])->whereHas('order', function ($q) {
                return $q->whereHas('progress_active', function ($j) {
                    return $j->where('status_code', 88);
                });
            })
                ->where('review.' . $column_name, $related_id, )
                ->where('status', 1)
                ->orderBy('review.created_at', 'desc')
                ->join('order_detail', function ($q) {
                    $q->on('order_detail.order_id', '=', 'review.order_id')
                        ->on('order_detail.product_id', '=', 'review.product_id');
                })->select('review.*', 'order_detail.quantity', 'order_detail.total_price');

            $data = $review->paginate($limit);
        } else {
            $order = Order::with([
                'detail' => function ($product) {
                    $product->with(['product' => function ($j) {
                        $j->with(['product_photo']);
                    }]);
                }, 'progress_active', 'merchant', 'delivery', 'buyer',
            ])->where([
                [$column_name, $related_id],
            ])->whereHas('progress_active', function ($j) {
                $j->whereIn('status_code', [88]);
            })->orderBy('created_at', 'desc')->paginate($limit);

            $detail_array = [];
            foreach ($order as $o) {
                foreach ($o->detail as $detail) {
                    $review = Review::with(['review_photo', 'customer'])->where('order_id', $o->id)->where('customer_id', $o->buyer_id)
                        ->where('status', 1)->where('merchant_id', $o->merchant_id)->where('product_id', $detail->product_id)->first();
                    $detail->merchant = $o->merchant;
                    $detail->review = $review;
                    array_push($detail_array, $detail);
                }
            }

            $data = static::paginate($detail_array, $limit, 1);
        }

        $response['success'] = true;
        $response['message'] = 'Data review berhasil didapatkan!';
        $response['data'] = $data;

        return $response;
    }

    public function getDetailReview($review_id)
    {
        $review = Review::with(['review_photo', 'merchant', 'customer',
            'product' => function ($product) {
                $product->with(['product_photo']);
            }, 'order' => function ($order) {
                $order->with(['detail']);
            }, 'order.progress_active'])->where('status', 1)->findOrFail($review_id);

        $response['success'] = true;
        $response['message'] = 'Detail review berhasil didapatkan!';
        $response['data'] = $review;

        return $response;
    }

    public function getListReviewDone($column_name, $related_id, $limit = 10, $page = 1)
    {

        if ($column_name == 'merchant_id') {
            $review = Review::with([
                'product', 'order', 'order.delivery', 'review_photo',
                'customer', 'merchant', 'product.product_photo',
            ])->whereHas('order', function ($q) {
                return $q->whereHas('progress_active', function ($j) {
                    return $j->where('status_code', 88);
                });
            })
                ->where('review.' . $column_name, $related_id)
                ->where('status', 1)
                ->orderBy('review.created_at', 'desc')
                ->join('order_detail', function ($q) {
                    $q->on('order_detail.order_id', '=', 'review.order_id')
                        ->on('order_detail.product_id', '=', 'review.product_id');
                })->select('review.*', 'order_detail.quantity', 'order_detail.total_price');

            $data = $review->paginate($limit);
        } else {
            $order = Order::with([
                'detail' => function ($product) {
                    $product->with(['product' => function ($j) {
                        $j->with(['product_photo']);
                    }]);
                }, 'progress_active', 'merchant', 'delivery', 'buyer',
            ])->where([
                [$column_name, $related_id],
            ])->whereHas('progress_active', function ($j) {
                $j->whereIn('status_code', [88]);
            })->whereHas('review')->orderBy('created_at', 'desc')->paginate($limit);

            $detail_array = [];
            foreach ($order as $o) {
                foreach ($o->detail as $detail) {
                    $review = Review::with(['review_photo', 'customer'])->where('status', 1)->where('order_id', $o->id)->where('customer_id', $o->buyer_id)
                        ->where('merchant_id', $o->merchant_id)->where('product_id', $detail->product_id)->first();
                    if ($review != null) {
                        $detail->merchant = $o->merchant;
                        $detail->review = $review;
                        array_push($detail_array, $detail);
                    }
                }
            }

            $data = static::paginate($detail_array, $limit, 1);
        }

        $response['success'] = true;
        $response['message'] = 'Data review berhasil didapatkan!';
        $response['data'] = $data;

        return $response;
    }

    public function getCountReviewDoneByRate($column_name, $related_id, $rate = null, $operator = null, $daterange = [])
    {
        // Default daterange
        // if ($rate == null && empty($daterange)) {
        //     $from = Carbon::now()->timezone('Asia/Jakarta')->subMonth();
        //     $to = Carbon::now()->timezone('Asia/Jakarta');
        //     $daterange = [$from->format('Y-m-d 00:00:00'), $to->toDateTimeString()];
        // }

        $review = Review::with(['order'])
            ->whereHas('order', function ($q) use ($daterange) {
                return $q->whereHas('progress_active', function ($j) {
                    return $j->where('status_code', 88);
                })
                    ->when(count($daterange) == 2, function ($q) use ($daterange) {
                        return $q->whereBetween('created_at', $daterange);
                    })
                ;
            })
            ->where('status', 1)
            ->where($column_name, $related_id)
            ->where('reply_message', null)
            ->when($rate != null && $operator != null, function ($q) use ($rate, $operator) {
                if ($rate < 1) {
                    $rate = 1;
                }

                if ($rate > 5) {
                    $rate = 5;
                }

                $q->where('rate', $operator, $rate);
            })
            ->orderBy('created_at', 'desc');

        $data = $review->count();

        return $data;
    }

    public function getCountReviewUnreply($column_name, $related_id, $rate = null, $operator = null, $daterange = [])
    {
        $review = Review::with([
            'product', 'order', 'order.delivery', 'review_photo',
            'customer', 'merchant', 'product.product_photo',
        ])
            ->whereHas('order', function ($q) {
                return $q->whereHas('progress_active', function ($j) {
                    return $j->where('status_code', 88);
                });
            })
            ->where('status', 1)
            ->where('review.' . $column_name, $related_id)
            ->where('review.reply_message', null)
            ->orderBy('review.created_at', 'desc')
            ->join('order_detail', function ($q) {
                $q->on('order_detail.order_id', '=', 'review.order_id')
                    ->on('order_detail.product_id', '=', 'review.product_id');
            })->select('review.*', 'order_detail.quantity', 'order_detail.total_price');

        return $review->get();
    }

    public function getListReviewUndone($column_name, $related_id)
    {
        $order = Order::with([
            'detail' => function ($product) {
                $product->with(['product' => function ($j) {
                    $j->with(['product_photo']);
                }]);
            }, 'progress_active', 'merchant', 'delivery', 'buyer',
        ])->where([
            [$column_name, $related_id],
        ])->whereHas('progress_active', function ($j) {
            $j->whereIn('status_code', [88]);
        })->whereDoesntHave('review')->orderBy('created_at', 'desc')->paginate(10);

        $detail_array = [];
        foreach ($order as $o) {
            foreach ($o->detail as $detail) {
                $review = Review::with(['review_photo', 'customer'])->where('status', 1)->where('order_id', $o->id)->where('customer_id', $o->buyer_id)
                    ->where('merchant_id', $o->merchant_id)->where('product_id', $detail->product_id)->first();
                if ($review == null) {
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

    public function getListReviewDoneReply($column_name, $related_id, $limit = 10, $page = 1)
    {
        if ($column_name == 'merchant_id') {
            $review = Review::with([
                'product', 'order', 'order.delivery', 'review_photo',
                'customer', 'merchant', 'product.product_photo',
            ])->where('status', 1)
                ->whereHas('order', function ($q) {
                    return $q->whereHas('progress_active', function ($j) {
                        return $j->where('status_code', 88);
                    });
                })
                ->where('review.' . $column_name, $related_id)
                ->whereNotNull('review.reply_message')
                ->orderBy('review.created_at', 'desc')
                ->join('order_detail', function ($q) {
                    $q->on('order_detail.order_id', '=', 'review.order_id')
                        ->on('order_detail.product_id', '=', 'review.product_id');
                })->select('review.*', 'order_detail.quantity', 'order_detail.total_price');

            $data = $review->paginate($limit);
        } else {
            $order = Order::with([
                'detail' => function ($product) {
                    $product->with(['product' => function ($j) {
                        $j->with(['product_photo']);
                    }]);
                }, 'progress_active', 'merchant', 'delivery', 'buyer',
            ])->where([
                [$column_name, $related_id],
            ])->whereHas('progress_active', function ($j) {
                $j->whereIn('status_code', [88]);
            })->whereHas('review', function ($r) {
                $r->whereNotNull('reply_message');
            })->orderBy('created_at', 'desc')->paginate($limit);

            $detail_array = [];
            foreach ($order as $o) {
                foreach ($o->detail as $detail) {
                    $review = Review::with(['review_photo', 'customer'])->where('status', 1)->where('order_id', $o->id)->where('customer_id', $o->buyer_id)
                        ->where('merchant_id', $o->merchant_id)->where('product_id', $detail->product_id)->first();
                    if ($review['reply_message'] != null) {
                        $detail->merchant = $o->merchant;
                        $detail->review = $review;
                        array_push($detail_array, $detail);
                    }
                }
            }

            $data = static::paginate($detail_array, $limit, 1);
        }

        $response['success'] = true;
        $response['message'] = 'Data review berhasil didapatkan!';
        $response['data'] = $data;

        return $response;
    }

    public function getListReviewDoneUnreply($column_name, $related_id, $limit = 10, $page = 1)
    {
        if ($column_name == 'merchant_id') {
            $review = Review::with([
                'product', 'order', 'order.delivery', 'review_photo',
                'customer', 'merchant', 'product.product_photo',
            ])->whereHas('order', function ($q) {
                return $q->whereHas('progress_active', function ($j) {
                    return $j->where('status_code', 88);
                });
            })
                ->where('status', 1)
                ->where('review.' . $column_name, $related_id)
                ->where('review.reply_message', null)
                ->orderBy('review.created_at', 'desc')
                ->join('order_detail', function ($q) {
                    $q->on('order_detail.order_id', '=', 'review.order_id')
                        ->on('order_detail.product_id', '=', 'review.product_id');
                })->select('review.*', 'order_detail.quantity', 'order_detail.total_price');

            $data = $review->paginate($limit);
        } else {
            $order = Order::with([
                'detail' => function ($product) {
                    $product->with(['product' => function ($j) {
                        $j->with(['product_photo']);
                    }]);
                }, 'progress_active', 'merchant', 'delivery', 'buyer',
            ])->where([
                [$column_name, $related_id],
            ])->whereHas('progress_active', function ($j) {
                $j->whereIn('status_code', [88]);
            })->whereHas('review', function ($r) {
                $r->where('reply_message', null);
            })->orderBy('created_at', 'desc')->paginate(10);

            $detail_array = [];
            foreach ($order as $o) {
                foreach ($o->detail as $detail) {
                    $review = Review::with(['review_photo', 'customer'])->where('status', 1)->where('order_id', $o->id)->where('customer_id', $o->buyer_id)
                        ->where('merchant_id', $o->merchant_id)->where('product_id', $detail->product_id)->first();
                    if ($review != null && $review['reply_message'] == null) {
                        $detail->merchant = $o->merchant;
                        $detail->review = $review;
                        array_push($detail_array, $detail);
                    }
                }
            }

            $data = static::paginate($detail_array, $limit, 1);
        }

        $response['success'] = true;
        $response['message'] = 'Data review berhasil didapatkan!';
        $response['data'] = $data;

        return $response;
    }

    public function getListReviewByTransaction($order_id)
    {
        $order = Order::with(['detail' => function ($product) use ($order_id) {
            $product->with(['product' => function ($j) use ($order_id) {
                $j->with(['merchant', 'product_photo', 'reviews' => function ($review) use ($order_id) {
                    $review->whereIn('order_id', [$order_id])->with(['review_photo']);
                }]);
                $review->where('status', 1);
            }]);
        }, 'buyer'])->where('id', $order_id)->get();

        $response['success'] = true;
        $response['message'] = 'Data review berhasil didapatkan!';
        $response['data'] = static::paginate($order->toArray(), 10, 1);

        return $response;
    }

    public static function paginate(array $items, $perPage = 10, $page = 1, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);

        $paginated = new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
        $modified = [];
        foreach ($paginated->items() as $key) {
            array_push($modified, $key);
        }

        return [
            'current_page' => $paginated->currentPage(),
            'data' => $modified,
            'first_page_url' => "/?page=1",
            'from' => $paginated->firstItem(),
            'last_page' => $paginated->lastPage(),
            'last_page_url' => "/?page=" . $paginated->lastPage(),
            'links' => $paginated->linkCollection(),
            'next_page_url' => $paginated->nextPageUrl(),
            'path' => $paginated->path(),
            'per_page' => $paginated->perPage(),
            'prev_page_url' => $paginated->previousPageUrl(),
            'to' => count($modified),
            'total' => $paginated->total(),
        ];
    }
}

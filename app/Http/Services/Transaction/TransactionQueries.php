<?php

namespace App\Http\Services\Transaction;

use App\Http\Services\Service;
use App\Models\CustomerDiscount;
use App\Models\DeliveryDiscount;
use App\Models\Order;
use Carbon\Carbon;

class TransactionQueries extends Service
{
    public function getTransaction($column_name, $column_value, $limit = 10, $filter = [], $page = 1)
    {
        $data = Order::with([
            'detail' => function ($product) {
                $product->with(['product' => function ($j) {
                    $j->with(['product_photo']);
                }]);
            }, 'progress_active', 'merchant', 'delivery', 'buyer'
        ])->where($column_name, $column_value)->when($column_name == 'merchant_id', function($query){
            $query->whereHas('progress_active', function($q){
                $q->whereNotIn('status_code', [99]);
            });
        })->orderBy('created_at', 'desc');

        $data = $this->filter($data, $filter);
        $data = $data->get();

        $data = static::paginate($data->toArray(), $limit, $page);

        return $data;
    }

    public function getTransactionWithStatusCode($column_name, $column_value, $status_code, $limit = 10, $filter = [], $page = 1)
    {
        $data = Order::with([
            'detail' => function ($product) {
                $product->with(['product' => function ($j) {
                    $j->with(['product_photo']);
                }]);
            }, 'progress_active', 'merchant', 'delivery', 'buyer'
        ])->where([
            [$column_name, $column_value],
        ])->whereHas('progress_active', function ($j) use ($status_code) {
            $j->whereIn('status_code', $status_code);
        })->when($column_name == 'merchant_id', function($query){
            $query->whereHas('progress_active', function($q){
                $q->whereNotIn('status_code', [99]);
            });
        })->orderBy('created_at', 'desc');


        $data = $this->filter($data, $filter);
        $data = $data->get();

        $data = static::paginate($data->toArray(), $limit, $page);

        return $data;
    }

    public function getDetailTransaction($id)
    {
        $data = Order::with([
            'detail' => function ($product) {
                $product->with(['product' => function ($j) {
                    $j->with(['product_photo']);
                }]);
            }, 'progress', 'merchant' => function ($merchant) {
                $merchant->with(['province', 'city', 'district']);
            }, 'delivery' => function ($region) {
                $region->with(['city', 'district']);
            }, 'buyer', 'payment', 'review' => function ($review){
                $review->with(['review_photo']);
            }])->find($id);

        $data->iconpay_product_id = static::$productid;

        return $data;
    }

    public function searchTransaction($column_name, $column_value, $keyword, $limit = 0, $filter = [], $page = 1)
    {
        $data = Order::with([
            'detail' => function ($product) {
                $product->with(['product' => function ($j) {
                    $j->with(['product_photo']);
                }]);
            }, 'progress_active', 'merchant', 'delivery', 'buyer'
        ])
            ->leftjoin('order_detail', 'order_detail.order_id', '=', 'order.id')
            ->leftjoin('product', 'order_detail.product_id', '=', 'product.id')
            ->leftjoin('merchant', 'merchant.id', '=', 'order.merchant_id')
            ->where('order.' . $column_name, $column_value)
            ->orWhere(function ($q) use ($keyword) {
                $q->where('merchant.name', 'ILIKE', '%' . $keyword . '%')
                    ->orWhere('product.name', 'ILIKE', '%' . $keyword . '%')
                    ->orWhere('order.trx_no', 'ILIKE', '%' . $keyword . '%');
            })
            ->orderBy('created_at', 'desc');

        $data = $this->filter($data, $filter);
        $data = $data->get();

        $data = static::paginate($data->toArray(), $limit, $page);
    }

    public function getStatusOrder($id)
    {
        $data = Order::with(['progress_active'])->find($id);
        return $data;
    }

    public function getDeliveryDiscount(){
        $data = DeliveryDiscount::where('id', '1')->where('is_active', true)->first();

        if (empty($data)) {
            $data = new DeliveryDiscount();
            $data->discount_amount = 0;
        }

        return $data;
    }

    public function getCustomerDiscount($user_id, $email){
        $discount = 0;
        $now = Carbon::now('Asia/Jakarta');
        $data = CustomerDiscount::where('customer_reference_id', $user_id)->orWhere('customer_reference_id', $email)
            ->where('is_used', false)->where('expired_date', '>=', $now)->first();

        if (!empty($data)){
            $discount = (int) $data->amount;
        }

        return $discount;
    }

    public function filter($model, $filter)
    {
        if (count($filter) > 0) {
            $status = $filter['status'] ?? null;
            $start_date = $filter['start_date'] ?? null;
            $end_date = $filter['end_date'] ?? null;

            $data = $model->when(!empty($start_date), function ($query) use ($start_date) {
                $query->where('created_at', '>=', $start_date);
            })->when(!empty($end_date), function ($query) use ($end_date) {
                $query->where('created_at', '<=', $end_date);
            })->when(!empty($status), function ($query) use ($status) {
                $query->whereHas('progress_active', function ($j) use ($status) {
                    if (strpos($status, ',')) {
                        $j->whereIn('status_code', explode(',', strtolower($status)));
                    } else {
                        $j->where('status_code', $status);
                    }
                });
            });

            return $data;
        } else {
            return $model;
        }
    }
}

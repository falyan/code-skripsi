<?php

namespace App\Http\Services\Transaction;

use App\Http\Services\Service;
use App\Models\Order;

class TransactionQueries extends Service
{
    public function getTransaction($column_name, $column_value)
    {
        $data = Order::with([
            'detail' => function ($product){
                $product->with(['product' => function($j){
                    $j->with(['product_photo']);
                }]);
            }, 'progress_active', 'merchant', 'delivery', 'buyer'
        ])->where($column_name, $column_value)->paginate(10);
        return $data;
    }

    public function getTransactionWithStatusCode($column_name, $column_value, $status_code)
    {
        $data = Order::with([
            'detail' => function ($product){
                $product->with(['product' => function($j){
                    $j->with(['product_photo']);
                }]);
            }, 'progress_active', 'merchant', 'delivery', 'buyer'
        ])->where([
            [$column_name, $column_value],
        ])->whereHas('progress_active', function ($j) use ($status_code) {
            $j->whereIn('status_code', $status_code);
        })->paginate(10);
        return $data;
    }

    public function getDetailTransaction($order_id)
    {
        $data = Order::with([
            'detail' => function ($product){
                $product->with(['product' => function($j){
                    $j->with(['product_photo']);
                }]);
            }, 'progress', 'merchant' => function($merchant){
                $merchant->with(['province', 'city', 'district']);
            }, 'delivery' => function($region){
                $region->with(['city', 'district']);
            }, 'buyer'
        ])->find($order_id);
        return $data;
    }

    public function searchTransaction($column_name, $column_value, $keyword)
    {
        $data = Order::with([
                'detail' => function ($product){
                    $product->with(['product' => function($j){
                        $j->with(['product_photo']);
                    }]);
                }, 'progress_active', 'merchant', 'delivery', 'buyer'
            ])
            ->leftjoin('order_detail', 'order_detail.order_id', '=', 'order.id')
            ->leftjoin('product', 'order_detail.product_id', '=', 'product.id')
            ->leftjoin('merchant', 'merchant.id', '=', 'order.merchant_id')
            ->where('order.'.$column_name, $column_value)
            ->orWhere(function($q)use($keyword){
                $q->where('merchant.name', 'ILIKE', '%' . $keyword . '%')
                ->orWhere('product.name', 'ILIKE', '%' . $keyword . '%')
                ->orWhere('order.trx_no', 'ILIKE', '%' . $keyword . '%');
            })
            ->paginate(10);
        return ($data);
    }
}

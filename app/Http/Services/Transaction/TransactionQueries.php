<?php

namespace App\Http\Services\Transaction;

use App\Models\Order;

class TransactionQueries
{
    public function getTransaction($column_name, $column_value)
    {
        $data = Order::with([
            'detail', 'progress_active', 'merchant', 'delivery'
        ])->where($column_name, $column_value)->paginate(10);
        return $data;
    }

    public function getTransactionWithStatusCode($column_name, $column_value, $status_code)
    {
        $data = Order::with([
            'detail', 'progress_active', 'merchant', 'delivery'
        ])->where([
            [$column_name, $column_value],
        ])->whereHas('progress_active', function ($j) use($status_code){
            $j->whereIn('status_code', [$status_code]);
        })->paginate(10);
        return $data;
    }

    public function getDetailTransaction($order_id)
    {
        $data = Order::with([
            'detail', 'progress_active', 'merchant', 'delivery'
        ])->find($order_id);
        return $data;
    }

    public function searchTransaction($buyer_id, $keyword)
    {
        $data = Order::with([
            'detail', 'progress_active', 'merchant', 'delivery'
        ])->where(
            function ($q) use ($buyer_id) {
                $q->where('buyer_id', $buyer_id)->orWhere('related_pln_mobile_customer_id', $buyer_id);
            }
        )->orWhereHas('merchant', function ($j) use ($keyword) {
            $j->where('merchant.name', 'ILIKE', '%' . $keyword . '%');
        })->orWhereHas('detail', function ($j) use ($keyword) {
            $j->whereHas('product', function ($j) use ($keyword) {
                $j->where('product.name', 'ILIKE', '%' . $keyword . '%');
            });
        })->paginate(10);

        return $data;
    }
}

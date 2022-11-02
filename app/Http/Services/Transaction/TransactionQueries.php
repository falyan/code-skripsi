<?php

namespace App\Http\Services\Transaction;

use App\Http\Services\Service;
use App\Models\CustomerDiscount;
use App\Models\DeliveryDiscount;
use App\Models\MasterData;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\OrderDelivery;
use App\Models\Product;
use App\Models\VariantValueProduct;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;

class TransactionQueries extends Service
{
    public function getTransaction($column_name, $column_value, $limit = 10, $filter = [], $page = 1)
    {
        $data = Order::with([
            'detail' => function ($product) {
                $product->with(['product' => function ($j) {
                    $j->with(['product_photo']);
                }]);
            }, 'progress_active', 'merchant', 'delivery', 'buyer', 'review' => function ($r) {
                $r->with(['review_photo'])->where('status', 1);
            },
        ])->where($column_name, $column_value)->when($column_name == 'merchant_id', function ($query) {
            $query->whereHas('progress_active', function ($q) {
                $q->whereNotIn('status_code', [99]);
            });
        })->orderBy('created_at', 'desc');

        $data = $this->filter($data, $filter);
        $data = $data->get();

        $data = static::paginate($data->toArray(), $limit, $page);

        return $data;
    }

    public function getTransactionByCategoryKey($column_name, $column_value, $category_key, $limit = 3, $filter = [], $page = 1)
    {
        $categories = MasterData::with(['child' => function ($j) {
            $j->with(['child']);
        }])->where('type', 'product_category')->where('key', $category_key)->get();

        $cat_child = [];
        foreach ($categories as $category) {
            foreach ($category->child as $child) {
                if (!$child->child->isEmpty()) {
                    array_push($cat_child, $child->child);
                }
            }
        }

        $data = [];
        foreach ($cat_child as $cat) {
            foreach ($cat as $obj) {
                $order = Order::with([
                    'detail' => function ($product) {
                        $product->with(['product' => function ($j) {
                            $j->with(['product_photo']);
                        }]);
                    }, 'progress_active', 'merchant', 'delivery', 'buyer', 'review' => function ($r) {
                        $r->with(['review_photo']);
                    },
                ])->where($column_name, $column_value)
                    ->when($column_name == 'merchant_id', function ($query) {
                        $query->whereHas('progress_active', function ($q) {
                            $q->whereNotIn('status_code', [99]);
                        });
                    })
                    ->whereHas('detail', function ($q) use ($obj) {
                        $q->whereHas('product', function ($p) use ($obj) {
                            $p->where('category_id', $obj->id);
                        });
                    })
                    ->orderBy('created_at', 'desc')->get();
                array_push($data, $order);
            }
        }
        $data = $this->filter(collect($data)->collapse(), $filter);
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
            }, 'progress_active', 'merchant', 'delivery', 'buyer',
        ])->where(
            $column_name, $column_value,
        )->whereHas('progress_active', function ($j) use ($status_code) {
            $j->where('status_code', $status_code);
        })
            ->when($column_name == 'merchant_id', function ($query) {
                $query->whereHas('progress_active', function ($q) {
                    // $q->whereNotIn('status_code', [99]);
                });
            })
            ->orderBy('created_at', 'desc')
        ;

        $data = $this->filter($data, $filter);
        $data = $data->get();

        $data = static::paginate($data->toArray(), $limit, $page);

        return $data;
    }

    public function getTransactionDone($column_name, $column_value, $status_code, $limit = 10, $filter = [], $page = 1)
    {
        $data = Order::with([
            'detail' => function ($product) {
                $product->with(['product' => function ($j) {
                    $j->with(['product_photo']);
                }]);
            }, 'progress_active', 'merchant', 'delivery', 'buyer', 'review' => function ($r) {
                $r->with(['review_photo']);
            },
        ])->where([
            [$column_name, $column_value],
        ])->whereHas('progress_active', function ($j) use ($status_code) {
            $j->whereIn('status_code', $status_code);
        })->when($column_name == 'merchant_id', function ($query) {
            $query->whereHas('progress_active', function ($q) {
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
                }, 'variant_value_product']);
            }, 'progress', 'merchant' => function ($merchant) {
                $merchant->with(['province', 'city', 'district']);
            }, 'delivery' => function ($region) {
                $region->with(['city', 'district']);
            }, 'buyer', 'payment', 'review' => function ($review) {
                $review->with(['review_photo']);
            }
        ])->find($id);

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
        ])->where('order.' . $column_name, $column_value)
            ->where(function ($q) use ($keyword, $column_name) {
                $q
                    ->whereHas('buyer', function ($buyer) use ($keyword) {
                        $buyer->where('full_name', 'ilike', "%{$keyword}%")
                            ->orWhere('phone', 'ilike', "%{$keyword}%");
                    })
                    ->orWhere('trx_no', 'ILIKE', '%' . $keyword . '%');
            })->orderBy('order.created_at', 'desc');

        $data = $this->filter($data, $filter);
        $data = $this->transactionPaginate($data, $limit);

        return $data;
    }

    public function countSearchTransaction($column_name, $column_value, $keyword, $filter = [])
    {
        $data = Order::with([
            'detail' => function ($product) {
                $product->with(['product' => function ($j) {
                    $j->with(['product_photo']);
                }]);
            }, 'progress_active', 'merchant', 'delivery', 'buyer'
        ])->where('order.' . $column_name, $column_value)
            ->where(function ($q) use ($keyword, $column_name) {
                $q
                    ->whereHas('buyer', function ($buyer) use ($keyword) {
                        $buyer->where('full_name', 'ilike', "%{$keyword}%")
                            ->orWhere('phone', 'ilike', "%{$keyword}%");
                    })
                    ->orWhere('trx_no', 'ILIKE', '%' . $keyword . '%');
            })->orderBy('order.created_at', 'desc');

        $data = $this->filter($data, $filter);
        $data = $data->count();

        return $data;
    }

    public function getStatusOrder($id, $allStatus = false)
    {
        $data = Order::when($allStatus == false, function($query) {
            $query->with('progress_active');
        })->when($allStatus == true, function($query) {
            $query->with('progress');
        })->find($id);
        return $data;
    }
    
    public function checkAwb($awb)
    {
        $data = OrderDelivery::where('awb_number', $awb)->first();
        return $data;
    }

    public function getDeliveryDiscount()
    {
        $data = DeliveryDiscount::where('id', '1')->where('is_active', true)->first();

        if (empty($data)) {
            $data = new DeliveryDiscount();
            $data->discount_amount = 0;
        }

        return $data;
    }

    public function getCustomerDiscount($user_id, $email)
    {
        $discount = 0;
        $now = Carbon::now('Asia/Jakarta');
        $data = CustomerDiscount::where('customer_reference_id', $user_id)->orWhere('customer_reference_id', $email)
            ->where('is_used', false)->where('expired_date', '>=', $now)->first();

        if (!empty($data)) {
            $discount = (int) $data->amount;
        }

        return $discount;
    }

    public function countCheckoutPrice($customer, $datas)
    {
        $total_price = $total_payment = $total_delivery_discount = $total_delivery_fee = 0;

        $new_merchant = array_map(function ($merchant) use (&$total_price, &$total_payment, &$total_delivery_discount, &$total_delivery_fee) {
            $total_weight = 0;
            $merchant_total_price = 0;
            $data_merchant = Merchant::with(['city'])->findOrFail($merchant['merchant_id']);

            $new_product = array_map(function ($product) use (&$total_weight, &$merchant_total_price) {
                if (!$data_product = Product::with(['product_photo', 'stock_active'])->find($product['product_id'])) {
                    throw new Exception('Produk dengan id ' . $product['product_id'] . ' tidak ditemukan', 404);
                }

                $variant_data = null;
                if (isset($product['variant_value_product_id']) && $product['variant_value_product_id'] != null) {
                    if (!$variant_data = VariantValueProduct::with('variant_stock')->where('id', $product['variant_value_product_id'])
                        ->where('product_id', $product['product_id'])->first()) {
                        throw new Exception('Variant produk dengan id ' . $product['variant_value_product_id'] . ' tidak ditemukan', 404);
                    }
                    $product['total_price'] = $product['total_amount'] = $total_item_price = $variant_data['price'] * $product['quantity'];
                } else {
                    $product['total_price'] = $product['total_amount'] = $total_item_price = $data_product['price'] * $product['quantity'];
                }

                $product['total_weight'] = $product_total_weight = $data_product['weight'] * $product['quantity'];
                $product['insurance_cost'] = $product['discount'] = $product['total_discount'] = $product['total_insurance_cost'] = 0;
                $product['variant_data'] = $variant_data;

                $total_weight += $product_total_weight;
                $merchant_total_price += $total_item_price;

                return array_merge($product, $data_product->toArray());
            }, data_get($merchant, 'products'));

            $merchant['products'] = $new_product;
            $merchant['total_weight'] = $total_weight;
            if ($merchant['delivery_discount'] > $merchant['delivery_fee']) {
                $merchant['delivery_discount'] = $merchant['delivery_fee'];
            }
            $merchant['total_amount'] = $merchant_total_price_with_delivery = $merchant_total_price + $merchant['delivery_fee'];
            $merchant['total_payment'] = $merchant_total_payment = $merchant_total_price_with_delivery - $merchant['delivery_discount'];

            $total_price += $merchant_total_price_with_delivery;
            $total_payment += $merchant_total_payment;
            $total_delivery_fee += $merchant['delivery_fee'];
            $total_delivery_discount += $merchant['delivery_discount'];

            return array_merge($merchant, $data_merchant->toArray());
        }, data_get($datas, 'merchants'));

        $datas['merchants'] = $new_merchant;
        $datas['total_amount'] = $total_price;
        $datas['total_amount_without_delivery'] = $total_price - $total_delivery_fee;
        $datas['total_delivery_fee'] = $total_delivery_fee;
        $datas['total_delivery_discount'] = $total_delivery_discount;
        $datas['total_payment'] = $total_payment;

        $total_discount = $total_price_discount = 0;
        $percent_discount = 50;
        $max_percent_discount = 500000;
        $is_percent_discount = false;
        $discount = $this->getCustomerDiscount($customer->id, $customer->email);

        if ($discount == 0 && $is_percent_discount == true) {
            $total_item_price = 0;
            array_map(function ($merchant) use (&$total_item_price) {
                array_map(function ($product) use (&$total_item_price) {
                    $total_item_price += $product['total_price'];
                }, data_get($merchant, 'products'));
            }, data_get($datas, 'merchants'));

            $discount = ($percent_discount / 100) * $total_item_price;
            if ($discount > $max_percent_discount) {
                $discount = $max_percent_discount;
            }
        }

        $new_merchant = array_map(function ($merchant) use (&$discount, &$total_discount, &$total_price_discount) {
            $count_discount = $discount;

            if (data_get($merchant, 'total_payment') != null && data_get($merchant, 'total_payment') <= $discount) {
                $count_discount = data_get($merchant, 'total_payment');
            }

            data_set($merchant, 'total_payment', data_get($merchant, 'total_payment') - $count_discount);
            $discount = $discount - $count_discount;
            $total_discount += $count_discount;
            $total_price_discount += data_get($merchant, 'total_payment');
            $merchant['product_discount'] = $count_discount;

            return $merchant;
        }, data_get($datas, 'merchants'));
        $datas['merchants'] = $new_merchant;
        $datas['total_discount'] = $total_discount;
        $datas['total_payment'] -= $total_discount;

        return $datas;
    }

    public function filter($model, $filter)
    {
        if (count($filter) > 0) {
            $status = $filter['status'] ?? null;
            $start_date = $filter['start_date'] ?? null;
            $end_date = $filter['end_date'] ?? null;

            if (validateDate($start_date, 'Y-m-d')) {
                $start_date = $start_date . " 00:00:00";
            }

            if (validateDate($end_date, 'Y-m-d')) {
                $end_date = $end_date . " 23:59:59";
            }

            $data = $model->when(!empty($start_date) && !empty($end_date), function ($query) use ($start_date, $end_date) {
                $query->whereBetween('created_at', [$start_date, $end_date]);
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

    protected function transactionPaginate($transactions, $limit = 10)
    {
        $itemsPaginated = $transactions->paginate($limit);

        $itemsTransformed = $itemsPaginated->getCollection()->toArray();

        $itemsTransformedAndPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $itemsTransformed,
            $itemsPaginated->total(),
            $itemsPaginated->perPage(),
            $itemsPaginated->currentPage(), [
                // 'path' => \Illuminate\Http\Request::url(),
                'query' => [
                    'page' => $itemsPaginated->currentPage(),
                ],
            ]
        );

        return $itemsTransformedAndPaginated;
    }
}

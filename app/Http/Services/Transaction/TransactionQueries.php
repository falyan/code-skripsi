<?php

namespace App\Http\Services\Transaction;

use App\Http\Services\Manager\GamificationManager;
use App\Http\Services\Service;
use App\Models\City;
use App\Models\CustomerAddress;
use App\Models\CustomerDiscount;
use App\Models\DeliveryDiscount;
use App\Models\MasterData;
use App\Models\MasterTiket;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\OrderDelivery;
use App\Models\Product;
use App\Models\PromoLog;
use App\Models\VariantStock;
use App\Models\VariantValueProduct;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;

class TransactionQueries extends Service
{
    public function getTransaction($column_name, $column_value, $limit = 10, $filter = [], $page = 1)
    {
        $order = Order::with([
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

        $order = $this->filter($order, $filter);
        $order = $order->get();

        $data = $order->map(function ($item) {
            $item->detail->each(function ($product) {
                if ($product->product_data != null && isset($product->product_data)) {
                    unset($product->product);
                    $product_data = json_decode($product->product_data);

                    $product->product = $product_data;
                }
            });

            return $item;
        });

        $data = static::paginate($order->toArray(), $limit, $page);

        return $data;
    }

    public function sellerSubsidyEv($merchant_id, $limit = 10, $filter = [], $page = 1)
    {
        $keyword = $filter['keyword'] ?? null;

        $order = Order::with([
            'detail' => function ($product) {
                $product->with(['product' => function ($p) {
                    $p->with(['product_photo']);
                }]);
            }, 'progress_active', 'merchant', 'delivery', 'buyer', 'ev_subsidy', 'review' => function ($r) {
                $r->with(['review_photo'])->where('status', 1);
            },
        ])
            ->whereHas('ev_subsidy', function ($q) use ($merchant_id) {
                $q->whereHas('order', function ($o) use ($merchant_id) {
                    $o->where('merchant_id', $merchant_id);
                });
            })
            ->where('merchant_id', $merchant_id)
            ->when($keyword != null, function ($query) use ($keyword) {
                $query->where('trx_no', 'ILIKE', "%{$keyword}%");
            })
            ->orderBy('created_at', 'desc');

        $order = $this->filter($order, $filter);
        $order = $order->get();

        $data = $order->map(function ($item) {
            $item->detail->each(function ($product) {
                if ($product->product_data != null) {
                    unset($product->product);
                    $product_data = json_decode($product->product_data);

                    $product->product = $product_data;
                }
            });

            return $item;
        });

        $data = static::paginate($order->toArray(), $limit, $page);

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
                    $j->select('id', 'merchant_id', 'name')->with(['product_photo']);
                }]);
            }, 'progress_active', 'merchant', 'delivery', 'buyer',
        ])->where(
            $column_name, $column_value,
        )->whereHas('progress_active', function ($j) use ($status_code) {
            if (count($status_code) > 1) {
                $j->whereIn('status_code', $status_code);
            }

            if (count($status_code) == 1) {
                $j->where('status_code', $status_code[0]);
            }

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

        $data = $data->map(function ($item) {
            $item->detail->each(function ($product) {
                if ($product->product_data != null && isset($product->product_data)) {
                    unset($product->product);
                    $product_data = json_decode($product->product_data);

                    $product->product = $product_data;
                }
            });

            return $item;
        });

        $data = static::paginate($data->toArray(), $limit, $page);

        return $data;
    }

    public function getTransactionToExport($column_name, $column_value, $filter = null)
    {

        $data = Order::with([
            'detail' => function ($product) {
                $product->with(['product' => function ($j) {
                    $j->select('id', 'merchant_id', 'name')->with(['product_photo']);
                }]);
            }, 'progress_active', 'merchant', 'delivery', 'buyer',
        ])->where(
            $column_name,
            $column_value,
        )->whereHas('progress_active', function ($j) use ($filter) {
            if (isset($filter['status_code']) && !empty($filter['status_code'])) {
                $j->whereIn('status_code', explode(',', $filter['status_code']));
            } else {
                $j->whereIn('status_code', ['01', '02', '03', '08', '09', '88']);
            }
        })->when(!empty($filter['start_date']) && !empty($filter['end_date']), function ($query) use ($filter) {
            $query->whereDate('created_at', '>=', $filter['start_date'])->whereDate('created_at', '<=', $filter['end_date']);
        })->orderBy('created_at', 'desc')->get();

        return $data;
    }

    public function getTransactionDone($column_name, $column_value, $status_code, $limit = 10, $filter = [], $page = 1)
    {
        $data = Order::with([
            'detail' => function ($product) {
                $product->with(['product', function ($j) {
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

        $data = $data->map(function ($item) {
            $item->detail->each(function ($product) {
                if ($product->product_data != null) {
                    unset($product->product);
                    $product_data = json_decode($product->product_data);

                    $product->product = $product_data;
                }
            });

            return $item;
        });

        $data = static::paginate($data->toArray(), $limit, $page);

        return $data;
    }

    public function getDetailTransaction($id)
    {
        $data = Order::with([
            'detail' => function ($product) {
                $product->with(['product' => function ($j) {
                    $j->with('ev_subsidy');
                }, 'variant_value_product']);
            }, 'progress', 'merchant' => function ($merchant) {
                $merchant->with(['province', 'city', 'district', 'subdistrict']);
            }, 'delivery' => function ($region) {
                $region->with(['city', 'district']);
            }, 'buyer', 'ev_subsidy', 'payment', 'review' => function ($review) {
                $review->with(['review_photo']);
            }, 'promo_log_orders' => function ($promo) {
                $promo->with(['promo_merchant.promo_master']);
            },
        ])->find($id);

        $details = $data->detail;

        foreach ($details as $key => $detail) {
            $product_data = json_decode($detail->product_data);

            $detail->product->name = $product_data->name ?? $detail->product->name;
            $detail->product->product_photo = $product_data->product_photo ?? $detail->product->product_photo;
            $detail->product->price = $product_data->price ?? $detail->product->price;
            $detail->product->strike_price = $product_data->strike_price ?? $detail->product->strike_price;
            $detail->product->condition = $product_data->condititon ?? $detail->product->condition;
            $detail->product->description = $product_data->description ?? $detail->product->description;
            $details[$key] = $detail;
        }

        $delivery = json_decode($data->delivery->merchant_data);
        if ($delivery != null) {
            $city = City::find($delivery->merchant_city_id);
            $data->merchant->address = $delivery->merchant_address;
            $data->merchant->province_id = $delivery->merchant_province_id;
            $data->merchant->city_id = $delivery->merchant_city_id;
            $data->merchant->city->id = $city->id;
            $data->merchant->city->name = $city->name;
            $data->merchant->district_id = $delivery->merchant_district_id;
            $data->merchant->subdistrict_id = $delivery->merchant_subdistrict_id;
            $data->merchant->postal_code = $delivery->merchant_postal_code;
            $data->merchant->latitude = $delivery->merchant_latitude;
            $data->merchant->longitude = $delivery->merchant_longitude;

            $data->merchant->phone_office = $delivery->merchant_phone_office;
            $data->merchant->name = $delivery->merchant_name;

            unset($data->delivery->merchant_data);
        }

        foreach ($data->promo_log_orders as $promo_log_order) {
            if ($promo_log_order->promo_merchant->promo_master->event_type == 'ongkir') {
                $data->delivery->is_shipping_discount = true;
            }

            if ($promo_log_order->promo_merchant->promo_master->event_type == 'flash_sale') {
                foreach ($details as $key => $detail) {
                    $detail->product->is_flash_sale_discount = true;
                    $details[$key] = $detail;
                }
            }
        }

        unset($data->promo_log_orders);
        $data->detail = $details;
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
            }, 'progress_active', 'merchant', 'delivery', 'buyer',
        ])->where('order.' . $column_name, $column_value)
            ->where(function ($q) use ($keyword, $column_name) {
                $q
                    ->whereHas('buyer', function ($buyer) use ($keyword) {
                        $buyer->where('full_name', 'ilike', "%{$keyword}%")
                            ->orWhere('phone', 'ilike', "%{$keyword}%");
                    })
                    ->orWhere('trx_no', 'ILIKE', '%' . $keyword . '%');
            })->whereHas('progress_active', function ($q) {
            $q->whereNotIn('status_code', ['00', '99']);
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
            }, 'progress_active', 'merchant', 'delivery', 'buyer',
        ])->where('order.' . $column_name, $column_value)
            ->where(function ($q) use ($keyword, $column_name) {
                $q
                    ->whereHas('buyer', function ($buyer) use ($keyword) {
                        $buyer->where('full_name', 'ilike', "%{$keyword}%")
                            ->orWhere('phone', 'ilike', "%{$keyword}%");
                    })
                    ->orWhere('trx_no', 'ILIKE', '%' . $keyword . '%');
            })->whereHas('progress_active', function ($q) {
            $q->whereNotIn('status_code', ['00', '99']);
        })->orderBy('order.created_at', 'desc');

        $data = $this->filter($data, $filter);
        $data = $data->count();

        return $data;
    }

    public function getStatusOrder($id, $allStatus = false)
    {
        $data = Order::when($allStatus == false, function ($query) {
            $query->with('progress_active');
        })->when($allStatus == true, function ($query) {
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
        $datas['buyer_npwp'] = auth()->user()->npwp;
        $datas['merchants'] = $new_merchant;
        $datas['total_discount'] = $total_discount;
        $datas['total_payment'] -= $total_discount;

        return $datas;
    }

    public function countCheckoutPriceV2($customer, $datas)
    {
        $city_id = data_get($datas, 'destination_info.city_id');

        if (!$city_id) {
            throw new Exception('Silahkan tambah alamat pengiriman terlebih dahulu!', 404);
        }

        $province_id = City::where('id', $city_id)->first()->province_id;
        $total_price = $total_payment = $total_delivery_discount = $total_delivery_fee = $total_insentif = $total_discount_payment = 0;
        $total_price_discount = 0;
        $message_error = '';

        $new_merchant = [];
        $ev_subsidies = [];
        $promo_masters = [];
        foreach (data_get($datas, 'merchants') as $merchant) {
            $total_weight = 0;
            $merchant_total_price = 0;

            $data_merchant = Merchant::with([
                'city',
                'district',
                'promo_merchant' => function ($pd) {
                    $pd->where('status', 1);
                    $pd->where(function ($query) {
                        $query->where('start_date', '<=', date('Y-m-d H:i:s'))
                            ->where('end_date', '>=', date('Y-m-d H:i:s'));
                    });
                    $pd->whereHas('promo_master', function ($pm) {
                        $pm->where('status', 1);
                    });
                },
                'promo_merchant.promo_master' => function ($pm) {
                    $pm->where('status', 1);
                },
                'promo_merchant.promo_master.promo_regions',
                'promo_merchant.promo_master.promo_values',
                'orders' => function ($orders) {
                    $orders->whereHas('progress_active', function ($progress) {
                        $progress->whereIn('status_code', ['01', '02']);
                    });
                },
            ])->findOrFail($merchant['merchant_id']);

            $new_product = [];
            foreach (data_get($merchant, 'products') as $product) {
                $data_product = Product::with(['product_photo', 'stock_active', 'ev_subsidy' => function ($es) use ($merchant) {
                    $es->where('merchant_id', $merchant['merchant_id']);
                }])->find($product['product_id']);
                if (!$data_product) {
                    throw new Exception('Produk dengan id ' . $product['product_id'] . ' tidak ditemukan', 404);
                }

                if ($data_product->ev_subsidy != null) {
                    $ev_subsidies[] = $data_product->ev_subsidy;
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
                $product['total_insentif'] = $product['insentif'] = 0;

                $total_weight += $product_total_weight;
                $merchant_total_price += $total_item_price;

                $new_product[] = array_merge($product, $data_product->toArray());
            }

            // shipping discount
            $promo_merchant_ongkir = null;
            if ($data_merchant->can_shipping_discount == true) {
                foreach ($data_merchant->promo_merchant as $promo) {
                    if ($promo->promo_master->event_type == 'ongkir') {
                        foreach ($promo->promo_master->promo_regions as $region) {
                            $region_ids = collect($region->province_ids)->toArray();
                            if (in_array($province_id, $region_ids)) {
                                $promo_merchant_ongkir = $promo;

                                if ($promo_masters == []) {
                                    $promo_masters[] = $promo->promo_master;
                                } else {
                                    foreach ($promo_masters as $promo_master) {
                                        if ($promo_master->id == $promo->promo_master->id) {
                                            $promo_merchant_ongkir->promo_master = $promo_master;
                                            break;
                                        } else {
                                            $promo_masters[] = $promo->promo_master;
                                        }
                                    }
                                }

                                if ($region->value_type == 'value_2') {
                                    $value_ongkir = $promo->promo_master->value_2;
                                } else {
                                    $value_ongkir = $promo->promo_master->value_1;
                                }

                                $max_merchant = ($promo->usage_value + $value_ongkir) > $promo->max_value;
                                $max_master = ($promo->promo_master->usage_value + $value_ongkir) > $promo->promo_master->max_value;

                                if ($max_merchant && !$max_master) {
                                    $merchant['delivery_discount'] = $value_ongkir;
                                    break;
                                }

                                if (!$max_merchant && $max_master) {
                                    $merchant['delivery_discount'] = $value_ongkir;
                                    break;
                                }

                                if (!$max_merchant && !$max_master) {
                                    $merchant['delivery_discount'] = $value_ongkir;
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            $merchant['products'] = $new_product;
            $merchant['total_weight'] = $total_weight;
            if ($merchant['delivery_discount'] > $merchant['delivery_fee']) {
                $merchant['delivery_discount'] = $merchant['delivery_fee'];
            }

            $merchant_total_price_with_delivery = $merchant_total_price + $merchant['delivery_fee'];
            $merchant['total_amount'] = $merchant_total_price;
            $merchant['total_payment'] = $merchant_total_payment = $merchant_total_price_with_delivery - $merchant['delivery_discount'];

            if ($promo_merchant_ongkir != null) {
                if ($promo_merchant_ongkir->promo_master->min_order_value > $merchant_total_price) {
                    $message_error = 'Minimal order untuk diskon ongkir adalah Rp ' . number_format($promo_merchant_ongkir->promo_master->min_order_value, 0, ',', '.');
                    $merchant['delivery_discount'] = 0;
                }

                $customer_limit_count = $promo_merchant_ongkir->promo_master->customer_limit_count;
                if ($customer_limit_count != null && $customer_limit_count > 0) {
                    $promo_logs = PromoLog::where('promo_master_id', $promo_merchant_ongkir->promo_master->id)
                        ->whereHas('order', function ($query) {
                            $query->where('buyer_id', auth()->user()->id);
                        })->get();

                    $promo_logs_add = collect($promo_logs)->where('type', 'add')->count();
                    $promo_logs_sub = collect($promo_logs)->where('type', 'sub')->count();

                    if ($customer_limit_count <= ($promo_logs_sub - $promo_logs_add)) {
                        $message_error = 'Anda telah melebihi batas penggunaan promo ini';
                        $merchant['delivery_discount'] = 0;
                    }
                }

                $promo_merchant_ongkir->promo_master->usage_value += $merchant['delivery_discount'];
                foreach ($promo_masters as $key => $promo_master) {
                    if ($promo_master->id == $promo_merchant_ongkir->promo_master->id) {
                        $promo_masters[$key] = $promo_merchant_ongkir->promo_master;
                        break;
                    }
                }
            }

            // flash sale discount
            $promo_merchant_flash_sale = null;
            $promo_flash_sale_value = null;
            $merchant['product_discount'] = 0;
            if ($data_merchant->can_flash_sale_discount == true) {
                foreach ($data_merchant->promo_merchant as $promo) {
                    if ($promo->promo_master->event_type == 'flash_sale') {
                        $value_flash_sale = 0;
                        $promo_merchant_flash_sale = $promo;

                        if ($promo_masters == []) {
                            $promo_masters[] = $promo->promo_master;
                        } else {
                            foreach ($promo_masters as $promo_master) {
                                if ($promo_master->id == $promo->promo_master->id) {
                                    $promo_merchant_flash_sale->promo_master = $promo_master;
                                    break;
                                } else {
                                    $promo_masters[] = $promo->promo_master;
                                }
                            }
                        }

                        $value_flash_sale = $promo->promo_master->value_1;
                        if ($promo->promo_master->promo_value_type == 'percentage') {
                            $value_flash_sale = $merchant_total_price * ($promo_merchant_flash_sale->promo_master->value_1 / 100);
                            if ($value_flash_sale >= $promo->promo_master->max_discount_value) {
                                $value_flash_sale = $promo->promo_master->max_discount_value;
                            }
                        }

                        foreach ($promo->promo_master->promo_values as $promo_value) {
                            $value_flash_sale = $promo->promo_master->value_1;
                            if ($promo->promo_master->promo_value_type == 'percentage') {
                                $value_flash_sale = $merchant_total_price * ($promo_merchant_flash_sale->promo_master->value_1 / 100);
                                if ($value_flash_sale >= $promo->promo_master->max_discount_value) {
                                    $value_flash_sale = $promo->promo_master->max_discount_value;
                                }
                            }

                            if ($merchant_total_price >= $promo_value->min_value && $merchant_total_price <= $promo_value->max_value && $promo_value->status == 1) {
                                $promo_flash_sale_value = $promo_value;

                                if ($value_flash_sale >= $promo_value->max_discount_value) {
                                    $value_flash_sale = $promo_value->max_discount_value;
                                }

                                break;
                            }
                        }

                        $max_merchant = ($promo->usage_value + $value_flash_sale) > $promo->max_value;
                        $max_master = ($promo->promo_master->usage_value + $value_flash_sale) > $promo->promo_master->max_value;

                        if ($max_merchant && !$max_master) {
                            $merchant['product_discount'] = $value_flash_sale;
                            break;
                        }

                        if (!$max_merchant && $max_master) {
                            $merchant['product_discount'] = $value_flash_sale;
                            break;
                        }

                        if (!$max_merchant && !$max_master) {
                            $merchant['product_discount'] = $value_flash_sale;
                            break;
                        }
                    }
                }
            }

            if ($promo_merchant_flash_sale != null) {
                if ($promo_merchant_flash_sale->promo_master->min_order_value > $merchant_total_price && $promo_flash_sale_value == null) {
                    $merchant['product_discount'] = 0;
                }

                $customer_limit_count = $promo_merchant_flash_sale->promo_master->customer_limit_count;
                if ($customer_limit_count != null && $customer_limit_count > 0) {
                    $promo_logs = PromoLog::where('promo_master_id', $promo_merchant_flash_sale->promo_master->id)
                        ->whereHas('order', function ($query) {
                            $query->where('buyer_id', auth()->user()->id);
                        })->get();

                    $promo_logs_add = collect($promo_logs)->where('type', 'add')->count();
                    $promo_logs_sub = collect($promo_logs)->where('type', 'sub')->count();

                    if ($customer_limit_count <= ($promo_logs_sub - $promo_logs_add)) {
                        $message_error = 'Anda telah melebihi batas penggunaan promo ini';
                        $merchant['product_discount'] = 0;
                    }
                }

                if ($merchant['product_discount'] > $merchant_total_price) {
                    $merchant['product_discount'] = $merchant_total_price;
                }

                $promo_merchant_flash_sale->promo_master->usage_value += $merchant['product_discount'];
                foreach ($promo_masters as $key => $promo_master) {
                    if ($promo_master->id == $promo_merchant_flash_sale->promo_master->id) {
                        $promo_masters[$key] = $promo_merchant_flash_sale->promo_master;
                        break;
                    }
                }
            }

            $merchant['total_payment'] = $merchant['total_payment'] - $merchant['product_discount'];

            $total_price += $merchant_total_price_with_delivery;
            $total_payment += $merchant_total_payment;
            $total_delivery_fee += $merchant['delivery_fee'];
            $total_delivery_discount += $merchant['delivery_discount'];
            $total_price_discount += $merchant['product_discount'];

            $data_merchant['order_count'] = count($data_merchant['orders']);

            unset($data_merchant->promo_merchant);
            unset($merchant['promo_merchant']);
            unset($data_merchant['orders']);

            $new_merchant[] = array_merge($merchant, $data_merchant->toArray());
        }

        $datas['merchants'] = $new_merchant;
        $datas['total_amount'] = $total_price;
        $datas['total_amount_without_delivery'] = $total_price - $total_delivery_fee;
        $datas['total_delivery_fee'] = $total_delivery_fee;
        $datas['total_delivery_discount'] = $total_delivery_discount;
        $datas['total_payment'] = $total_payment;

        // $total_discount = $total_price_discount = 0;
        // $percent_discount = 50;
        // $max_percent_discount = 500000;
        // $is_percent_discount = false;
        // $discount = $this->getCustomerDiscount($customer->id, $customer->email);

        // if ($discount == 0 && $is_percent_discount == true) {
        //     $total_item_price = 0;
        //     array_map(function ($merchant) use (&$total_item_price) {
        //         array_map(function ($product) use (&$total_item_price) {
        //             $total_item_price += $product['total_price'];
        //         }, data_get($merchant, 'products'));
        //     }, data_get($datas, 'merchants'));

        //     $discount = ($percent_discount / 100) * $total_item_price;
        //     if ($discount > $max_percent_discount) {
        //         $discount = $max_percent_discount;
        //     }
        // }

        $new_products = [];
        foreach ($datas['merchants'] as $merchant) {
            foreach ($merchant['products'] as $product) {
                $new_products[] = $product;
            }
        }

        if (isset($datas['customer']) && data_get($datas, 'customer') != null) {
            $ev_subsidy = null;
            foreach ($ev_subsidies as $subsidy) {
                if ($ev_subsidy == null) {
                    $ev_subsidy = $subsidy;
                } else {
                    if ($subsidy->subsidy_amount > $ev_subsidy->subsidy_amount) {
                        $ev_subsidy = $subsidy;
                    }
                }
            }

            $subsidy = false;
            foreach ($new_products as $key => $product) {
                if ($ev_subsidy != null && $subsidy == false) {
                    if ($ev_subsidy->merchant_id == $product['merchant_id'] && $ev_subsidy->product_id == $product['id']) {
                        $product['insentif'] += $ev_subsidy->subsidy_amount;
                        $product['total_insentif'] += $ev_subsidy->subsidy_amount;

                        $new_products[$key] = $product;
                        $subsidy = true;
                    }
                }
            }
        }

        $new_merchant = [];
        foreach (data_get($datas, 'merchants') as $merchant) {
            $product_insentif = 0;
            if (isset($datas['customer']) && data_get($datas, 'customer') != null) {
                $insentif = 0;
                foreach ($merchant['products'] as $key => $product) {
                    foreach ($new_products as $new_product_value) {
                        if ($product['product_id'] == $new_product_value['product_id']) {
                            $merchant['products'][$key]['insentif'] = $new_product_value['insentif'];
                            $merchant['products'][$key]['total_insentif'] = $new_product_value['total_insentif'];

                            $insentif += $merchant['products'][$key]['total_insentif'];
                        }
                    }
                }

                $product_insentif += $insentif;
            }
            // $count_discount = $discount;

            // if (data_get($merchant, 'total_payment') != null && data_get($merchant, 'total_payment') <= $discount) {
            //     $count_discount = data_get($merchant, 'total_payment');
            // }

            // data_set($merchant, 'total_payment', data_get($merchant, 'total_payment') - $count_discount);
            // $discount = $discount - $count_discount;
            // $total_discount += $count_discount;
            // $total_price_discount += data_get($merchant, 'total_payment');
            // $merchant['product_discount'] = $count_discount;
            $merchant['product_insentif'] = $product_insentif;
            $merchant['total_payment'] -= $product_insentif;

            $total_insentif += $product_insentif;

            $new_merchant[] = $merchant;
        }

        // $new_merchant2 = [];
        // foreach ($new_merchant as $merchant) {
        //     if (isset($datas['customer']) && data_get($datas, 'customer') != null) {
        //         foreach ($merchant['products'] as $key => $product) {
        //             $merchant['products'][$key]['ev_subsidy'] = $ev_subsidy;
        //         }
        //     }

        //     $new_merchant2[] = $merchant;
        // }

        $bonus_activation = MasterData::where('key', 'gami_bonus_activation')->select('value')->first();
        // payment discount
        $discount_payment = 0;
        $discount_type = null;
        $discount_claim_id = null;
        // cek jika di setting db bgami_bonus_activation nya active, maka jalankan perhitungan discount ini
        if ($bonus_activation->value === 'ACTIVE') {
            // cek jika total price minimal 50000
            $totalProductPrice = $total_price - $total_delivery_fee;

            if ($totalProductPrice >= 50000) {
                $bonus_amount = 0;
                $userId = auth()->user()->pln_mobile_customer_id;

                // cek jika customer memiliki bonus discount, jika tidak maka tidak dapat discount payment
                $checkBonusDiscount = GamificationManager::claimBonusHold($userId, $totalProductPrice);

                // jika customer memiliki bonus discount, validasi apakah bonus discount nya valid atau tidak. Jika valid, maka discount payment nya di set sesuai dengan bonus discount nya
                if ($checkBonusDiscount['success'] === true) {
                    $validateBonusDiscount = GamificationManager::claimBonusValidate($checkBonusDiscount['data']['id'], $totalProductPrice);

                    if ($validateBonusDiscount['success'] === true) {
                        $bonus_amount += $validateBonusDiscount['data']['bonusAmount'];
                    }

                    $discount_payment += $bonus_amount;
                    $discount_type = 'GAMI-BONUS-DISCOUNT';
                    $discount_claim_id = $checkBonusDiscount['data']['id'];
                }
            }
        }

        $total_discount_payment += $discount_payment;

        // $datas['merchants'] = $new_merchant2;
        $datas['buyer_npwp'] = auth()->user()->npwp;
        $datas['merchants'] = $new_merchant;
        $datas['discount_type'] = $discount_type;
        $datas['discount_claim_id'] = $discount_claim_id;
        $datas['total_discount'] = $total_price_discount + $total_discount_payment;
        $datas['total_insentif'] = $total_insentif;
        $datas['total_payment'] -= $total_price_discount;
        $datas['total_payment'] -= $total_insentif;
        $datas['total_payment'] -= $total_discount_payment;

        if ($message_error != '') {
            $datas['success'] = false;
            $datas['status_code'] = 402;
            $datas['message'] = $message_error;
        } else {
            $datas['success'] = true;
            $datas['status_code'] = null;
            $datas['message'] = 'Berhasil menghitung total pembayaran';
        }

        // validasi tiket
        $master_tikets = MasterTiket::with(['master_data'])->where('status', 1)->get();
        $customer_tiket = Order::with(['detail', 'detail.product'])->where('buyer_id', $customer->id)
            ->whereHas('progress_active', function ($q) {
                $q->whereIn('status_code', ['00', '01', '02', '03', '08', '88']);
            })
            ->whereHas('detail', function ($q) use ($master_tikets) {
                $q->whereHas('product', function ($q) use ($master_tikets) {
                    $q->whereIn('category_id', collect($master_tikets)->pluck('master_data.id')->toArray());
                });
            })->get();

        $count_tiket = 0;
        foreach ($customer_tiket as $order) {
            foreach ($order->detail as $detail) {
                $tiket = collect($master_tikets)->where('master_data.id', $detail->product->category_id)->first();

                if ($tiket) {
                    $count_tiket += $detail->quantity;
                }
            }
        }

        $buying_tiket = false;
        foreach ($new_product as $product) {
            $tiket = collect($master_tikets)->where('master_data.id', $product['category_id'])->first();

            if ($tiket) {
                $count_tiket += $product['quantity'];

                $buying_tiket = true;
            }
        }

        if ($count_tiket > 4 && $buying_tiket) {
            $datas['success'] = false;
            $datas['status_code'] = 400;
            $datas['message'] = 'Anda telah melebihi batas pembelian tiket';
        }

        foreach ($datas['merchants'] as $merchant) {

            if ($merchant['order_count'] > 50) {
                $datas['success'] = false;
                $datas['status_code'] = 400;
                $datas['message'] = 'Mohon maaf, saat ini merchant tidak dapat melakukan transaksi';
            }
        }

        return $datas;
    }

    public function countCheckoutPriceV3($customer, $datas)
    {
        $customer_address = CustomerAddress::where('id', $datas['customer_address_id'])->first();

        if (!$customer_address) {
            throw new Exception('Silahkan tambah alamat pengiriman terlebih dahulu!', 404);
        }

        $province_id = $customer_address->province_id;
        $total_price = $total_payment = $total_delivery_discount = $total_delivery_fee = $total_insentif = $total_discount_payment = 0;
        $total_price_discount = 0;
        $message_error = '';

        $new_merchant = [];
        $ev_subsidies = [];
        $promo_masters = [];
        foreach (data_get($datas, 'merchants') as $merchant) {
            $total_weight = 0;
            $merchant_total_price = 0;

            $data_merchant = Merchant::with([
                'city',
                'district',
                'promo_merchant' => function ($pd) {
                    $pd->where('status', 1);
                    $pd->where(function ($query) {
                        $query->where('start_date', '<=', date('Y-m-d H:i:s'))
                            ->where('end_date', '>=', date('Y-m-d H:i:s'));
                    });
                    $pd->whereHas('promo_master', function ($pm) {
                        $pm->where('status', 1);
                    });
                },
                'promo_merchant.promo_master' => function ($pm) {
                    $pm->where('status', 1);
                },
                'promo_merchant.promo_master.promo_regions',
                'promo_merchant.promo_master.promo_values',
                'orders' => function ($orders) {
                    $orders->whereHas('progress_active', function ($progress) {
                        $progress->whereIn('status_code', ['01', '02']);
                    });
                },
            ])->findOrFail($merchant['merchant_id']);

            $new_product = [];
            foreach (data_get($merchant, 'products') as $product) {
                $data_product = Product::with(['product_photo', 'stock_active', 'ev_subsidy' => function ($es) use ($merchant) {
                    $es->where('merchant_id', $merchant['merchant_id']);
                }])->find($product['product_id']);
                if (!$data_product) {
                    throw new Exception('Produk dengan id ' . $product['product_id'] . ' tidak ditemukan', 404);
                }

                if ($data_product->ev_subsidy != null) {
                    $ev_subsidies[] = $data_product->ev_subsidy;
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
                $product['total_insentif'] = $product['insentif'] = 0;

                $total_weight += $product_total_weight;
                $merchant_total_price += $total_item_price;

                $new_product[] = array_merge($product, $data_product->toArray());
            }

            // shipping discount
            $promo_merchant_ongkir = null;
            if ($data_merchant->can_shipping_discount == true) {
                foreach ($data_merchant->promo_merchant as $promo) {
                    if ($promo->promo_master->event_type == 'ongkir') {
                        foreach ($promo->promo_master->promo_regions as $region) {
                            $region_ids = collect($region->province_ids)->toArray();
                            if (in_array($province_id, $region_ids)) {
                                $promo_merchant_ongkir = $promo;

                                if ($promo_masters == []) {
                                    $promo_masters[] = $promo->promo_master;
                                } else {
                                    foreach ($promo_masters as $promo_master) {
                                        if ($promo_master->id == $promo->promo_master->id) {
                                            $promo_merchant_ongkir->promo_master = $promo_master;
                                            break;
                                        } else {
                                            $promo_masters[] = $promo->promo_master;
                                        }
                                    }
                                }

                                if ($region->value_type == 'value_2') {
                                    $value_ongkir = $promo->promo_master->value_2;
                                } else {
                                    $value_ongkir = $promo->promo_master->value_1;
                                }

                                $max_merchant = ($promo->usage_value + $value_ongkir) > $promo->max_value;
                                $max_master = ($promo->promo_master->usage_value + $value_ongkir) > $promo->promo_master->max_value;

                                if ($max_merchant && !$max_master) {
                                    $merchant['delivery_discount'] = $value_ongkir;
                                    break;
                                }

                                if (!$max_merchant && $max_master) {
                                    $merchant['delivery_discount'] = $value_ongkir;
                                    break;
                                }

                                if (!$max_merchant && !$max_master) {
                                    $merchant['delivery_discount'] = $value_ongkir;
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            $merchant['products'] = $new_product;
            $merchant['total_weight'] = $total_weight;
            if ($merchant['delivery_discount'] > $merchant['delivery_fee']) {
                $merchant['delivery_discount'] = $merchant['delivery_fee'];
            }

            $merchant_total_price_with_delivery = $merchant_total_price + $merchant['delivery_fee'];
            $merchant['total_amount'] = $merchant_total_price;
            $merchant['total_payment'] = $merchant_total_payment = $merchant_total_price_with_delivery - $merchant['delivery_discount'];

            if ($promo_merchant_ongkir != null) {
                if ($promo_merchant_ongkir->promo_master->min_order_value > $merchant_total_price) {
                    $message_error = 'Minimal order untuk diskon ongkir adalah Rp ' . number_format($promo_merchant_ongkir->promo_master->min_order_value, 0, ',', '.');
                    $merchant['delivery_discount'] = 0;
                }

                $customer_limit_count = $promo_merchant_ongkir->promo_master->customer_limit_count;
                if ($customer_limit_count != null && $customer_limit_count > 0) {
                    $promo_logs = PromoLog::where('promo_master_id', $promo_merchant_ongkir->promo_master->id)
                        ->whereHas('order', function ($query) {
                            $query->where('buyer_id', auth()->user()->id);
                        })->get();

                    $promo_logs_add = collect($promo_logs)->where('type', 'add')->count();
                    $promo_logs_sub = collect($promo_logs)->where('type', 'sub')->count();

                    if ($customer_limit_count <= ($promo_logs_sub - $promo_logs_add)) {
                        $message_error = 'Anda telah melebihi batas penggunaan promo ini';
                        $merchant['delivery_discount'] = 0;
                    }
                }

                $promo_merchant_ongkir->promo_master->usage_value += $merchant['delivery_discount'];
                foreach ($promo_masters as $key => $promo_master) {
                    if ($promo_master->id == $promo_merchant_ongkir->promo_master->id) {
                        $promo_masters[$key] = $promo_merchant_ongkir->promo_master;
                        break;
                    }
                }
            }

            // flash sale discount
            $promo_merchant_flash_sale = null;
            $promo_flash_sale_value = null;
            $merchant['product_discount'] = 0;
            if ($data_merchant->can_flash_sale_discount == true) {
                foreach ($data_merchant->promo_merchant as $promo) {
                    if ($promo->promo_master->event_type == 'flash_sale') {
                        $value_flash_sale = 0;
                        $promo_merchant_flash_sale = $promo;

                        if ($promo_masters == []) {
                            $promo_masters[] = $promo->promo_master;
                        } else {
                            foreach ($promo_masters as $promo_master) {
                                if ($promo_master->id == $promo->promo_master->id) {
                                    $promo_merchant_flash_sale->promo_master = $promo_master;
                                    break;
                                } else {
                                    $promo_masters[] = $promo->promo_master;
                                }
                            }
                        }

                        $value_flash_sale = $promo->promo_master->value_1;
                        if ($promo->promo_master->promo_value_type == 'percentage') {
                            $value_flash_sale = $merchant_total_price * ($promo_merchant_flash_sale->promo_master->value_1 / 100);
                            if ($value_flash_sale >= $promo->promo_master->max_discount_value) {
                                $value_flash_sale = $promo->promo_master->max_discount_value;
                            }
                        }

                        foreach ($promo->promo_master->promo_values as $promo_value) {
                            $value_flash_sale = $promo->promo_master->value_1;
                            if ($promo->promo_master->promo_value_type == 'percentage') {
                                $value_flash_sale = $merchant_total_price * ($promo_merchant_flash_sale->promo_master->value_1 / 100);
                                if ($value_flash_sale >= $promo->promo_master->max_discount_value) {
                                    $value_flash_sale = $promo->promo_master->max_discount_value;
                                }
                            }

                            if ($merchant_total_price >= $promo_value->min_value && $merchant_total_price <= $promo_value->max_value && $promo_value->status == 1) {
                                $promo_flash_sale_value = $promo_value;

                                if ($value_flash_sale >= $promo_value->max_discount_value) {
                                    $value_flash_sale = $promo_value->max_discount_value;
                                }

                                break;
                            }
                        }

                        $max_merchant = ($promo->usage_value + $value_flash_sale) > $promo->max_value;
                        $max_master = ($promo->promo_master->usage_value + $value_flash_sale) > $promo->promo_master->max_value;

                        if ($max_merchant && !$max_master) {
                            $merchant['product_discount'] = $value_flash_sale;
                            break;
                        }

                        if (!$max_merchant && $max_master) {
                            $merchant['product_discount'] = $value_flash_sale;
                            break;
                        }

                        if (!$max_merchant && !$max_master) {
                            $merchant['product_discount'] = $value_flash_sale;
                            break;
                        }
                    }
                }
            }

            if ($promo_merchant_flash_sale != null) {
                if ($promo_merchant_flash_sale->promo_master->min_order_value > $merchant_total_price && $promo_flash_sale_value == null) {
                    $merchant['product_discount'] = 0;
                }

                $customer_limit_count = $promo_merchant_flash_sale->promo_master->customer_limit_count;
                if ($customer_limit_count != null && $customer_limit_count > 0) {
                    $promo_logs = PromoLog::where('promo_master_id', $promo_merchant_flash_sale->promo_master->id)
                        ->whereHas('order', function ($query) {
                            $query->where('buyer_id', auth()->user()->id);
                        })->get();

                    $promo_logs_add = collect($promo_logs)->where('type', 'add')->count();
                    $promo_logs_sub = collect($promo_logs)->where('type', 'sub')->count();

                    if ($customer_limit_count <= ($promo_logs_sub - $promo_logs_add)) {
                        $message_error = 'Anda telah melebihi batas penggunaan promo ini';
                        $merchant['product_discount'] = 0;
                    }
                }

                if ($merchant['product_discount'] > $merchant_total_price) {
                    $merchant['product_discount'] = $merchant_total_price;
                }

                $promo_merchant_flash_sale->promo_master->usage_value += $merchant['product_discount'];
                foreach ($promo_masters as $key => $promo_master) {
                    if ($promo_master->id == $promo_merchant_flash_sale->promo_master->id) {
                        $promo_masters[$key] = $promo_merchant_flash_sale->promo_master;
                        break;
                    }
                }
            }

            $merchant['total_payment'] = $merchant['total_payment'] - $merchant['product_discount'];

            $total_price += $merchant_total_price_with_delivery;
            $total_payment += $merchant_total_payment;
            $total_delivery_fee += $merchant['delivery_fee'];
            $total_delivery_discount += $merchant['delivery_discount'];
            $total_price_discount += $merchant['product_discount'];

            $data_merchant['order_count'] = count($data_merchant['orders']);

            unset($data_merchant->promo_merchant);
            unset($merchant['promo_merchant']);
            unset($data_merchant['orders']);

            $new_merchant[] = array_merge($merchant, $data_merchant->toArray());
        }

        $datas['merchants'] = $new_merchant;
        $datas['total_amount'] = $total_price;
        $datas['total_amount_without_delivery'] = $total_price - $total_delivery_fee;
        $datas['total_delivery_fee'] = $total_delivery_fee;
        $datas['total_delivery_discount'] = $total_delivery_discount;
        $datas['total_payment'] = $total_payment;

        // $total_discount = $total_price_discount = 0;
        // $percent_discount = 50;
        // $max_percent_discount = 500000;
        // $is_percent_discount = false;
        // $discount = $this->getCustomerDiscount($customer->id, $customer->email);

        // if ($discount == 0 && $is_percent_discount == true) {
        //     $total_item_price = 0;
        //     array_map(function ($merchant) use (&$total_item_price) {
        //         array_map(function ($product) use (&$total_item_price) {
        //             $total_item_price += $product['total_price'];
        //         }, data_get($merchant, 'products'));
        //     }, data_get($datas, 'merchants'));

        //     $discount = ($percent_discount / 100) * $total_item_price;
        //     if ($discount > $max_percent_discount) {
        //         $discount = $max_percent_discount;
        //     }
        // }

        $new_products = [];
        foreach ($datas['merchants'] as $merchant) {
            foreach ($merchant['products'] as $product) {
                $new_products[] = $product;
            }
        }

        if (isset($datas['customer']) && data_get($datas, 'customer') != null) {
            $ev_subsidy = null;
            foreach ($ev_subsidies as $subsidy) {
                if ($ev_subsidy == null) {
                    $ev_subsidy = $subsidy;
                } else {
                    if ($subsidy->subsidy_amount > $ev_subsidy->subsidy_amount) {
                        $ev_subsidy = $subsidy;
                    }
                }
            }

            $subsidy = false;
            foreach ($new_products as $key => $product) {
                if ($ev_subsidy != null && $subsidy == false) {
                    if ($ev_subsidy->merchant_id == $product['merchant_id'] && $ev_subsidy->product_id == $product['id']) {
                        $product['insentif'] += $ev_subsidy->subsidy_amount;
                        $product['total_insentif'] += $ev_subsidy->subsidy_amount;

                        $new_products[$key] = $product;
                        $subsidy = true;
                    }
                }
            }
        }

        $new_merchant = [];
        foreach (data_get($datas, 'merchants') as $merchant) {
            $product_insentif = 0;
            if (isset($datas['customer']) && data_get($datas, 'customer') != null) {
                $insentif = 0;
                foreach ($merchant['products'] as $key => $product) {
                    foreach ($new_products as $new_product_value) {
                        if ($product['product_id'] == $new_product_value['product_id']) {
                            $merchant['products'][$key]['insentif'] = $new_product_value['insentif'];
                            $merchant['products'][$key]['total_insentif'] = $new_product_value['total_insentif'];

                            $insentif += $merchant['products'][$key]['total_insentif'];
                        }
                    }
                }

                $product_insentif += $insentif;
            }
            // $count_discount = $discount;

            // if (data_get($merchant, 'total_payment') != null && data_get($merchant, 'total_payment') <= $discount) {
            //     $count_discount = data_get($merchant, 'total_payment');
            // }

            // data_set($merchant, 'total_payment', data_get($merchant, 'total_payment') - $count_discount);
            // $discount = $discount - $count_discount;
            // $total_discount += $count_discount;
            // $total_price_discount += data_get($merchant, 'total_payment');
            // $merchant['product_discount'] = $count_discount;
            $merchant['product_insentif'] = $product_insentif;
            $merchant['total_payment'] -= $product_insentif;

            $total_insentif += $product_insentif;

            $new_merchant[] = $merchant;
        }

        // $new_merchant2 = [];
        // foreach ($new_merchant as $merchant) {
        //     if (isset($datas['customer']) && data_get($datas, 'customer') != null) {
        //         foreach ($merchant['products'] as $key => $product) {
        //             $merchant['products'][$key]['ev_subsidy'] = $ev_subsidy;
        //         }
        //     }

        //     $new_merchant2[] = $merchant;
        // }

        $bonus_activation = MasterData::where('key', 'gami_bonus_activation')->select('value')->first();
        // payment discount
        $discount_payment = 0;
        $discount_type = null;
        $discount_claim_id = null;
        // cek jika di setting db bgami_bonus_activation nya active, maka jalankan perhitungan discount ini
        if ($bonus_activation->value === 'ACTIVE') {
            // cek jika total price minimal 50000
            $totalProductPrice = $total_price - $total_delivery_fee;

            if ($totalProductPrice >= 50000) {
                $bonus_amount = 0;
                $userId = auth()->user()->pln_mobile_customer_id;

                // if ($userId == null) {
                //     $userId = auth()->user()->id;
                // }
                // $userId = 981; //dummy
                // cek jika customer memiliki bonus discount, jika tidak maka tidak dapat discount payment
                $checkBonusDiscount = GamificationManager::claimBonusHold($userId, $totalProductPrice);

                // jika customer memiliki bonus discount, validasi apakah bonus discount nya valid atau tidak. Jika valid, maka discount payment nya di set sesuai dengan bonus discount nya
                if ($checkBonusDiscount['success'] === true) {
                    $validateBonusDiscount = GamificationManager::claimBonusValidate($checkBonusDiscount['data']['id'], $totalProductPrice);

                    if ($validateBonusDiscount['success'] === true) {
                        $bonus_amount += $validateBonusDiscount['data']['bonusAmount'];
                    }

                    $discount_payment += $bonus_amount;
                    $discount_type = 'GAMI-BONUS-DISCOUNT';
                    $discount_claim_id = $checkBonusDiscount['data']['id'];
                }
            }
        }

        $total_discount_payment += $discount_payment;

        // $datas['merchants'] = $new_merchant2;
        $datas['buyer_npwp'] = auth()->user()->npwp;
        $datas['merchants'] = $new_merchant;
        $datas['discount_type'] = $discount_type;
        $datas['discount_claim_id'] = $discount_claim_id;
        $datas['total_discount'] = $total_price_discount + $total_discount_payment;
        $datas['total_insentif'] = $total_insentif;
        $datas['total_payment'] -= $total_price_discount;
        $datas['total_payment'] -= $total_insentif;
        $datas['total_payment'] -= $total_discount_payment;

        if ($message_error != '') {
            $datas['success'] = false;
            $datas['status_code'] = 402;
            $datas['message'] = $message_error;
        } else {
            $datas['success'] = true;
            $datas['status_code'] = null;
            $datas['message'] = 'Berhasil menghitung total pembayaran';
        }

        // validasi tiket
        $master_tikets = MasterTiket::with(['master_data'])->where('status', 1)->get();
        $customer_tiket = Order::with(['detail', 'detail.product'])->where('buyer_id', $customer->id)
            ->whereHas('progress_active', function ($q) {
                $q->whereIn('status_code', ['00', '01', '02', '03', '08', '88']);
            })
            ->whereHas('detail', function ($q) use ($master_tikets) {
                $q->whereHas('product', function ($q) use ($master_tikets) {
                    $q->whereIn('category_id', collect($master_tikets)->pluck('master_data.id')->toArray());
                });
            })->get();

        $count_tiket = 0;
        foreach ($customer_tiket as $order) {
            foreach ($order->detail as $detail) {
                $tiket = collect($master_tikets)->where('master_data.id', $detail->product->category_id)->first();

                if ($tiket) {
                    $count_tiket += $detail->quantity;
                }
            }
        }

        $buying_tiket = false;
        foreach ($new_product as $product) {
            $tiket = collect($master_tikets)->where('master_data.id', $product['category_id'])->first();

            if ($tiket) {
                $count_tiket += $product['quantity'];

                $buying_tiket = true;
            }
        }

        if ($count_tiket > 4 && $buying_tiket) {
            $datas['success'] = false;
            $datas['status_code'] = 400;
            $datas['message'] = 'Anda telah melebihi batas pembelian tiket';
        }

        foreach ($datas['merchants'] as $merchant) {
            if ($merchant['order_count'] > 50) {
                $datas['success'] = false;
                $datas['status_code'] = 400;
                $datas['message'] = 'Mohon maaf, saat ini merchant tidak dapat melakukan transaksi';
            }
        }

        return $datas;
    }

    public function countCheckoutPriceV5($customer, $datas)
    {
        $city_id = data_get($datas, 'destination_info.city_id');
        $province_id = City::where('id', $city_id)->first()->province_id;
        $total_price = $total_payment = $total_delivery_discount = $total_delivery_fee = 0;
        $total_discount = $total_price_discount = $discount = 0;

        $new_merchant = [];
        $promo_masters = [];
        foreach (data_get($datas, 'merchants') as $merchant) {
            $total_weight = 0;
            $merchant_total_price = 0;

            $data_merchant = Merchant::with([
                'city',
                'district',
                'promo_merchant' => function ($pd) {
                    $pd->where('status', 1);
                    $pd->where(function ($query) {
                        $query->where('start_date', '<=', date('Y-m-d H:i:s'))
                            ->where('end_date', '>=', date('Y-m-d H:i:s'));
                    });
                    $pd->whereHas('promo_master', function ($pm) {
                        $pm->where('status', 1);
                    });
                },
                'promo_merchant.promo_master' => function ($pm) {
                    $pm->where('status', 1);
                },
                'promo_merchant.promo_master.promo_regions',
                'promo_merchant.promo_master.promo_values',
            ])->findOrFail($merchant['merchant_id']);

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

            // shipping discount
            $promo_merchant_ongkir = null;
            if ($data_merchant->can_shipping_discount == true) {
                foreach ($data_merchant->promo_merchant as $promo) {
                    if ($promo->promo_master->event_type == 'ongkir') {
                        foreach ($promo->promo_master->promo_regions as $region) {
                            $region_ids = collect($region->province_ids)->toArray();
                            if (in_array($province_id, $region_ids)) {
                                $promo_merchant_ongkir = $promo;

                                if ($promo_masters == []) {
                                    $promo_masters[] = $promo->promo_master;
                                } else {
                                    foreach ($promo_masters as $promo_master) {
                                        if ($promo_master->id == $promo->promo_master->id) {
                                            $promo_merchant_ongkir->promo_master = $promo_master;
                                            break;
                                        } else {
                                            $promo_masters[] = $promo->promo_master;
                                        }
                                    }
                                }

                                if ($region->value_type == 'value_2') {
                                    $value_ongkir = $promo->promo_master->value_2;
                                } else {
                                    $value_ongkir = $promo->promo_master->value_1;
                                }

                                $max_merchant = ($promo->usage_value + $value_ongkir) > $promo->max_value;
                                $max_master = ($promo->promo_master->usage_value + $value_ongkir) > $promo->promo_master->max_value;

                                if ($max_merchant && !$max_master) {
                                    $merchant['delivery_discount'] = $value_ongkir;
                                    break;
                                }

                                if (!$max_merchant && $max_master) {
                                    $merchant['delivery_discount'] = $value_ongkir;
                                    break;
                                }

                                if (!$max_merchant && !$max_master) {
                                    $merchant['delivery_discount'] = $value_ongkir;
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            $merchant['products'] = $new_product;
            $merchant['total_weight'] = $total_weight;
            if ($merchant['delivery_discount'] > $merchant['delivery_fee']) {
                $merchant['delivery_discount'] = $merchant['delivery_fee'];
            }

            $merchant['total_amount'] = $merchant_total_price_with_delivery = $merchant_total_price + $merchant['delivery_fee'];
            $merchant['total_payment'] = $merchant_total_payment = $merchant_total_price_with_delivery - $merchant['delivery_discount'];

            if ($promo_merchant_ongkir != null) {
                if ($promo_merchant_ongkir->promo_master->min_order_value > $merchant_total_price) {
                    $merchant['delivery_discount'] = 0;
                }

                $promo_merchant_ongkir->promo_master->usage_value += $merchant['delivery_discount'];
                foreach ($promo_masters as $key => $promo_master) {
                    if ($promo_master->id == $promo_merchant_ongkir->promo_master->id) {
                        $promo_masters[$key] = $promo_merchant_ongkir->promo_master;
                        break;
                    }
                }
            }

            // flash sale discount
            $promo_merchant_flash_sale = null;
            $merchant['product_discount'] = 0;
            if ($data_merchant->can_flash_sale_discount == true) {
                foreach ($data_merchant->promo_merchant as $promo) {
                    if ($promo->promo_master->event_type == 'flash_sale') {
                        $value_flash_sale = 0;
                        $promo_merchant_flash_sale = $promo;

                        if ($promo_masters == []) {
                            $promo_masters[] = $promo->promo_master;
                        } else {
                            foreach ($promo_masters as $promo_master) {
                                if ($promo_master->id == $promo->promo_master->id) {
                                    $promo_merchant_flash_sale->promo_master = $promo_master;
                                    break;
                                } else {
                                    $promo_masters[] = $promo->promo_master;
                                }
                            }
                        }

                        $value_flash_sale = $promo->promo_master->value_1;
                        if ($promo->promo_master->promo_value_type == 'percentage') {
                            $value_flash_sale = $merchant_total_price * ($promo_merchant_flash_sale->promo_master->value_1 / 100);

                            if ($value_flash_sale >= $promo->promo_master->max_discount_value) {
                                $value_flash_sale = $promo->promo_master->max_discount_value;
                            }
                        }

                        if ($promo->promo_master->promo_values != null) {
                            foreach ($promo->promo_master->promo_values as $promo_value) {
                                if ($merchant_total_price > $promo_value->min_value && $merchant_total_price < $promo_value->max_value) {
                                    if ($value_flash_sale >= $promo_value->max_discount_value) {
                                        $value_flash_sale = $promo_value->max_discount_value;
                                    }
                                }
                            }
                        }

                        $max_merchant = ($promo->usage_value + $value_flash_sale) > $promo->max_value;
                        $max_master = ($promo->promo_master->usage_value + $value_flash_sale) > $promo->promo_master->max_value;

                        if ($max_merchant && !$max_master) {
                            $merchant['product_discount'] = $value_flash_sale;
                            break;
                        }

                        if (!$max_merchant && $max_master) {
                            $merchant['product_discount'] = $value_flash_sale;
                            break;
                        }

                        if (!$max_merchant && !$max_master) {
                            $merchant['product_discount'] = $value_flash_sale;
                            break;
                        }
                    }
                }
            }

            if ($promo_merchant_flash_sale != null) {
                if ($promo_merchant_flash_sale->promo_master->min_order_value > $merchant_total_price) {
                    $merchant['product_discount'] = 0;
                }

                $promo_merchant_flash_sale->promo_master->usage_value += $merchant['product_discount'];
                foreach ($promo_masters as $key => $promo_master) {
                    if ($promo_master->id == $promo_merchant_flash_sale->promo_master->id) {
                        $promo_masters[$key] = $promo_merchant_flash_sale->promo_master;
                        break;
                    }
                }
            }

            $total_price += $merchant_total_price_with_delivery;
            $total_payment += $merchant_total_payment;
            $total_delivery_fee += $merchant['delivery_fee'];
            $total_delivery_discount += $merchant['delivery_discount'];
            $total_price_discount += $merchant['product_discount'];

            unset($data_merchant->promo_merchant);
            unset($merchant['promo_merchant']);

            array_push($new_merchant, array_merge($merchant, $data_merchant->toArray()));
        }

        $datas['merchants'] = $new_merchant;
        $datas['total_amount'] = $total_price;
        $datas['total_amount_without_delivery'] = $total_price - $total_delivery_fee;
        $datas['total_delivery_fee'] = $total_delivery_fee;
        $datas['total_delivery_discount'] = $total_delivery_discount;
        $datas['total_payment'] = $total_payment;

        // $total_discount = $total_price_discount = 0;
        // $percent_discount = 50;
        // $max_percent_discount = 500000;
        // $is_percent_discount = false;
        // $discount = $this->getCustomerDiscount($customer->id, $customer->email);
        // $discount = 0;

        // if ($discount == 0 && $is_percent_discount == true) {
        //     $total_item_price = 0;
        //     array_map(function ($merchant) use (&$total_item_price) {
        //         array_map(function ($product) use (&$total_item_price) {
        //             $total_item_price += $product['total_price'];
        //         }, data_get($merchant, 'products'));
        //     }, data_get($datas, 'merchants'));

        //     $discount = ($percent_discount / 100) * $total_item_price;
        //     if ($discount > $max_percent_discount) {
        //         $discount = $max_percent_discount;
        //     }
        // }

        // foreach (data_get($datas, 'merchants') as $key => $merchant) {
        // $count_discount = $discount;

        // if (data_get($merchant, 'total_payment') != null && data_get($merchant, 'total_payment') <= $discount) {
        //     $count_discount = data_get($merchant, 'total_payment');
        // }

        // data_set($merchant, 'total_payment', data_get($merchant, 'total_payment') - $count_discount);
        // $discount = $discount - $count_discount;
        // $total_discount += $count_discount;
        // $total_price_discount += data_get($merchant, 'total_payment');
        //     $merchant['product_discount'] = $count_discount;

        //     $new_merchant[$key] = $merchant;
        // }

        $datas['merchants'] = $new_merchant;
        $datas['total_discount'] = $total_price_discount;
        $datas['total_payment'] -= $total_price_discount;
        $datas['buyer_npwp'] = auth()->user()->npwp;

        return $datas;
    }

    public function createOrderV2($request)
    {
        $merchants = [];
        foreach (data_get($request, 'merchants') as $merchant) {
            $get_merchant = Merchant::find(data_get($merchant, 'merchant_id'));

            if (data_get($merchant, 'delivery_method') == 'custom') {
                if (data_get($merchant, 'has_custom_logistic') == false || null) {
                    throw new Exception('Merchant ' . data_get($merchant, 'name') . ' tidak mendukung pengiriman oleh seller', 404);
                }
                data_set($merchant, 'delivery_method', 'Pengiriman oleh Seller');
            }

            foreach (data_get($merchant, 'products') as $item) {
                if (!$product = Product::find(data_get($item, 'product_id'))) {
                    throw new Exception('Produk dengan id ' . data_get($item, 'product_id') . ' tidak ditemukan', 404);
                }
                if ($product->stock_active->amount < data_get($item, 'quantity')) {
                    throw new Exception('Stok produk dengan id ' . $product->id . ' tidak mencukupi', 400);
                }
                if (data_get($item, 'quantity') < $product->minimum_purchase) {
                    throw new Exception('Pembelian minimum untuk produk ' . $product->name . ' adalah ' . $product->minimum_purchase, 400);
                }
                if (data_get($item, 'variant_value_product_id') != null) {
                    if (
                        VariantStock::where('variant_value_product_id', data_get($item, 'variant_value_product_id'))
                        ->where('status', 1)->pluck('amount')->first() < data_get($item, 'quantity')
                    ) {
                        throw new Exception('Stok variant produk dengan id ' . data_get($item, 'variant_value_product_id') . ' tidak mencukupi', 400);
                    }
                }
            }

            $merchant['can_shipping_discount'] = $get_merchant->can_shipping_discount;
            $merchant['can_flash_sale_discount'] = $get_merchant->can_flash_sale_discount;
            $merchant['is_shipping_discount'] = $get_merchant->is_shipping_discount;

            $merchants[] = $merchant;
        }

        return $merchants;
    }

    public function createOrderV3($request)
    {
        $merchants = [];
        foreach (data_get($request, 'merchants') as $merchant) {
            $get_merchant = Merchant::find(data_get($merchant, 'merchant_id'));

            if (data_get($merchant, 'delivery_method') == 'custom') {
                if (data_get($merchant, 'has_custom_logistic') == false || null) {
                    throw new Exception('Merchant ' . data_get($merchant, 'name') . ' tidak mendukung pengiriman oleh seller', 404);
                }
                data_set($merchant, 'delivery_method', 'Pengiriman oleh Seller');
            }

            foreach (data_get($merchant, 'products') as $item) {
                if (!$product = Product::find(data_get($item, 'product_id'))) {
                    throw new Exception('Produk dengan id ' . data_get($item, 'product_id') . ' tidak ditemukan', 404);
                }
                if ($product->stock_active->amount < data_get($item, 'quantity')) {
                    throw new Exception('Stok produk dengan id ' . $product->id . ' tidak mencukupi', 400);
                }
                if (data_get($item, 'quantity') < $product->minimum_purchase) {
                    throw new Exception('Pembelian minimum untuk produk ' . $product->name . ' adalah ' . $product->minimum_purchase, 400);
                }
                if (data_get($item, 'variant_value_product_id') != null) {
                    if (
                        VariantStock::where('variant_value_product_id', data_get($item, 'variant_value_product_id'))
                        ->where('status', 1)->pluck('amount')->first() < data_get($item, 'quantity')
                    ) {
                        throw new Exception('Stok variant produk dengan id ' . data_get($item, 'variant_value_product_id') . ' tidak mencukupi', 400);
                    }
                }
            }

            // $promo_merchant = PromoMerchant::with(['promo_master', 'promo_master.promo_regions', 'promo_master.promo_values'])
            //     ->where([
            //         'merchant_id' => data_get($merchant, 'id'),
            //         'status' => 1,
            //     ])
            //     ->where('start_date', '<=', date('Y-m-d'))
            //     ->where('end_date', '>=', date('Y-m-d'))
            //     ->whereHas('promo_master', function ($query) {
            //         $query->where('status', 1);
            //     })
            //     ->get();

            // $merchant['promo_merchant'] = $promo_merchant;
            $merchant['can_shipping_discount'] = $get_merchant->can_shipping_discount;
            $merchant['can_flash_sale_discount'] = $get_merchant->can_flash_sale_discount;
            $merchant['is_shipping_discount'] = $get_merchant->is_shipping_discount;

            $merchants[] = $merchant;
        }

        return $merchants;
    }

    public function getTransactionByReference($no_reference)
    {
        $orders = Order::with(['detail', 'promo_log_orders', 'progress_active', 'delivery'])->where('no_reference', $no_reference)->get();
        return $orders;
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

        $itemsTransformed = array_map(function ($item) {
            $delivery = json_decode($item['delivery']['merchant_data']);

            if ($delivery != null) {
                $item['merchant']['address'] = $delivery->merchant_address;
                $item['merchant']['province_id'] = $delivery->merchant_province_id;
                $item['merchant']['city_id'] = $delivery->merchant_city_id;
                $item['merchant']['district_id'] = $delivery->merchant_district_id;
                $item['merchant']['subdistrict_id'] = $delivery->merchant_subdistrict_id;
                $item['merchant']['postal_code'] = $delivery->merchant_postal_code;
                $item['merchant']['latitude'] = $delivery->merchant_latitude;
                $item['merchant']['longitude'] = $delivery->merchant_longitude;

                $item['merchant']['phone_office'] = $delivery->merchant_phone_office;
                $item['merchant']['name'] = $delivery->merchant_name;

                unset($item['delivery']['merchant_data']);
            }

            return $item;
        }, $itemsPaginated->getCollection()->toArray());

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

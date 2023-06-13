<?php

namespace App\Http\Services\Merchant\Agent;

use App\Http\Services\Manager\AgentManager;
use App\Http\Services\Manager\KudoManager;
use App\Http\Services\Service;
use App\Models\AgentMenu;
use App\Models\AgentOrder;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;

class AgentQueries extends Service
{
    static $kudoManager;
    protected $agentManager;

    public function __construct()
    {
        self::$kudoManager = new KudoManager();
        $this->agentManager = new AgentManager();
    }

    public function getMenu()
    {
        $merchant_id = Auth::user()->merchant_id;
        $menus = AgentMenu::with([
            'margin' => function ($query) use ($merchant_id) {
                $query->where('merchant_id', $merchant_id);
            },
        ])->where('status', 1)->get();

        return $menus;
    }

    public function getDetailMenu($agent_id, bool $with_product_groups = false)
    {
        $merchant_id = Auth::user()->merchant_id;
        $menu = AgentMenu::find($agent_id);

        throw_if(empty($menu), new Exception('Not found.', 404));

        $menu = $menu->load([
            'margin' => function ($query) use ($merchant_id) {
                $query->where('merchant_id', $merchant_id);
            },
        ]);

        if ($with_product_groups) {
            $product_groups = self::$kudoManager->getProductGroupByCategoryId($menu->reference_id);
            $menu['product_groups'] = $product_groups['data']['product_groups'];
        }

        return $menu;
    }

    // getOrder
    public function getOrder($transaction_id)
    {
        $order = AgentOrder::where('trx_no', $transaction_id)->first()
            ->load(['progress_active', 'payments']);

        return $order;
    }

    public function getTransaction($limit = 10, $page = 1)
    {
        $merchant_id = Auth::user()->merchant_id;
        $orders = AgentOrder::where('merchant_id', $merchant_id)->whereDate('created_at', Carbon::today())
            ->with(['progress_active', 'payments'])
            ->orderBy('created_at', 'desc')
            ->paginate($limit);

        return $orders;
    }

    public function getDetailTransaction($order_id)
    {
        $order = AgentOrder::where('id', $order_id)->first()
            ->load(['progress_active', 'payments']);

        return $order;
    }

    //get transaction with filter status, range date, and sort by
    public function getTransactionWithFilter($limit = 10, $page = 1, $keyword = null, $filter = [], $sortby = null)
    {
        $merchant_id = Auth::user()->merchant_id;
        $agentOrders = new AgentOrder();
        $orders = $agentOrders->where('merchant_id', $merchant_id)->whereDate('created_at', Carbon::today())
            ->with(['progress_active', 'payments']);

        if ($keyword) {
            $orders = $orders->where(function ($query) use ($keyword) {
                $query->where('product_name', 'ILIKE', "%{$keyword}%")
                    ->orWhere('customer_id', 'ILIKE', "%{$keyword}%")
                    ->orWhere('meter_number', 'ILIKE', "%{$keyword}%");
            });
        }

        $filtered = $this->filter($orders, $filter);

        if ($sortby) {
            $sorted = $this->sorting($filtered, $sortby);
        } else {
            $sorted = $filtered->orderBy('created_at', 'desc');
        }

        $orders = $sorted->paginate($limit);

        return $orders;
    }

    public function filter($model, $filter = [])
    {
        if (count($filter) > 0) {
            $keyword = $filter['keyword'] ?? null;
            $status = $filter['status'] ?? null;
            $start_date = $filter['start_date'] ?? null;
            $end_date = $filter['end_date'] ?? null;
            $product = $filter['product'] ?? null;

            $data = $model->when(!empty($keyword), function ($query) use ($keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('product_name', 'ILIKE', "%{$keyword}%")
                        ->orWhere('customer_id', 'ILIKE', "%{$keyword}%")
                        ->orWhere('meter_number', 'ILIKE', "%{$keyword}%");
                });
            })
                ->when(!empty($status), function ($query) use ($status) {
                    $statusArr = explode(',', $status);
                    $statusMap = [
                        'waiting' => '00',
                        'process' => ['01', '03', '09'],
                        'failed' => '08',
                        'reversal' => '05',
                        'succeed' => '04',
                    ];
                    $statusCodes = [];
                    foreach ($statusArr as $status) {
                        if (isset($statusMap[$status])) {
                            $statusCode = $statusMap[$status];
                            if (is_array($statusCode)) {
                                $statusCodes = array_merge($statusCodes, $statusCode);
                            } else {
                                $statusCodes[] = $statusCode;
                            }
                        }
                    }
                    if (!empty($statusCodes)) {
                        $query->whereHas('progress_active', function ($query) use ($statusCodes) {
                            $query->whereIn('status_code', $statusCodes);
                        });
                    }
                })
                ->when(!empty($start_date) && !empty($end_date), function ($query) use ($start_date, $end_date) {
                    $query->where(function ($query) use ($start_date, $end_date) {
                        $query->whereDate('created_at', '>=', $start_date)
                            ->whereDate('created_at', '<=', $end_date);
                    });
                })
                ->when(!empty($product), function ($query) use ($product) {
                    $query->where('product_name', 'ILIKE', "%{$product}%");
                });

            return $data;
        } else {
            return $model;
        }
    }

    public function sorting($model, $sortby = null)
    {
        if (!empty($sortby)) {
            $data = $model->when($sortby == 'newest', function ($query) {
                $query->orderBy('created_at', 'desc');
            })->when($sortby == 'oldest', function ($query) {
                $query->orderBy('created_at', 'asc');
            });

            return $data;
        } else {
            return $model;
        }
    }
}

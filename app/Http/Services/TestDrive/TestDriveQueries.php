<?php

namespace App\Http\Services\TestDrive;

use App\Http\Services\Service;
use App\Models\Product;
use App\Models\TestDrive;
use App\Models\TestDriveBooking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TestDriveQueries extends Service
{
    public function getAllEvent($merchant_id = null, $filter = [], $sortby = null, $current_page = 1)
    {
        $raw_data = TestDrive::with(['merchant:id,name,photo_url', 'city:id,name', 'product' => function($product){
            $product->with(['product_photo:id,product_id,url'])->select(['product.id', 'product.merchant_id', 'product.name']);
        }])
        ->when(!empty($merchant_id), function ($query) use ($merchant_id) {
            $query->where('merchant_id', $merchant_id);
        })->select(['id', 'merchant_id', 'title', 'area_name', 'address', 'city_id', 'latitude', 'longitude', 'start_date', 'end_date', 'start_time', 'end_time', 'status']);

        $filtered_data = $this->filter($raw_data, $filter);
        $sorted_data = $this->sorting($filtered_data, $sortby);
        $data = static::paginate(($sorted_data->get())->toArray(), 10, $current_page);

        return $data;
    }

    public function getEVProducts($current_page = 1)
    {
        $data = Product::with(['product_photo'])->whereHas('category', function ($category) {
            $category->whereHas('parent', function ($basic) {
                $basic->where('key', 'ILIKE', '%mobil_listrik%')->orWhere('key', 'ILIKE', '%motor_listrik%');
            });
        })
            ->where('merchant_id', Auth::user()->merchant_id)
            ->get();

        $data = static::paginate($data->toArray(), 10, $current_page);

        return $data;
    }

    public function getDetailEvent($id)
    {
        $detail_event = TestDrive::with(['merchant:id,name,photo_url', 'product' => function($product){
            $product->with(['product_photo:id,product_id,url'])->select(['product.id', 'product.merchant_id', 'product.name']);
        }])->find($id);
        $visitor_by_date = $this->getVisitorByDate($id);

        $data['detail'] = $detail_event;
        $data['visitor_by_date'] = $visitor_by_date;
        return $data;
    }

    public function getVisitorByDate($test_drive_id)
    {
        $data = DB::table('test_drive_booking')
        ->select('visit_date', DB::raw('count(id) as total_visitor'))
        ->groupBy(['visit_date'])
        ->where('test_drive_id', $test_drive_id)
        ->get();
        return $data;
    }

    public function filter($model, $filter = [])
    {
        if (count($filter) > 0) {
            $keyword = $filter['keyword'] ?? null;
            $city = $filter['city'] ?? null;
            $status = $filter['status'] ?? null;
            $date = $filter['date'] ?? null;

            $data = $model->when(!empty($keyword), function ($query) use ($keyword) {
                $query->where('title', 'LIKE', "%{$keyword}%");
            })->when(!empty($city), function ($query) use ($city) {
                if (strpos($city, ',')) {
                    $query->whereIn('city_id', explode(',', $city));
                } else {
                    $query->where('city_id', $city);
                }
            })->when(!empty($status), function ($query) use ($status) {
                $now = Carbon::now()->timezone('Asia/Jakarta')->format('Y-m-d');
                if ($status == 1) {
                    $query->where('end_date', '>=', $now)->where('status',1);
                } else {
                    $query->whereIn('status', [2,9])->orWhere('end_date', '>', $now);
                }
            })->when(!empty($date), function ($query) use ($date) {
                $query->where('start_date', '<=', $date)->where('end_date', '>=', $date);
            });

            return $data;
        } else {
            return $model;
        }
    }

    public function sorting($model, $sortby = null)
    {
        if (!empty($sortby)) {
            $param = explode(':', str_replace([',', ';', '.'], ':', $sortby));
            $data = $model->when($param[0] == 'date', function ($query) use($param) {
                $query->orderBy('start_date', $param[1]);
            });

            return $data;
        } else {
            return $model;
        }
    }
}

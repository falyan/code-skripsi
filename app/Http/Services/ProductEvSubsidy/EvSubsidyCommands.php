<?php

namespace App\Http\Services\ProductEvSubsidy;

use App\Http\Services\Service;
use App\Models\CustomerEVSubsidy;
use App\Models\Order;
use App\Models\OrderProgress;
use App\Models\ProductEvSubsidy;
use App\Models\ProductStock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EvSubsidyCommands extends Service
{
    public function create($request)
    {
        $products = $request['products'];

        $ev_products = [];
        foreach ($products as $product) {
            $ev_products[] = [
                'product_id' => $product['product_id'],
                'merchant_id' => auth()->user()->merchant_id,
                'subsidy_amount' => $product['subsidy_amount'],
                'created_by' => auth()->user()->full_name,
                'created_at' => Carbon::now(),
            ];
        }

        try {
            DB::beginTransaction();
            ProductEVSubsidy::insert($ev_products);

            DB::commit();

            return [
                'status' => true,
                'message' => 'EV Subsidi berhasil dibuat',
                'data' => $ev_products,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'status' => false,
                'message' => 'EV Subsidi gagal dibuat',
                'errors' => $e->getMessage(),
            ];
        }
    }

    public function updateEvSubsidy($request, $id)
    {
        $subsidy_amount = $request['subsidy_amount'];

        $ev_product = ProductEvSubsidy::find($id);

        if (!$ev_product) {
            return [
                'status' => false,
                'message' => 'EV Subsidi tidak ditemukan',
            ];
        }

        $ev_product->subsidy_amount = $subsidy_amount;
        $ev_product->updated_by = auth()->user()->full_name;
        $ev_product->updated_at = Carbon::now();

        try {
            DB::beginTransaction();
            $ev_product->save();

            DB::commit();

            return [
                'status' => true,
                'message' => 'EV Subsidi berhasil diupdate',
                'data' => $ev_product,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'status' => false,
                'message' => 'EV Subsidi gagal diupdate',
                'errors' => $e->getMessage(),
            ];
        }
    }

    public function deleteEvSubsidy($request)
    {
        $ev_products = ProductEvSubsidy::whereIn('id', $request['ids']);

        if (!$ev_products->get()) {
            return [
                'status' => false,
                'message' => 'EV Subsidi tidak ditemukan',
            ];
        }

        try {
            DB::beginTransaction();
            $ev_products->delete();

            DB::commit();

            return [
                'status' => true,
                'message' => 'EV Subsidi berhasil dihapus',
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'status' => false,
                'message' => 'EV Subsidi gagal dihapus',
                'errors' => $e->getMessage(),
            ];
        }
    }

    public function updateStatus($request)
    {
        $data = CustomerEVSubsidy::findOrFail($request['ev_subsidy_id']);

        if ($data->status_approval != null) {
            return [
                'status' => false,
                'message' => 'Status tidak dalam status menunggu',
                'errors' => 'Status approval telah ' . ($data->status_approval == 0 ? 'ditolak' : 'disetujui'),
            ];
        }

        try {
            DB::beginTransaction();
            $data->status_approval = $request['status'];
            $data->save();

            if ($request['status'] == 0) {
                $order = Order::with('detail')->findOrFail($data->order_id);
                foreach ($order->detail as $detail) {
                    $product_stock = ProductStock::where([
                        'product_id' => $detail->product_id,
                        'status' => 1,
                    ])->first();

                    $product_stock->amount += $detail->quantity;
                    $product_stock->save();
                }

                $order_progress = OrderProgress::where('order_id', $data->order_id)->first();
                $order_progress->status = 0;
                $order_progress->save();

                OrderProgress::create([
                    'order_id' => $data->order_id,
                    'note' => 'Pengajuan Subsidi Ditolak',
                    'status' => 1,
                    'status_code' => '99',
                    'status_name' => 'Pesanan Dibatalkan',
                    'created_by' => auth()->user()->full_name,
                    'updated_by' => auth()->user()->full_name,
                ]);
            }

            DB::commit();

            return [
                'status' => true,
                'message' => 'Status berhasil diupdate',
                'data' => $data,
            ];
        } catch (\Throwable $th) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'Status gagal diupdate',
                'errors' => $th->getMessage(),
            ];
        }
    }
}

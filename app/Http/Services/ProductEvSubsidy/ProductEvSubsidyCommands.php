<?php

namespace App\Http\Services\ProductEvSubsidy;

use App\Http\Services\Service;
use App\Models\Product;
use App\Models\ProductEvSubsidy;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProductEvSubsidyCommands extends Service
{
    public function create($request)
    {
        $productIds = $request['product_ids'];

        $products = Product::whereIn('id', $productIds)->get();

        $ev_products = [];
        foreach ($products as $product) {
            $ev_products[] = [
                'product_id' => $product->id,
                'merchant_id' => auth()->user()->merchant_id,
                'subsidy_amount' => 0,
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
}

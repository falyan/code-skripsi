<?php

namespace App\Http\Services\Etalase;

use App\Http\Services\Service;
use App\Models\Etalase;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EtalaseCommands extends Service
{
    public static function storeItem($request){
        try {
            $item_names = Etalase::where('merchant_id', data_get($request, 'merchant_id'))->get()->pluck('name');
            $recorded = [];
            foreach ($item_names as $origin) {
                array_push($recorded, lcfirst($origin));
            }
            if (in_array(lcfirst(data_get($request, 'name')), $recorded)) {
                throw new Exception('Nama etalase ini sudah anda gunakan', 400);
            }
            
            DB::beginTransaction();
            $record = Etalase::create([
                'merchant_id' => data_get($request, 'merchant_id'),
                'name' => data_get($request, 'name'),
                'created_by' => data_get($request, 'full_name'),
                'updated_by' => data_get($request, 'full_name'),
            ]);
            DB::commit();

            return $record;
        } catch (Exception $th) {
            DB::rollBack();
            throw new Exception($th->getMessage(), $th->getCode());
        }
    }

    public static function updateItem($id, $request)
    {
        try {
            $item_names = Etalase::where('merchant_id', data_get($request, 'merchant_id'))->get()->pluck('name');
            $recorded = [];
            foreach ($item_names as $origin) {
                array_push($recorded, lcfirst($origin));
            }
            if (in_array(lcfirst(data_get($request, 'name')), $recorded)) {
                throw new Exception('Nama etalase ini sudah anda gunakan', 400);
            }

            $item = Etalase::find($id);

            if (strtolower($item->name) == 'semua produk') {
                throw new Exception('Etalase ' . $item->name . ' tidak dapat diubah', 400);
            }

            DB::beginTransaction();
            $item->name = data_get($request, 'name') == null ? $item->name : data_get($request, 'name');
            $item->updated_by = Auth::user()->full_name;
            $item->save();
            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            throw new Exception($th->getMessage(), $th->getCode());
        }
    }

    public static function deleteItem($id)
    {
        try {
            $item = Etalase::with('product')->find($id);
            if (strtolower($item->name) == 'semua produk') {
                throw new Exception('Etalase ' . $item->name . ' tidak dapat dihapus', 400);
            }
            DB::beginTransaction();
            self::moveProductToDefaultEtalase($item->product);
            $item->delete();
            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            throw new Exception($th->getMessage(), $th->getCode());
        }
    }

    public static function moveProductToDefaultEtalase($products)
    {
        try {
            //find default etalase
            $default = Etalase::where('merchant_id', Auth::user()->merchant_id)->whereIn('name', ['semua produk', 'Semua produk', 'Semua Produk', 'SEMUA PRODUK'])->first();
            
            if ($default == null) {
                $default = self::createDefault(Auth::user()->merchant_id);
            }else {
                throw new Exception('Failed to move product to default etalase', 400);
            }
            //update etalase to default
            $total = count($products) ?? 0;
            for ($i=0; $i < $total; $i++) { 
                $products[$i]->etalase_id = $default->id;
                $products[$i]->updated_by = Auth::user()->full_name;
                $products[$i]->save();
            }
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage(), $th->getCode());
        }
    }

    public static function createDefault($merchant_id)
    {
        $request = [
            'merchant_id' => $merchant_id,
            'name' => "Semua Produk",
            'full_name' => "System",
        ];
        $default = self::storeItem($request);

        return $default;
    }
}

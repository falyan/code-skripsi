<?php

namespace App\Http\Services\Etalase;

use App\Models\Etalase;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EtalaseCommands{
    public static function storeItem($request){
        try {

            $item_names = Etalase::where('merchant_id', data_get($request, 'merchant_id'))->get()->pluck('name');
            $recorded = [];
            foreach ($item_names as $origin) {
                array_push($recorded, $origin);
            }
            
            if (in_array(data_get($request, 'name'), $recorded)) {
                throw new Exception('Nama etalase ini sudah anda gunakan', 400);
            }

            DB::beginTransaction();
            $record = Etalase::create([
                'merchant_id' => data_get($request, 'merchant_id'),
                'name' => data_get($request, 'name'),
                'created_by' => data_get($request, 'full_name'),
                'updated_by' => data_get($request, 'full_name')
            ]);
            DB::commit();

            return $record;
        } catch (Exception $th) {
            DB::rollBack();
            throw new Exception($th->getMessage(), $th->getCode());
        }
    }

    public static function deleteItem($id)
    {
        try {
            $item = Etalase::find($id);
            $item->delete();
        } catch (\Exception $th) {
            throw new Exception($th->getMessage(), $th->getCode());
        }
    }
}

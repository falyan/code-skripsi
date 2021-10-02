<?php

namespace App\Http\Services\Example;

use App\Models\Etalase;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EtalaseCommands{
    public static function storeItem($request){
        try {

            DB::beginTransaction();
            $record = Etalase::create([
                'merchant_id' => data_get($request, 'merchant_id'),
                'name' => data_get($request, 'name'),
                'created_by' => null, //! ?? user dapet dari table customer, api create customer masih blum ada?
                'updated_by' => null //! ??
            ]);
            DB::commit();

            return $record;
        } catch (\Exception $th) {
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

<?php

namespace App\Http\Services\Etalase;

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
                'created_by' => "pudidi",
                'updated_by' => "pudidi"
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

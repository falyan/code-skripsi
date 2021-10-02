<?php

namespace App\Http\Services\Etalase;

use App\Http\Resources\Etalase\EtalaseCollection;
use App\Http\Resources\Etalase\EtalaseResource;
use App\Models\Etalase;

class EtalaseQueries{
    public static function getAll(){
        $paginate = Etalase::paginate();
        return new EtalaseCollection($paginate);
    }

    public static function getById($id)
    {
        $row = Etalase::where('id', $id)->first();
        return new EtalaseResource($row);
    }
}

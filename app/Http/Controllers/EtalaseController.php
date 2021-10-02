<?php

namespace App\Http\Controllers;

use App\Http\Resources\Etalase\EtalaseResource;
use App\Http\Services\Example\EtalaseCommands;
use App\Http\Services\Example\ExampleCommands;
use Exception;
use Illuminate\Support\Facades\Validator;

class EtalaseController extends Controller
{
    public function store()
    {
        $validator = Validator::make(request()->all(), [
            'merchant_id' => 'required',
            'name' => 'required'
        ]);
        
        try {
            if ($validator->fails()) {
                throw new Exception($validator->errors(), 400);
            }

            $record = EtalaseCommands::storeItem(request()->all());
            
            return new EtalaseResource($record);
        } catch (\Throwable $th) {
            return response()->json(['error' => ['code' => 'ERROR', 'http_code' => $th->getCode(), 'message' => $th->getMessage()]]);
        }
    }
}

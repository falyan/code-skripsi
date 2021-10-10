<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Etalase\EtalaseCollection;
use App\Http\Resources\Etalase\EtalaseResource;
use App\Http\Services\Etalase\EtalaseCommands;
use App\Http\Services\Etalase\EtalaseQueries;
use App\Http\Services\Example\ExampleCommands;
use App\Http\Services\Manager\RajaOngkirManager;
use App\Models\Etalase;
use Exception, Input;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EtalaseController extends Controller
{
    public function index()
    {
        try {
            return EtalaseQueries::getAll(Auth::user()->merchant->id);
        } catch (\Throwable $th) {
            if (in_array($th->getCode(), $this->error_codes)) {
                return response()->json(['error' => ['code' => 'ERROR', 'http_code' => $th->getCode(), 'message' => $th->getMessage()]], $th->getCode());
            }
            return response()->json(['error' => ['code' => 'ERROR', 'http_code' => $th->getCode(), 'message' => $th->getMessage()]], 404);
        }
    }


    public function store()
    {
        $validator = Validator::make(request()->all(), [
            'merchant_id' => 'required',
            'name' => 'required'
        ]);
        
        request()->request->add([
            'full_name' => Auth::user()->full_name
        ]);
        
        try {
            if ($validator->fails()) {
                throw new Exception($validator->errors(), 400);
            }

            $record = EtalaseCommands::storeItem(request()->all());
            
            return $this->respondWithData(new EtalaseResource($record), 'Success saved data');
        } catch (\Throwable $th) {
            if (in_array($th->getCode(), $this->error_codes)) {
                return response()->json(['error' => ['code' => 'ERROR', 'http_code' => $th->getCode(), 'message' => $th->getMessage()]], $th->getCode());
            }
            return response()->json(['error' => ['code' => 'ERROR', 'http_code' => $th->getCode(), 'message' => $th->getMessage()]], 404);
        }
    }

    public function delete($id)
    {
        try {
            EtalaseCommands::deleteItem($id);

            return response()->json(['success' => true, 'message' => 'Item Etalase Berhasil Dihapus']);
        }  catch (\Throwable $th) {
            if (in_array($th->getCode(), $this->error_codes)) {
                return response()->json(['error' => ['code' => 'ERROR', 'http_code' => $th->getCode(), 'message' => $th->getMessage()]], $th->getCode());
            }
            return response()->json(['error' => ['code' => 'ERROR', 'http_code' => $th->getCode(), 'message' => $th->getMessage()]], 404);
        }
    }

    public function show($id)
    {
        try {
            return EtalaseQueries::getById($id);
        } catch (\Throwable $th) {
            if (in_array($th->getCode(), $this->error_codes)) {
                return response()->json(['error' => ['code' => 'ERROR', 'http_code' => $th->getCode(), 'message' => $th->getMessage()]], $th->getCode());
            }
            return response()->json(['error' => ['code' => 'ERROR', 'http_code' => $th->getCode(), 'message' => $th->getMessage()]], 404);
        }
    }
}
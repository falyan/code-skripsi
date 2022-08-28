<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\ManualTransfer\ManualTransferCommands;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ManualTransferController extends Controller
{
    protected $commands;

    public function __construct()
    {
        $this->commands  = new ManualTransferCommands();
    }

    public function create(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'idpel' => 'required|exists:payment,no_reference',
                'produk' => 'required',
            ], [
                'required' => ':attribute diperlukan.',
            ]);

            if ($validator->fails()) {
                $errors = collect();
                foreach ($validator->errors()->getMessages() as $key => $value) {
                    foreach ($value as $error) {
                        $errors->push($error);
                    }
                }

                return $this->respondValidationError($errors, 'Validation Error!');
            }

            $data = $this->commands->create($request);

            return response()->json([
                'kode' => '00',
                'pesan' => 'SUKSES',
                'data' => $data,
            ]);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }
}

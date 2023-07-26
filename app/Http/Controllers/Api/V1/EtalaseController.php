<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Etalase\EtalaseResource;
use App\Http\Services\Etalase\EtalaseCommands;
use App\Http\Services\Etalase\EtalaseQueries;
use App\Http\Services\Product\ProductQueries;
use App\Models\Merchant;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EtalaseController extends Controller
{
    protected $productQueries;

    public function __construct()
    {
        $this->productQueries = new ProductQueries();
    }

    public function index()
    {
        try {
            return EtalaseQueries::getAll(Auth::user()->merchant->id);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function publicEtalase($merchant_id)
    {
        try {
            return EtalaseQueries::getAll($merchant_id);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function publicEtalaseMerchant(Request $request, $merchant_id, $etalase_id)
    {
        $limit = $request->limit ?? 10;
        $filter = $request->filter ?? [];
        $sorting = $request->sortby ?? null;

        try {
            // return ProductQueries::getproductMerchantEtalaseId($merchant_id, $etalase_id, $filter, $sorting, $limit);
            return $this->productQueries->getproductMerchantEtalaseId($merchant_id, $etalase_id, $filter, $sorting, $limit);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function store()
    {
        $validator = Validator::make(
            request()->all(),
            [
                'merchant_id' => 'required',
                'name' => 'required',
            ],
            [
                'required' => ':attribute diperlukan.',
            ]
        );

        request()->request->add([
            'full_name' => Auth::user()->full_name,
        ]);

        try {
            if ($validator->fails()) {
                $errors = collect();
                foreach ($validator->errors()->getMessages() as $key => $value) {
                    foreach ($value as $error) {
                        $errors->push($error);
                    }
                }
                return $this->respondValidationError($errors, 'Validation Error!');
            }

            $record = EtalaseCommands::storeItem(request()->all());

            return $this->respondWithData(new EtalaseResource($record), 'Success saved data');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function update($id)
    {
        try {
            $validator = Validator::make(
                request()->all(),
                [
                    'name' => 'required',
                ],
                [
                    'exists' => 'ID :attribute tidak ditemukan.',
                    'required' => ':attribute diperlukan.',
                    'max' => 'panjang :attribute maksimum :max karakter.',
                    'min' => 'panjang :attribute minimum :min karakter.',
                ]
            );

            if ($validator->fails()) {
                $data = collect();
                foreach ($validator->errors()->getMessages() as $key => $value) {
                    foreach ($value as $error) {
                        $data->push($error);
                    }
                }
                return $this->respondValidationError($data, 'Validation Error!');
            }

            EtalaseCommands::updateItem($id, request()->all());

            return response()->json(['success' => true, 'message' => 'Item Etalase Berhasil Diperbaharui']);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function delete($id)
    {
        try {
            EtalaseCommands::deleteItem($id);

            return response()->json(['success' => true, 'message' => 'Item Etalase Berhasil Dihapus']);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function show($id)
    {
        try {
            return EtalaseQueries::getById($id);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }
}

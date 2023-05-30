<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\ManualTransfer\ManualTransferCommands;
use App\Http\Services\ManualTransfer\ManualTransferQueries;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ManualTransferController extends Controller
{
    protected $commands, $queries;

    public function __construct()
    {
        $this->commands = new ManualTransferCommands();
        $this->queries = new ManualTransferQueries();
    }

    public function create(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'idpel' => 'required',
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

            if (!request()->hasHeader('api_key') || request()->header('api_key') == null) {
                return response()->json([
                    'kode' => '93',
                    'pesan' => 'INVALID USER-PASSWORD',
                ]);
            }

            $data = $this->commands->create($request);

            return response()->json($data);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getToken(Request $request)
    {
        try {
            if (!request()->hasHeader('Authorization')) {
                return response()->json([
                    'kode' => '81',
                    'pesan' => 'INVALID USER-PASSWORD',
                ]);
            }

            $token = hash_hmac('sha256', env('TOKEN_USERNAME'), env('TOKEN_PASSWORD'));

            if (request()->header('Authorization') != $token) {
                return response()->json([
                    'kode' => '81',
                    'pesan' => 'INVALID USER-PASSWORD',
                ]);
            };

            return response()->json([
                'kode' => '00',
                'pesan' => 'Successfull',
                'data' => [
                    'token' => $token,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'kode' => '81',
                'pesan' => $e->getMessage(),
            ]);
        }
    }

    public function getGatheway()
    {
        $gatheways = $this->queries->getGatheway();

        if (empty($gatheways)) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ]);
        }

        $response = [
            'status' => true,
            'message' => 'Data ditemukan',
            'data' => [
                "code" => "marketplace-payment",
                "label" => "Cicilan Tanpa Kartu Kredit",
                "gateways" => array_map(function ($gatheway) {
                    return [
                        "name" => $gatheway['name'],
                        "code" => $gatheway['code'],
                        "icon" => $gatheway['icon'],
                        "debitInfo" => $gatheway['debit_info'],
                        "description" => $gatheway['description'],
                        "descriptionColor" => $gatheway['description_color'],
                        "url" => $gatheway['url'],
                        "isTapable" => $gatheway['is_tapable'],
                    ];
                }, $gatheways->toArray()),
            ],
        ];

        return response()->json($response);
    }

    public function selectGatheway(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required',
            'order_id' => 'required:exists:orders,id',
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

        $gatheway = $this->queries->getGathewayByCode($request->code);

        if (empty($gatheway)) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ]);
        }

        try {
            DB::beginTransaction();

            $this->commands->addLog($gatheway, $request->order_id);

            DB::commit();
            return [
                'status' => true,
                'message' => 'Berhasil memilih gatheway',
            ];
        } catch (\Throwable $th) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'Gagal memilih gatheway',
            ];
        }
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Manager\LogisticManager;
use App\Http\Services\Manager\RajaOngkirManager;
use App\Http\Services\Transaction\TransactionCommands;
use App\Models\CustomerAddress;
use App\Models\MasterData;
use App\Models\Merchant;
use App\Models\Order;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class LogisticController extends Controller
{
    protected $logisticManager, $transactionCommand, $rajaongkirManager;

    public function __construct()
    {
        $this->logisticManager = new LogisticManager();
        $this->transactionCommand = new TransactionCommands();
        $this->rajaongkirManager = new RajaOngkirManager();
    }

    public function searchLocation(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'keyword' => 'required',
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

        try {
            $request = request()->all();
            $params = [
                'keyword' => $request['keyword'],
                'limit' => empty($request['limit']) ? 10 : $request['limit'],
                'page' => empty($request['page']) ? 1 : $request['page'],
            ];
            $locations = $this->logisticManager->searchLocation($params);

            return $this->respondWithData($locations);
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    public function searchLocationByCode(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'kode' => 'min:3',
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

        try {
            $request = request()->all();
            $params = [
                'kode' => $request['kode'],
            ];
            $locations = $this->logisticManager->searchLocationByCode($params);

            return $this->respondWithData($locations);
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    public function getProvince()
    {
        try {
            $provinces = $this->logisticManager->getProvince();

            return $this->respondWithData($provinces);
        } catch (Exception $e) {
            return $this->respondErrorException($e, 'Data tidak ditemukan');
        }
    }

    public function getCity($id)
    {
        try {
            $cities = $this->logisticManager->getCity($id);

            return $this->respondWithData($cities);
        } catch (Exception $e) {
            return $this->respondErrorException($e, 'Data tidak ditemukan');
        }
    }

    public function getDistrict($id)
    {
        try {
            $districts = $this->logisticManager->getDistrict($id);

            return $this->respondWithData($districts);
        } catch (Exception $e) {
            return $this->respondErrorException($e, 'Data tidak ditemukan');
        }
    }

    public function getSubdistrict($id)
    {
        try {
            $subdistricts = $this->logisticManager->getSubdistrict($id);

            return $this->respondWithData($subdistricts);
        } catch (Exception $e) {
            return $this->respondErrorException($e, 'Data tidak ditemukan');
        }
    }

    public function ongkirLogistic(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'customer_address_id' => 'required',
            'merchant_id' => 'required',
            'weight' => 'required|numeric',
            'price' => 'required|numeric',
            'courier' => 'string',
            'type_service' => 'string',
        ], [
            'required' => ':attribute diperlukan.',
            'numeric' => ':attribute harus berupa angka.',
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

        $request = request()->all();
        $merchant = Merchant::with('expedition')->where('id', $request['merchant_id'])->first();
        $customer_address = CustomerAddress::where('id', $request['customer_address_id'])->first();
        $setting_courirers = MasterData::where('key', 's_courier')->get();

        try {
            $s_courier = '';
            foreach ($setting_courirers as $courier) {
                foreach (explode(':', $merchant->expedition->list_expeditions) as $value) {
                    if ($value == $courier->reference_third_party_id) {
                        $s_courier .= $value . ':';
                    }
                }
            }
            $ongkir = $merchant->expedition == null ? [] : $this->logisticManager->getOngkir($customer_address, $merchant, $request['weight'], rtrim($s_courier, ':'), $request['price']);

            return $this->respondWithData($ongkir);
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    public function track($order_id)
    {
        try {
            $tracking_data = $this->logisticManager->track($order_id);

            if (isset($tracking_data['status'])) {
                $status_code = $tracking_data['status'];
            } else if (isset($tracking_data['status_code'])) {
                $status_code = $tracking_data['status_code'];
            } else {
                $status_code = 200;
            }

            return $this->respondCustom([
                'status' => $status_code,
                'message' => isset($tracking_data['message']) ? $tracking_data['message'] : '',
                'success' => isset($tracking_data['success']) ? $tracking_data['success'] : true,
                'data' => isset($tracking_data['data']) ? $tracking_data['data'] : null,
            ]);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function updateAwb(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'trx_no' => 'required',
            'awb_number' => 'required',
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

        try {
            $trx_no = $request['trx_no'];
            $awb_number = $request['awb_number'];

            $update_awb = $this->logisticManager->updateAwb($trx_no, $awb_number);

            return response()->json($update_awb);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function webhook(Request $request)
    {
        try {
            $trx_no = $request->get('partner_no_trx');
            $awb_number = $request->get('awb_number');
            $no_reference = $request->get('no_reference');
            $courier_image = $request->get('courier_image');

            $this->transactionCommand->updateAwb($trx_no, $awb_number, $no_reference, $courier_image);

            $response = [
                'success' => true,
                'message' => 'Success',
                'data' => [
                    'trx_no' => $trx_no,
                    'awb_number' => $awb_number,
                ]
            ];

            return response()->json($response);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getOngkir(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'customer_address_id' => 'required',
            'merchant_id' => 'required',
            'weight' => 'required|numeric',
            'price' => 'required|numeric',
        ], [
            'required' => ':attribute diperlukan.',
            'numeric' => ':attribute harus berupa angka.',
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

        $request = request()->all();
        $merchant = Merchant::with(['expedition', 'district'])->where('id', $request['merchant_id'])->first();
        $customer_address = CustomerAddress::with(['district'])->where('id', $request['customer_address_id'])->first();

        $master_data = MasterData::whereIn('key', ['is_active_shipper', 'prefix_shipper', 'is_active_rajaongkir_cache'])->get();
        $setting_shipper = collect($master_data)->where('key', 'is_active_shipper')->first();
        $prefix_shipper = collect($master_data)->where('key', 'prefix_shipper')->first();
        $rajaongkir_cache_setting = collect($master_data)->where('key', 'is_active_rajaongkir_cache')->first();

        $setting_courirers = Cache::remember('setting_courirers', 60 * 60, function () {
            return MasterData::where('type', 'rajaongkir_courier')->get();
        });

        try {
            $ongkir = [];
            $logistic = [];
            if ($setting_shipper->value == 'active') {
                $s_courier = '';
                foreach (collect($setting_courirers)->where('key', 's_courier') as $courier) {
                    foreach (explode(':', $merchant->expedition->list_expeditions) as $value) {
                        if ($value == $courier->reference_third_party_id) {
                            $s_courier .= $value . ':';
                        }
                    }
                }

                if ($merchant->subdistrict_id == null || $customer_address->subdistrict_id == null) {
                    return $this->respondWithData([], 'Mohon maaf, alamat toko belum lengkap.', 200);
                }

                $logistic = $merchant->expedition == null ? [] : $this->logisticManager->getOngkir($customer_address, $merchant, $request['weight'], rtrim($s_courier, ':'), $request['price']);

                foreach (collect($logistic) as $value) {
                    $data = [];
                    foreach ($value['data'] as $data_value) {
                        $data[] = [
                            'service_code' => (string) $data_value['service_code'],
                            'service_name' => $data_value['service_name'] . ' ' . ($prefix_shipper == null ? 'Pick Up' : $prefix_shipper->value),
                            'estimate_day' => $data_value['estimate_day'],
                            'price' => $data_value['final_price'],
                            'min_weight' => $data_value['min_weight'],
                            'max_weight' => $data_value['max_weight'],
                            'delivery_discount' => 0,
                            'delivery_setting' => 'shipper',
                            'must_use_insurance' => $data_value['must_use_insurance'],
                        ];
                    }

                    $ongkir[] = [
                        'code' => $value['code'],
                        'name' => $value['name'],
                        'image' => $value['image'],
                        'data' => $data,
                    ];
                }
            }

            $ro_courier = '';
            foreach (collect($setting_courirers)->where('key', 'ro_courier') as $courier) {
                foreach (explode(':', $merchant->expedition->list_expeditions) as $value) {
                    if ($value == $courier->reference_third_party_id) {
                        $ro_courier .= $value . ':';
                    }
                }
            }

            $rajaongkir = $merchant->expedition == null ? [] : $this->rajaongkirManager->getOngkirSameLogistic($customer_address, $merchant, $request['weight'], rtrim($ro_courier, ':'), $rajaongkir_cache_setting->value);

            foreach ($rajaongkir as $rjo) {
                $key = array_search($rjo['code'], array_column($ongkir, 'code'));
                if ($key !== false) {
                    $data = [];
                    foreach ($rjo['data'] as $data_value) {
                        $data[] = [
                            'service_code' => $data_value['service_name'],
                            'service_name' => $data_value['service_name'],
                            'estimate_day' => $data_value['estimate_day'],
                            'price' => $data_value['price'],
                            'min_weight' => $data_value['min_weight'],
                            'max_weight' => $data_value['max_weight'],
                            'delivery_discount' => 0,
                            'delivery_setting' => 'rajaongkir',
                            'must_use_insurance' => false,
                        ];
                    }

                    $ongkir[$key]['data'] = array_merge($ongkir[$key]['data'], $data);
                } else {
                    $data = [];
                    foreach ($rjo['data'] as $data_value) {
                        $data[] = [
                            'service_code' => $data_value['service_name'],
                            'service_name' => $data_value['service_name'],
                            'estimate_day' => $data_value['estimate_day'],
                            'price' => $data_value['price'],
                            'min_weight' => $data_value['min_weight'],
                            'max_weight' => $data_value['max_weight'],
                            'delivery_discount' => 0,
                            'delivery_setting' => 'rajaongkir',
                            'must_use_insurance' => false,
                        ];
                    }

                    $ongkir[] = [
                        'code' => $rjo['code'],
                        'name' => $rjo['name'],
                        'image' => $rjo['image'],
                        'data' => $data,
                    ];
                }
            }

            return $this->respondWithData($ongkir);
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    public function trackOrder($order_id)
    {
        try {
            $order = Order::with(['delivery', 'merchant'])->where('id', $order_id)->first();
            if (!$order) {
                throw new Exception("Nomor invoice tidak ditemukan", 404);
            }

            if ($order->delivery->delivery_setting == 'shipper') {
                $tracking_data = $this->logisticManager->track($order);

                if (isset($tracking_data['status'])) {
                    $status_code = $tracking_data['status'];
                } else if (isset($tracking_data['status_code'])) {
                    $status_code = $tracking_data['status_code'];
                } else {
                    $status_code = 200;
                }

                return $this->respondCustom([
                    'status' => $status_code,
                    'message' => isset($tracking_data['message']) ? $tracking_data['message'] : '',
                    'success' => isset($tracking_data['success']) ? $tracking_data['success'] : true,
                    'data' => isset($tracking_data['data']) ? $tracking_data['data'] : null,
                ]);
            } else {
                $tracking_data = $this->rajaongkirManager->trackOrderSameLogistic($order);

                return $this->respondCustom([
                    'status' => 200,
                    'message' => 'Data berhasil didapatkan!',
                    'success' => true,
                    'data' => $tracking_data,
                ]);
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function trackScheduller($order_id)
    {
        $timestamp = request()->header('timestamp');
        $signature = request()->header('signature');

        if ($timestamp == null || $signature == null) {
            return $this->respondWithResult(false, 'Timestamp dan Signature diperlukan.', 400);
        }

        $timestamp_plus = \Carbon\Carbon::now('Asia/Jakarta')->addMinutes(5)->toIso8601String();
        if (strtotime($timestamp) > strtotime($timestamp_plus)) return $this->respondWithResult(false, 'Timestamp tidak valid.', 400);

        $boromir_key = env('BOROMIR_AUTH_KEY', 'boromir');
        $hash = hash_hmac('sha256', 'bot-' . $timestamp, $boromir_key);
        if ($hash != $signature) return $this->respondWithResult(false, 'Signature tidak valid.', 400);

        try {
            $order = Order::with(['delivery', 'merchant'])->where('id', $order_id)->first();
            if (!$order) {
                throw new Exception("Nomor invoice tidak ditemukan", 404);
            }

            if ($order->delivery->delivery_setting == 'shipper') {
                $tracking_data = $this->logisticManager->track($order);

                if (isset($tracking_data['status'])) {
                    $status_code = $tracking_data['status'];
                } else if (isset($tracking_data['status_code'])) {
                    $status_code = $tracking_data['status_code'];
                } else {
                    $status_code = 200;
                }

                return $this->respondCustom([
                    'status' => $status_code,
                    'message' => isset($tracking_data['message']) ? $tracking_data['message'] : '',
                    'success' => isset($tracking_data['success']) ? $tracking_data['success'] : true,
                    'data' => isset($tracking_data['data']) ? $tracking_data['data'] : null,
                ]);
            } else {
                $tracking_data = $this->rajaongkirManager->trackOrderSameLogistic($order);

                return $this->respondCustom([
                    'status' => 200,
                    'message' => 'Data berhasil didapatkan!',
                    'success' => true,
                    'data' => $tracking_data,
                ]);
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }
}

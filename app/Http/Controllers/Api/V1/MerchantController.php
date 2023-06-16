<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Merchant\MerchantCommands;
use App\Http\Services\Merchant\MerchantQueries;
use App\Models\Merchant;
// use App\Models\Merchant;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MerchantController extends Controller
{
    protected $merchantQueries, $merchantCommands;

    public function __construct()
    {
        $this->merchantQueries = new MerchantQueries();
        $this->merchantCommands = new MerchantCommands();
    }

    public function aturToko()
    {
        $validator = Validator::make(request()->all(), [
            'slogan' => 'required',
            'description' => 'required',
            'operational' => 'required',
            'is_npwp_required' => 'required|boolean'
        ], [
            'required' => ':attribute diperlukan.',
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
            };

            return MerchantCommands::aturToko(request()->all(), Auth::user()->merchant_id);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function aturTokoAgent()
    {
        $validator = Validator::make(request()->all(), [
            'slogan' => 'required',
            'description' => 'required',
            'email' => 'required|email',
            'latitude' => 'required',
            'longitude' => 'required',
        ], [
            'required' => ':attribute diperlukan.',
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
            };

            return MerchantCommands::aturTokoAgent(request()->all(), Auth::user()->merchant_id);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function homepageProfile()
    {
        $request = request()->all();

        try {
            $daterange = [];

            // $filter_days = [
            //     '1' => [Carbon::now()->toDateString() . ' 00:00:00', Carbon::now()->toDateString() . ' 23:59:59'], // Today
            //     '7' => [Carbon::now()->subDays(7)->toDateString() . ' 00:00:00', Carbon::now()->toDateString() . ' 23:59:59'], // Last 7 days
            //     '30' => [Carbon::now()->subDays(30)->toDateString() . ' 00:00:00', Carbon::now()->toDateString() . ' 23:59:59'], // Last 30 days
            // ];

            // if (isset($request['filter_day'])) {
            //     $filter = $request['filter_day'];
            //     if (isset($filter_days[$filter])) {
            //         $daterange = $filter_days[$filter];
            //     } elseif (isset($request['from']) && isset($request['to'])) {
            //         $daterange = [$request['from'] . ' 00:00:00', $request['to'] . ' 23:59:59'];
            //     }
            // } elseif (isset($request['from']) && isset($request['to'])) {
            //     $daterange = [$request['from'] . ' 00:00:00', $request['to'] . ' 23:59:59'];
            // }

            if (isset($request['from']) && isset($request['to'])) {
                $from = Carbon::parse($request['from'] . ' 00:00:00');
                $to = Carbon::parse($request['to'] . ' 23:59:59');
                $difference = ceil((strtotime($request['to'] . ' 23:59:59') - strtotime($request['from'] . ' 00:00:00')) / 60 / 60 / 24);

                $before_from = Carbon::parse($from)->subDays($difference)->toDateTimeString();
                $before_to = Carbon::parse($request['from'] . ' 23:59:59')->subDays(1)->toDateTimeString();
                $daterange = [
                    'daterange' => [$from->toDateTimeString(), $to->toDateTimeString()],
                    'before' => [$before_from, $before_to],
                ];
            } else {
                $from = Carbon::now()->timezone('Asia/Jakarta')->subWeek()->addDay();
                $to = Carbon::now()->timezone('Asia/Jakarta');
                $difference = ceil((strtotime($to) - strtotime($from)) / 60 / 60 / 24);

                $before_from = Carbon::parse($from)->subDays($difference)->format('Y-m-d 00:00:00');
                $before_to = Carbon::parse($from->format('Y-m-d') . ' 23:59:59')->subDays(1)->toDateTimeString();

                $daterange = [
                    'daterange' => [$from->format('Y-m-d 00:00:00'), $to->toDateTimeString()],
                    'before' => [$before_from, $before_to],
                ];
            }

            return MerchantQueries::homepageProfile(Auth::user()->merchant_id, $daterange);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function publicProfile($merchant_id)
    {
        try {
            $merchant = MerchantQueries::publicProfile($merchant_id);
            return $this->respondWithData($merchant, 'Berhasil mendapatkan data toko');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function publicProfileV2($merchant_id)
    {
        try {
            $merchant = MerchantQueries::publicProfileV2($merchant_id);
            return $this->respondWithData($merchant, 'Berhasil mendapatkan data toko');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getOfficialMerchant($category_key)
    {
        try {
            $data = $this->merchantQueries->getOfficialMerchant($category_key);
            return $this->respondWithData($data['data'], $data['message']);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getOfficialMerchantBySubCategory($category_key, $sub_category_key)
    {
        try {
            $data = $this->merchantQueries->getOfficialMerchantBySubCategory($category_key, $sub_category_key);
            return $this->respondWithData($data['data'], $data['message']);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function activity()
    {
        Log::info("T00001", [
            'path_url' => "start.activity",
            'query' => [],
            'body' => Carbon::now('Asia/Jakarta'),
            'response' => 'Start',
        ]);
        $request = request()->all();

        try {
            $daterange = [];
            if (isset($request['from']) && isset($request['to'])) {
                $from = Carbon::parse($request['from'] . ' 00:00:00');
                $to = Carbon::parse($request['to'] . ' 23:59:59');
                $daterange = [$from->toDateTimeString(), $to->toDateTimeString()];
            }

            return MerchantQueries::getActivity(Auth::user()->merchant_id, $daterange);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function setExpedition()
    {
        $validator = Validator::make(request()->all(), [
            'list_expeditions' => 'required',
        ], [
            'required' => 'Minimal harus pilih 1 expedisi.',
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
            MerchantCommands::createOrUpdateExpedition(request()->get('list_expeditions'));
            return $this->respondWithData(Merchant::with('expedition')->where('id', Auth::user()->merchant->id)->firstOrFail(), 'Layanan ekspedisi berhasil disimpan');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function aturLokasi(Request $request)
    {
        $validator = Validator::make(
            request()->all(),
            [
                'address' => 'required|min:3',
                'province_id' => 'required|exists:province,id',
                'city_id' => 'required|exists:city,id',
                'district_id' => 'required|exists:district,id',
                'postal_code' => 'required|max:5',
                'longitude' => 'nullable',
                'latitude' => 'nullable',
            ],
            [
                'exists' => 'ID :attribute tidak ditemukan.',
                'required' => ':attribute diperlukan.',
                'max' => 'panjang :attribute maksimum :max karakter.',
                'min' => 'panjang :attribute minimum :min karakter.',
            ]
        );

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
            request()->request->add([
                'full_name' => Auth::user()->full_name,
            ]);
            $data = MerchantCommands::updateLokasi($request, Auth::user()->merchant_id);
            return $this->respondWithData($data, 'Data lokasi berhasil disimpan');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getOfficialStore(Request $request)
    {
        try {
            $limit = $request->limit ?? 10;
            $page = $request->page ?? 1;

            $data = $this->merchantQueries->getOfficialStore($limit, $page);

            return $this->respondWithData($data, 'Berhasil mendapatkan data toko');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function searchOfficialStoreByName(Request $request)
    {
        try {
            $limit = $request->limit ?? 10;
            $page = $request->page ?? 1;
            $name = $request->name ?? '';

            $data = $this->merchantQueries->searchOfficialStoreByName($name, $limit, $page);

            return $this->respondWithData($data, 'Berhasil mendapatkan data toko');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function requestMerchantList(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'key' => 'required',
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
            // return \Illuminate\Support\Str::random(32);
            $key = "mbfxuavEyTjtfOGNR2bwrVlkgRnBsqUO";

            if ($request->key != $key) {
                return $this->respondValidationError(['key' => 'Your key is invalid'], 'Validation Error!');
            }

            $limit = $request->limit ?? 10;
            $page = $request->page ?? 1;

            $data = MerchantQueries::getListMerchant($limit, $page);
            return $this->respondWithData($data, 'Berhasi mendapatkan data ist merchant');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function updateMerchantProfile(Request $request)
    {
        try {
            if (Auth::check()) {
                $merchantCommand = new MerchantCommands();
                return $merchantCommand->updateMerchantProfile(Auth::user()->merchant_id, $request);
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function setCustomLogistic()
    {
        try {
            if (Auth::check()) {
                $merchantCommand = new MerchantCommands();
                return $merchantCommand->setCustomLogistic(Auth::user()->merchant_id);
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getBanner()
    {
        try {
            if (Auth::check()) {
                $merchantQueries = new MerchantQueries();
                return $merchantQueries->getBanner(Auth::user()->merchant_id);
            }
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function createBanner(Request $request)
    {
        $validator = Validator::make(
            request()->all(),
            ['url' => 'required'],
            ['required' => ':attribute diperlukan.']
        );

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
            request()->request->add([
                'full_name' => Auth::user()->full_name,
            ]);
            $merchantCommand = new MerchantCommands();
            $data = $merchantCommand->createBanner($request, Auth::user()->merchant_id);
            return $this->respondWithData($data, 'Data lokasi berhasil disimpan');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function deleteBanner($banner_id)
    {
        try {
            $merchantCommand = new MerchantCommands();
            $data = $merchantCommand->deleteBanner($banner_id, Auth::user()->merchant_id);
            return $this->respondWithData($data, 'Banner berhasil dihapus');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }
}

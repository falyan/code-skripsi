<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Merchant\Agent\AgentCommands;
use App\Http\Services\Merchant\Agent\AgentQueries;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MerchantAgentController extends Controller
{
    protected $merchantQueries, $merchantCommands;
    protected $agentQueries, $agentCommands;

    public function __construct()
    {
        // $this->merchantQueries = new MerchantQueries();
        // $this->merchantCommands = new MerchantCommands();
        $this->agentQueries = new AgentQueries();
        $this->agentCommands = new AgentCommands();
    }

    public function getMenu(Request $request)
    {
        try {
            $menus = $this->agentQueries->getMenu();

            return $this->respondWithData($menus, 'success');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getDetailMenu($agent_id, Request $request)
    {
        try {
            $with_product_groups = $request->with_product_groups == '1';
            $menu = $this->agentQueries->getDetailMenu($agent_id, $with_product_groups);

            return $this->respondWithData($menu, 'success');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function setMarginDefault(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'agent_menu_id' => ['required', Rule::exists('agent_menu', 'id')->whereNull('deleted_at')],
                'margin' => 'required|numeric|max:2500',
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
            };

            $merchant_agent = $this->agentCommands->setMargin($request);

            return $this->respondWithData($merchant_agent, 'success');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    // ========= ICONPAY V3 API ==========

    public function getInfoTagihanPostpaidV3(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'idpel' => 'required',
            ]);

            if ($validator->fails()) {
                $errors = collect();
                foreach ($validator->errors()->getMessages() as $key => $value) {
                    foreach ($value as $error) {
                        $errors->push($error);
                    }
                }
                return $this->respondValidationError($errors, 'Validation Error!');
            };

            $response = $this->agentCommands->getInfoTagihanPostpaidV3($request);

            if (isset($response['response_code']) && $response['response_code'] == '0000') {
                return $this->respondCustom([
                    'message' => isset($response['response_message']) ? $response['response_message'] : 'success',
                    'response_code' => isset($response['response_code']) ? $response['response_code'] : '',
                    'data' => $response['transaction_detail'],
                ]);
            }

            return $response;
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    public function getInquiryPostpaidV3(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "idpel" => "required",
                "margin" => "required|numeric",
                "data.customer_id" => "required",
                "data.customer_name" => "required",
            ]);

            if ($validator->fails()) {
                $errors = collect();
                foreach ($validator->errors()->getMessages() as $key => $value) {
                    foreach ($value as $error) {
                        $errors->push($error);
                    }
                }
                return $this->respondValidationError($errors, 'Validation Error!');
            };

            $response = $this->agentCommands->getInquiryPostpaidV3($request);

            return $response;
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    public function getInfoTagihanPrepaidV3(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "idpel" => "required",
            ]);

            if ($validator->fails()) {
                $errors = collect();
                foreach ($validator->errors()->getMessages() as $key => $value) {
                    foreach ($value as $error) {
                        $errors->push($error);
                    }
                }
                return $this->respondValidationError($errors, 'Validation Error!');
            };

            $response = $this->agentCommands->getInfoTagihanPrepaidV3($request);

            if (isset($response['response_code']) && $response['response_code'] == '0000') {
                return $this->respondCustom([
                    'message' => isset($response['response_message']) ? $response['response_message'] : 'success',
                    'response_code' => isset($response['response_code']) ? $response['response_code'] : '',
                    'data' => $response['transaction_detail'],
                ]);
            }

            return $response;
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    public function getInfoManualAdviceV3(Request $request)
    {
        try {
            $page = $request->page ?? 1;
            $limit = $request->limit ?? 10;

            $validator = Validator::make($request->all(), [
                "idpel" => "required",
            ]);

            if ($validator->fails()) {
                $errors = collect();
                foreach ($validator->errors()->getMessages() as $key => $value) {
                    foreach ($value as $error) {
                        $errors->push($error);
                    }
                }
                return $this->respondValidationError($errors, 'Validation Error!');
            };

            $response = $this->agentCommands->getInfoManualAdviceV3($request, $page, $limit);

            return $response;
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    public function getInquiryPrepaidV3(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "idpel" => "required",
                "margin" => "required|numeric",
                "data.customer_id" => "required",
                "data.customer_name" => "required",
            ]);

            if ($validator->fails()) {
                $errors = collect();
                foreach ($validator->errors()->getMessages() as $key => $value) {
                    foreach ($value as $error) {
                        $errors->push($error);
                    }
                }
                return $this->respondValidationError($errors, 'Validation Error!');
            };

            $response = $this->agentCommands->getInquiryPrepaidV3($request);

            return $response;
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    public function confirmOrderIconcash(Request $request)
    {
        // try {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required',
            'client_ref' => 'required',
            'source_account_id' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = collect();
            foreach ($validator->errors()->getMessages() as $key => $value) {
                foreach ($value as $error) {
                    $errors->push($error);
                }
            }
            return $this->respondValidationError($errors, 'Validation Error!');
        };

        $response = $this->agentCommands->confirmOrderIconcash($request['transaction_id'], Auth::user()->iconcash->token, $request['client_ref'], $request['source_account_id']);

        return $response;
        // } catch (Exception $e) {
        //     return $this->respondErrorException($e, $request);
        // }
    }

    public function getOrder(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'transaction_id' => 'required',
            ]);

            if ($validator->fails()) {
                $errors = collect();
                foreach ($validator->errors()->getMessages() as $key => $value) {
                    foreach ($value as $error) {
                        $errors->push($error);
                    }
                }
                return $this->respondValidationError($errors, 'Validation Error!');
            };

            $response = $this->agentQueries->getOrder($request->transaction_id);

            return $this->respondWithData($response, 'success');
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    public function downloadAgentReceipt(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'trx_no' => 'required',
            ]);

            if ($validator->fails()) {
                $errors = collect();
                foreach ($validator->errors()->getMessages() as $key => $value) {
                    foreach ($value as $error) {
                        $errors->push($error);
                    }
                }
                return $this->respondValidationError($errors, 'Validation Error!');
            };

            return $this->agentCommands->downloadAgentReceipt($request->trx_no);
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    public function getTransaction(Request $request)
    {
        try {
            $limit = $request->limit ?? 10;
            $page = $request->page ?? 1;

            $response = $this->agentQueries->getTransaction($limit, $page);

            return $this->respondWithData($response, 'success');
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    public function getTransactionDetail($order_id)
    {
        try {
            $response = $this->agentQueries->getDetailTransaction($order_id);

            return $this->respondWithData($response, 'success');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getTransactionWithFilter(Request $request)
    {
        try {
            $limit = $request->limit ?? 10;
            $page = $request->page ?? 1;
            $keyword = $request->keyword ?? null;
            $filter = $request->filter ?? null;
            $sortby = $request->sortby ?? null;

            $response = $this->agentQueries->getTransactionWithFilter($limit, $page, $keyword, $filter, $sortby);

            return $this->respondWithData($response, 'success');
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    public function manualAdvicePrepaid(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required',
            'client_ref' => 'required',
            'source_account_id' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = collect();
            foreach ($validator->errors()->getMessages() as $key => $value) {
                foreach ($value as $error) {
                    $errors->push($error);
                }
            }
            return $this->respondValidationError($errors, 'Validation Error!');
        };

        $iconcash = Auth::user()->iconcash;

        $response = $this->agentCommands->manualAdvicePrepaid($request, $iconcash->token);

        return $response;
    }

    // public function manualReversalPostpaid(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'transaction_id' => 'required',
    //     ]);

    //     if ($validator->fails()) {
    //         $errors = collect();
    //         foreach ($validator->errors()->getMessages() as $key => $value) {
    //             foreach ($value as $error) {
    //                 $errors->push($error);
    //             }
    //         }
    //         return $this->respondValidationError($errors, 'Validation Error!');
    //     };

    //     $response = $this->agentCommands->manualReversalPostpaid($request);

    //     return $response;
    // }
}

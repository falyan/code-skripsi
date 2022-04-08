<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Discussion\DiscussionCommands;
use App\Http\Services\Discussion\DiscussionQueries;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DiscussionController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $discussionCommands, $discussionQueries;
    public function __construct()
    {
        $this->discussionCommands = new DiscussionCommands();
        $this->discussionQueries = new DiscussionQueries();
    }

    #buyer region
    public function createDiscussionMaster(Request $request){
        $request['customer_id'] = Auth::id();
        $request['customer_name'] = Auth::user()->full_name;

        try {
            $rules = [
                'product_id' => 'required|exists:product,id',
                'merchant_id' => 'sometimes|exists:merchant,id',
                'message' => 'required'
            ];

            $validator = Validator::make($request->all(), $rules, [
                'required' => ':attribute diperlukan.',
                'exists' => ':attribute tidak ditemukan'
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

            return $this->discussionCommands->createDiscussionMaster($request);
        }catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    public function createDiscussionResponseByBuyer(Request $request){
        $request['customer_id'] = Auth::id();
        $request['name'] = Auth::user()->full_name;

        try {
            $rules = [
                'master_discussion_id' => 'required|exists:product_discussion_master,id',
                'message' => 'required'
            ];

            $validator = Validator::make($request->all(), $rules, [
                'required' => ':attribute diperlukan.',
                'exists' => ':attribute tidak ditemukan'
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

            return $this->discussionCommands->createDiscussionResponse($request);
        }catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    public function getListAllDiscussionByBuyer(Request $request){
        $customer_id = Auth::id();
        try {
            $rules = [
                'limit' => 'sometimes',
                'page' => 'sometimes'
            ];

            $validator = Validator::make($request->all(), $rules, [
                'required' => ':attribute diperlukan.'
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

            return $this->discussionQueries->getListDiscussion($customer_id, null, null, $request['limit'], $request['page']);
        }catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    public function getListUnreadDiscussionByBuyer(Request $request){
        $customer_id = Auth::id();
        try {
            $rules = [
                'limit' => 'sometimes',
                'page' => 'sometimes'
            ];

            $validator = Validator::make($request->all(), $rules, [
                'required' => ':attribute diperlukan.'
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

            return $this->discussionQueries->getListDiscussion($customer_id, null, 'unread', $request['limit'], $request['page']);
        }catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    public function getListReadDiscussionByBuyer(Request $request){
        $customer_id = Auth::id();
        try {
            $rules = [
                'limit' => 'sometimes',
                'page' => 'sometimes'
            ];

            $validator = Validator::make($request->all(), $rules, [
                'required' => ':attribute diperlukan.'
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

            return $this->discussionQueries->getListDiscussion($customer_id, null, 'read', $request['limit'], $request['page']);
        }catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    public function getListDiscussionByProduct(Request $request){
        try {
            $rules = [
                'product_id' => 'required|exists:product,id',
                'limit' => 'sometimes',
                'page' => 'sometimes'
            ];

            $validator = Validator::make($request->all(), $rules, [
                'required' => ':attribute diperlukan.',
                'exists' => ':attribute tidak ditemukan'
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

            return $this->discussionQueries->getListDiscussionByProduct($request['product_id'], $request['limit'], $request['page']);
        }catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    #seller region
    public function replyBuyerDiscussion(Request $request)
    {
        try {
            $user = Auth::user();
            $data = $request->all();

            $rules = [
                'master_discussion_id' => 'required|exists:product_discussion_master,id',
                'customer_id' => 'required|exists:customer,id',
                'message' => 'required',
            ];

            $validator = Validator::make($data, $rules, [
                'required' => ':attribute diperlukan.',
                'exists' => ':attribute tidak ditemukan'
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

            return $this->discussionCommands->sellerReplyDiscussion($user, $data);
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    public function getListAllDiscussionBySeller(Request $request)
    {
        $merchantId = Auth::user()->merchant->id;
        try {
            $rules = [
                'limit' => 'sometimes',
                'page' => 'sometimes'
            ];

            $validator = Validator::make($request->all(), $rules, [
                'required' => ':attribute diperlukan.'
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

            return $this->discussionQueries->getListDiscussionSeller($merchantId, null, $request['limit'], $request['page']);
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    public function getListUnreadDiscussionBySeller(Request $request)
    {
        $merchantId = Auth::user()->merchant->id;
        try {
            $rules = [
                'limit' => 'sometimes',
                'page' => 'sometimes'
            ];

            $validator = Validator::make($request->all(), $rules, [
                'required' => ':attribute diperlukan.'
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

            return $this->discussionQueries->getListDiscussionSeller($merchantId, 'unread', $request['limit'], $request['page']);
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    public function getListReadDiscussionBySeller(Request $request)
    {
        $merchantId = Auth::user()->merchant->id;
        try {
            $rules = [
                'limit' => 'sometimes',
                'page' => 'sometimes'
            ];

            $validator = Validator::make($request->all(), $rules, [
                'required' => ':attribute diperlukan.'
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

            return $this->discussionQueries->getListDiscussionSeller($merchantId, 'read', $request['limit'], $request['page']);
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }

    public function getDiscussionByMasterId($id){
        try {
            return $this->discussionQueries->getDiscussionByMasterId($id);
        } catch (Exception $e) {
            return $this->respondErrorException($e, $id);
        }
    }

    public function buyerReadDiscussion($id){
        try {
            return $this->discussionCommands->buyerReadDiscussion($id);
        } catch (Exception $e) {
            return $this->respondErrorException($e, $id);
        }
    }
}

<?php

namespace App\Http\Services\Discussion;

use App\Http\Services\Service;
use App\Models\DiscussionMaster;
use App\Models\DiscussionResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class DiscussionCommands extends Service
{
    public function createDiscussionMaster($data)
    {
        $discussion = DiscussionMaster::create([
            'product_id' => $data['product_id'],
            'customer_id' => $data['customer_id'] ?? null,
            'merchant_id' => $data['merchant_id'] ?? null,
            'message' => $data['message'] ?? null,
            'created_by' => $data['customer_name'] ?? 'customer',
            'updated_by' => $data['customer_name'] ?? 'customer',
            'is_read_merchant' => false
        ]);

        if (!$discussion) {
            $response['success'] = false;
            $response['message'] = 'Gagal menyimpan diskusi produk';
            $response['data'] = $discussion;
            return $response;
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil menyimpan diskusi produk';
        $response['data'] = $discussion;
        return $response;
    }

    public function createDiscussionResponse($data)
    {
        $discussion = DiscussionResponse::create([
            'master_discussion_id' => $data['master_discussion_id'],
            'customer_id' => $data['customer_id'] ?? null,
            'merchant_id' => $data['merchant_id'] ?? null,
            'message' => $data['message'] ?? null,
            'created_by' => $data['name'] ?? 'user',
            'updated_by' => $data['name'] ?? 'user',
            'is_read_customer' => false
        ]);

        if (!$discussion) {
            $response['success'] = false;
            $response['message'] = 'Gagal menyimpan diskusi produk';
            $response['data'] = $discussion;
            return $response;
        }

        $response['success'] = true;
        $response['message'] = 'Berhasil menyimpan diskusi produk';
        $response['data'] = $discussion;
        return $response;
    }

    #seller region
    public function sellerReplyDiscussion($user, array $data)
    {
        DB::beginTransaction();
        try {
            $discussionMaster = DiscussionMaster::where('id', $data['master_discussion_id'])->first();

            if (!$discussionMaster) {
                return response()->json([
                    'success' => false,
                    'message' => 'Master diskusi Produk tidak ditemukan.'
                ], 404);
            } else {
                if ($discussionMaster->merchant_id != $user->merchant->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda bukan seller yang tepat pada diskusi ini.'
                    ], 400);
                }

                $reply = DiscussionResponse::create([
                    'master_discussion_id' => $data['master_discussion_id'],
                    'customer_id' => $data['customer_id'] ?? null,
                    'merchant_id' => $user->merchant->id,
                    'message' => $data['message'],
                    'created_by' => $user->full_name ?? 'seller',
                    'updated_by' =>  $user->full_name ?? 'seller',
                    'is_read_customer' => false
                ]);

                if (!$reply) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Gagal menyimpan balasan diskusi produk',
                        'data' => null
                    ], 400);
                }

                $discussionMaster->update(['is_read_merchant' => true]);

                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Berhasil menyimpan balasan diskusi produk',
                    'data' => [
                        'reply' => $reply,
                        'discussion_master' => $discussionMaster
                    ]
                ], 200);
            }
        } catch (Exception $th) {
            DB::rollBack();
            if (in_array($th->getCode(), self::$error_codes)) {
                throw new Exception($th->getMessage(), $th->getCode());
            }
            throw new Exception($th->getMessage(), 500);
        }
    }

    public function buyerReadDiscussion($id){
        try {
            DB::beginTransaction();
            $master = DiscussionMaster::with(['discussion_response'])->where('id', $id)->first();

            if (empty($master)){
                $response['success'] = false;
                $response['message'] = 'Data diskusi dengan id '. $id . ' tidak ditemukan';

                return $response;
            }

            foreach ($master->discussion_response as $resp){
                $data = DiscussionResponse::find($resp->id);
                $data->is_read_customer = true;
                $data->save();
            }
            DB::commit();

            $response['success'] = true;
            $response['message'] = 'Berhasil merubah status diskusi';

            return $response;

        }catch (Exception $e){
            DB::rollBack();
            if (in_array($e->getCode(), self::$error_codes)) {
                throw new Exception($e->getMessage(), $e->getCode());
            }
            throw new Exception($e->getMessage(), 500);
        }
    }
}

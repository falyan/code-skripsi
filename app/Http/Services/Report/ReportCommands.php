<?php

namespace App\Http\Services\Report;

use App\Http\Services\Service;
use App\Models\DiscussionMaster;
use App\Models\DiscussionResponse;
use App\Models\Report;
use App\Models\Review;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportCommands extends Service
{
    public function createReport($data)
    {
        try {
            DB::beginTransaction();

            // $discussion = DiscussionMaster::with(['discussion_response' => function ($query) {
            //     $query->where('status', 1);
            // }])->where('product_id', $product_id)
            //     ->where('status', 1)
            //     ->whereHas('discussion_response', function ($query) use ($data) {
            //         $query->where('master_discussion_id', $data->discussion_response_id);
            //     })->first();

            $report = Report::create([
                'product_id' => $data->product_id,
                'review_id' => $data->review_id,
                'product_discussion_master_id' => $data->product_discussion_master_id,
                'product_discussion_response_id' => $data->product_discussion_response_id,
                'reported_by' => Auth::user()->id,
                'reason' => $data->reason,
                'description' => $data->description ?? null,
            ]);

            if (isset($data->review_id) && !empty($data->review_id)) {
                $review = Review::find($data->review_id);
                $review->status = 0;
                $review->save();
            }

            if (isset($data->product_discussion_master_id) && !empty($data->product_discussion_master_id)) {
                $discussion = DiscussionMaster::find($data->product_discussion_master_id);
                $discussion->status = 0;
                $discussion->save();
            }

            if (isset($data->product_discussion_response_id) && !empty($data->product_discussion_response_id)) {
                $discussion = DiscussionResponse::find($data->product_discussion_response_id);
                $discussion->status = 0;
                $discussion->save();
            }

            if (!$report) {
                DB::rollBack();
                $response['status'] = false;
                $response['message'] = 'Laporan gagal dibuat';
                return $response;
            }

            DB::commit();
            $response['status'] = true;
            $response['message'] = 'Laporan berhasil dibuat';

            return $response;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }
}

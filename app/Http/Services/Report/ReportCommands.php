<?php

namespace App\Http\Services\Report;

use App\Http\Services\Service;
use App\Models\DiscussionMaster;
use App\Models\DiscussionResponse;
use App\Models\MerchantBanner;
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

            if ($data->report_type == 'violation' || $data->report_type == 'block_user') {
                $report = Report::create([
                    'product_id' => $data->product_id,
                    'review_id' => $data->review_id,
                    'product_discussion_master_id' => $data->product_discussion_master_id,
                    'product_discussion_response_id' => $data->product_discussion_response_id,
                    'merchant_banner_id' => $data->merchant_banner_id,
                    'report_type' => $data->report_type,
                    'reported_by' => Auth::user()->id,
                    'reported_user_id' => $data->reported_user_id,
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

                if (isset($data->merchant_banner_id) && !empty($data->merchant_banner_id)) {
                    $banner = MerchantBanner::find($data->merchant_banner_id);
                    $banner->status = 0;
                    $banner->save();
                }
            }

            if ($data->report_type == 'violation_potential') {
                $report = Report::create([
                    'product_id' => $data->product_id,
                    'review_id' => $data->review_id,
                    'product_discussion_master_id' => $data->product_discussion_master_id,
                    'product_discussion_response_id' => $data->product_discussion_response_id,
                    'merchant_banner_id' => $data->merchant_banner_id,
                    'report_type' => $data->report_type,
                    'reported_by' => Auth::user()->id,
                    'reported_user_id' => $data->reported_user_id,
                    'reason' => $data->reason,
                    'description' => $data->description ?? null,
                ]);
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

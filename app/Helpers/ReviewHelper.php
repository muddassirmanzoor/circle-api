<?php

namespace App\Helpers;

use App\Review;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\ReviewReply;
use App\Freelancer;
use App\Appointment;

Class ReviewHelper {

    public static function addFreelancerReview($inputs = []) {
        $validation = Validator::make($inputs, ReviewValidationHelper::addReviewRules()['rules'], ReviewValidationHelper::addReviewRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $inputs['customer_id'] = CommonHelper::getCutomerIdByUuid($inputs['customer_uuid']);
        $inputs['content_id'] = CommonHelper::getContentIdByUUid($inputs['type'], $inputs['content_uuid']);

        $check_review = Review::checkCustomerReview('reviewer_id', $inputs['customer_id'], $inputs);
        if ($check_review) {
            return CommonHelper::jsonErrorResponse(ReviewMessageHelper::getMessageData('error', $inputs['lang'])['resend_error']);
        }

        if ($inputs['type'] == "appointment") {
            $check_status = Appointment::getAppointmentDetail('appointment_uuid', $inputs['content_uuid']);

            if (empty($check_status)) {
                return CommonHelper::jsonErrorResponse(ReviewMessageHelper::getMessageData('error', $inputs['lang'])['empty_error']);
            }
            if ($check_status['status'] == "pending") {
                return CommonHelper::jsonErrorResponse(ReviewMessageHelper::getMessageData('error', $inputs['lang'])['pending_status_error']);
            }
        }

        $review_inputs = self::setReviewParams($inputs);

        $save_review = Review::saveFreelancerReview($review_inputs);

        if (!$save_review) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(ReviewMessageHelper::getMessageData('error', $inputs['lang'])['save_error']);
        }
        if ($inputs['is_review'] == 1) {
            ProcessNotificationHelper::sendRatingNotification($inputs, $save_review);
        }
        DB::commit();
        return CommonHelper::jsonSuccessResponse(ReviewMessageHelper::getMessageData('success', $inputs['lang'])['successful_request']);
    }

    public static function setReviewParams($inputs = []) {
        return [
            'reviewer_id' => $inputs['customer_id'],
            'reviewed_id' => CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']),
            'rating' => $inputs['rating'] ?? null,
            'review' => $inputs['review'],
            'content_id' => $inputs['content_id'] ?? null,
            'type' => $inputs['type'] ?? 'subscription',
            'is_review' => $inputs['is_review'] ?? 1
        ];
    }

    public static function addReviewReply($inputs = []) {
        $validation = Validator::make($inputs, ReviewValidationHelper::addReviewReplyRules()['rules'], ReviewValidationHelper::addReviewReplyRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $inputs['reply_uuid'] = UuidHelper::generateUniqueUUID('review_replies', 'reply_uuid');

        $inputs['review_id'] = Review::where('review_uuid', $inputs['review_uuid'])->first()->id;
        
        $inputs['profile_id'] = Freelancer::where('freelancer_uuid', $inputs['logged_in_uuid'])->first()->id;
        $save_reply = ReviewReply::saveReply($inputs);
        if (!$save_reply) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(ReviewMessageHelper::getMessageData('error', $inputs['lang'])['save_error']);
        }
        $reply = ReviewReply::getSingleReply('reply_uuid', $save_reply['reply_uuid']);
        $response = ReviewDataHelper::getReviewReplyResponse($reply);
        DB::commit();
        return CommonHelper::jsonSuccessResponse(ReviewMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getFreelancerReviews($inputs = []) {
        $validation = Validator::make($inputs, ReviewValidationHelper::getFreelancerReviewRules()['rules'], ReviewValidationHelper::getFreelancerReviewRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $inputs['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);

        $review_data = Review::getFreelancerReviews('reviewed_id', $inputs['freelancer_id'], $inputs, (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null));
        $response = ReviewDataHelper::makeFreelancerReviewResponse($review_data);
        return CommonHelper::jsonSuccessResponse(ReviewMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getSingleReview($inputs = []) {
        $validation = Validator::make($inputs, ReviewValidationHelper::getSingleReviewRules()['rules'], ReviewValidationHelper::getSingleReviewRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $review_data = Review::getSingleReview('review_uuid', $inputs['review_uuid']);
        $response = ReviewDataHelper::prepareReviewResponse($review_data);
        return CommonHelper::jsonSuccessResponse(ReviewMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

}

?>

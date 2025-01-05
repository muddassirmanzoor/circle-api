<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\AppReview;
use App\Customer;
use App\Freelancer;

class FeedBackHelper
{
    /*
      |--------------------------------------------------------------------------
      | FeedBackHelper that contains all the posts like related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use post like processes
      |
     */

    public static function addFeedBack($inputs)
    {
        $validation = Validator::make($inputs, FeedBackValidationHelper::addFeedBackRules()['rules'], FeedBackValidationHelper::addFeedBackRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $tInput = [];
        $tInput['type'] = strtolower($inputs['type']);
        $tInput['description'] = $inputs['comments'];

        $email = null;
        if (strtolower($inputs['login_user_type']) == 'freelancer') {
            $email = CommonHelper::getFreelancerIdByUuid($inputs['profile_uuid'], 'email');
        } elseif (strtolower($inputs['login_user_type']) == 'customer') {
            $email = CommonHelper::getCutomerIdByUuid($inputs['profile_uuid'], 'email');
        }

        $tInput['email'] = $email;
        FreshServiceHelper::createTicket($tInput);

        $feedback_data = self::makeFeedBackArray($inputs);
        $save_feedback = AppReview::saveFeedBack($feedback_data);
        if (empty($save_feedback)) {
            return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('error', $inputs['lang'])['success_error']);
        }
        DB::commit();
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request']);
    }

    public static function makeFeedBackArray($input)
    {
        $userId = 0;
        if (strtolower($input['login_user_type']) == 'freelancer') {
            $userId = CommonHelper::getFreelancerIdByUuid($input['profile_uuid'], 'user_id');
        } elseif (strtolower($input['login_user_type']) == 'customer') {
            $userId = CommonHelper::getCutomerIdByUuid($input['profile_uuid'], 'user_id');
        }

        $data = array(
            'app_review_uuid' => UuidHelper::generateUniqueUUID(),
            'user_id' => !empty($input['profile_uuid']) ? $userId : null,
            'type' => !empty($input['type']) ? $input['type'] : null,
            'comments' => !empty($input['comments']) ? $input['comments'] : null,
        );
        return $data;
    }
}

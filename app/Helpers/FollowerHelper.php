<?php

namespace App\Helpers;

use App\Follower;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

Class FollowerHelper {

    public static function processFollowing($inputs = []) {
        $validation = Validator::make($inputs, FollowerValidationHelper::processFollowingRules()['rules'], FollowerValidationHelper::processFollowingRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        if ($inputs['follow_type'] == 'follow') {
            $following_inputs = ['follower_id' => $inputs['customer_id'], 'following_id' => $inputs['freelancer_id']];
            $check = Follower::checkFollowing($following_inputs['follower_id'], $following_inputs['following_id']);
            if ($check) {
                DB::rollBack();
                return CommonHelper::jsonErrorResponse(FollowerMessageHelper::getMessageData('error', $inputs['lang'])['follower_exists']);
            }
            $save_process = Follower::saveProcessFollower($following_inputs);

            if (!$save_process) {
                DB::rollBack();
                return CommonHelper::jsonErrorResponse(FollowerMessageHelper::getMessageData('error', $inputs['lang'])['save_error']);
            }
            $following_inputs['freelancer_id'] = $inputs['freelancer_id'];
            $following_inputs['customer_id'] = $inputs['customer_id'];
            ProcessNotificationHelper::sendFollowerNotification($following_inputs, $save_process);
            DB::commit();
            return CommonHelper::jsonSuccessResponse(FollowerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request']);
        } elseif ($inputs['follow_type'] == 'unfollow') {
            $delete_follower = Follower::deleteFreelancerFollowers($inputs['freelancer_id'], $inputs['customer_id']);
            if (!$delete_follower) {
                DB::rollBack();
                return CommonHelper::jsonErrorResponse(FollowerMessageHelper::getMessageData('error', $inputs['lang'])['delete_error']);
            }
            DB::commit();
            return CommonHelper::jsonSuccessResponse(FollowerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request']);
        }
    }

    public static function getFreelancerFollowers($inputs = []) {
        $validation = Validator::make($inputs, FollowerValidationHelper::getFreelancerFollowerRules()['rules'], FollowerValidationHelper::getFreelancerFollowerRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $inputs['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);
        $follower_data = Follower::getFreelancerFollowers('following_id', $inputs['freelancer_id'], (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null));
        $response['followers_count'] = Follower::getFollowersCount('following_id', $inputs['freelancer_id']);
        $response['followers'] = FollowerDataHelper::makeFreelancerFollowerResponse($follower_data);
        return CommonHelper::jsonSuccessResponse(FollowerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

}

?>

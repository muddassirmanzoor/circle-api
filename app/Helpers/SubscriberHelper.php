<?php

namespace App\Helpers;

use App\Subscription;
use Illuminate\Support\Facades\Validator;

Class SubscriberHelper {

    public static function getSubscribers($inputs = []) {
        $validation = Validator::make($inputs, SubscriberValidationHelper::getSubscriberRules()['rules'], SubscriberValidationHelper::getSubscriberRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $inputs['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);
        $subscriber_data = Subscription::getSubscribers('subscribed_id', $inputs['freelancer_id'], (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null));
        $response['subscriber_count'] = Subscription::getSubscribersCount('subscribed_id', $inputs['freelancer_id']);
//        $response['subscriber_count'] = 4;
        $response['subscribers'] = SubscriberResponseHelper::makeSubscriberResponse($subscriber_data);
        return CommonHelper::jsonSuccessResponse(SubscriberMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

}

?>

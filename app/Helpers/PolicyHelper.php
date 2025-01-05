<?php

namespace App\Helpers;

use App\FreelanceCategory;
use DB;
use Illuminate\Support\Facades\Validator;

Class PolicyHelper {
    /*
      |--------------------------------------------------------------------------
      | PolicyHelper that contains all the policy related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use policy processes
      |
     */

    /**
     * Description of PolicyHelpers
     *
     * @author ILSA Interactive
     */
    public static function getPolicyRules() {
        $validate['rules'] = [
            'logged_in_uuid' => 'required',
            'login_user_type' => 'required',
            'policy_type' => 'required',
            'activity_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'successful_request' => 'Request successful',
            'invalid_data' => 'Invalid data provided',
            'logged_in_uuid.required' => 'Login user uuid is missing',
            'login_user_type.required' => 'Login user type is missing',
            'policy_type.required' => 'Policy type is missing',
            'activity_type.required' => 'Activity type is missing',
        ];
    }

    public static function arabicMessages() {
        return [
            'successful_request' => 'Request successful',
            'invalid_data' => 'Invalid data provided',
            'logged_in_uuid.required' => 'Login user uuid is missing',
            'login_user_type.required' => 'Login user type is missing',
            'policy_type.required' => 'Policy type is missing',
            'activity_type.required' => 'Activity type is missing',
        ];
    }

    public static function getPolicy($inputs = []) {
        $validation = Validator::make($inputs, self::getPolicyRules()['rules'], self::getPolicyRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        if ($inputs['policy_type'] == 'cancel' && $inputs['activity_type'] == 'appointment') {
            return self::getCancelAppointmentPolicy($inputs);
        }
        if ($inputs['policy_type'] == 'cancel' && $inputs['activity_type'] == 'class') {
            return self::getCancelClassPolicy($inputs);
        }
        return CommonHelper::jsonErrorResponse(self::getPolicyRules()['message_' . strtolower($inputs['lang'])]['invalid_data']);
    }

    public static function getCancelAppointmentPolicy($inputs = []) {
        $response = [];
        $response['policy_uuid'] = 'asdf-tgyhf-56tgf-7y6tgh-ujkh78';
        $response['policy'] = 'There are charges for cancelations. This policy sets out the basis on which appointments and Subscriptions may be cancelled and refunded. Users should ensure they have read and understood this policy before proceeding. By continuing, users agree to be bound by the terms of this policy. If users do not agree to this policy then they must not continue or proceed with the checkout.';
        return CommonHelper::jsonSuccessResponse(self::getPolicyRules()['message_' . strtolower($inputs['lang'])]['successful_request'], $response);
    }

    public static function getCancelClassPolicy($inputs = []) {
        $response = [];
        $response['policy_uuid'] = 'asdf-tgyhf-56tgf-7y6tgh-u897yu';
        $response['policy'] = 'There are charges for cancelations. This policy sets out the basis\non which appointments and Subscriptions may be cancelled and refunded.
Users should ensure they have read and understood this policy before proceeding. By continuing, users agree to be bound by the terms of this policy. If users do not agree to this policy then they must not continue or proceed with the checkout.';
        return CommonHelper::jsonSuccessResponse(self::getPolicyRules()['message_' . strtolower($inputs['lang'])]['successful_request'], $response);
    }

}

?>
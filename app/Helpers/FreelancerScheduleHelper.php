<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Validator;
use App\Freelancer;
use App\Schedule;

Class FreelancerScheduleHelper {
    /*
      |--------------------------------------------------------------------------
      | FreelancerScheduleHelper that contains schedule related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use schedule processes
      |
     */

    /**
     * Description of FreelancerScheduleHelper
     *
     * @author ILSA Interactive
     */
    public static function getFreelancerSchedule($inputs = []) {
        $validation = Validator::make($inputs, ScheduleValidationHelper::getFreelancerScheduleRules()['rules'], ScheduleValidationHelper::getFreelancerScheduleRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $freelancer = Freelancer::getFreelancerDetail('freelancer_uuid', $inputs['freelancer_uuid']);
        if (empty($freelancer)) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['invalid_data']);
        }
        $schedule = Schedule::getFreelancerSchedule('freelancer_uuid', $inputs['freelancer_uuid']);
        $response = FreelancerScheduleResponseHelper::freelancerScheduleResponse($schedule, $inputs['local_timezone']);
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

}

?>
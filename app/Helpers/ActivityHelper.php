<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Validator;
use DB;

Class ActivityHelper {
    /*
      |--------------------------------------------------------------------------
      | ActivityHelper that contains activity  related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use activity processes
      |
     */

    /**
     * Description of ActivityHelper
     *
     * @author ILSA Interactive
     */
    public static function licenceAgreement($inputs = []) {
        $validation = Validator::make($inputs, ActivityValidationHelper::licenceAgreementRules()['rules'], ActivityValidationHelper::licenceAgreementRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $freelancer_inputs = ['freelancer_uuid' => $inputs['profile_uuid'], 'onboard_count' => $inputs['onboard_count'],'lang' => $inputs['lang']];
        $save_profile = FreelancerHelper::updateFreelancer($freelancer_inputs);
        $result = json_decode(json_encode($save_profile));
        if ($result->original->success) {
            DB::commit();
            return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request']);
        }
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('error', $inputs['lang'])['success_error']);
    }

}

?>
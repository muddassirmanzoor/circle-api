<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Validator;
use App\Share;
use DB;

Class ShareHelper {
    /*
      |--------------------------------------------------------------------------
      | ShareHelper that contains sharing related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use sharing processes
      |
     */

    /**
     * Description of ActivityHelper
     *
     * @author ILSA Interactive
     */
    public static function shareContent($inputs = []) {
        $validation = Validator::make($inputs, ShareValidationHelper::shareContentRules()['rules'], ShareValidationHelper::shareContentRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $save_data = Share::saveData($inputs);
        if (!$save_data) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['success_error']);
        }
//        $response = ShareResponseHelper::shareContentResponse($inputs);
        DB::commit();
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request']);
    }

}

?>
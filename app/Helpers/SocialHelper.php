<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Validator;
use App\SocialMedia;

Class SocialHelper {
    /*
      |--------------------------------------------------------------------------
      | SocialHelper that contains social related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use social processes
      |
     */

    /**
     * Description of SocialHelper
     *
     * @author ILSA Interactive
     */
    public static function addSocialMedia($inputs) {
        if (empty($inputs['media'])) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['invalid_data']);
        }

        $user_id = null;

        foreach ($inputs['media'] as $key => $media) {
            $validation = Validator::make($media, SocialValidationHelper::addSocialMediaRules()['rules'], SocialValidationHelper::addSocialMediaRules()['message_' . strtolower($inputs['lang'])]);
            if ($validation->fails()) {
                return CommonHelper::jsonErrorResponse($validation->errors()->first());
            }
            $inputs['media'][$key]['social_media_uuid'] = UuidHelper::generateUniqueUUID('social_medias', 'social_media_uuid');
            $inputs['media'][$key]['user_id'] = CommonHelper::getRecordByUserType($inputs['login_user_type'],$inputs['logged_in_uuid'],'user_id');
            $user_id = CommonHelper::getRecordByUserType($inputs['login_user_type'],$inputs['logged_in_uuid'],'user_id');
            unset($inputs['media'][$key]['profile_uuid']);
        }

        $delete_social_media = SocialMedia::deleteSocialMedia('user_id', $user_id);

        $save_social_media = SocialMedia::saveSocialMedia($inputs['media']);

        if (!$save_social_media) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['success_error']);
        }
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request']);
    }

}

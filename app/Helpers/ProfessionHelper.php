<?php

namespace App\Helpers;

use DB;
use App\Profession;
use Illuminate\Support\Facades\Validator;

class ProfessionHelper
{
    public static function getAllProfessions($inputs = []) {
        $validation = Validator::make($inputs, ProfessionValidationHelper::getAllProfessionsRules()['rules'], ProfessionValidationHelper::getAllProfessionsRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $professions = Profession::getAllProfessions();
        $professionArr = self::prepareProfessionListResponse($professions);
        return CommonHelper::jsonSuccessResponse(PromoCodeMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $professionArr);
    }
    
    public static function prepareProfessionListResponse($professions) {
        $response = [];
        if(isset($professions) && !empty($professions)) {
            foreach ($professions as $key => $profession) {
                $response[$key]['profession_uuid'] = $profession['profession_uuid'];
                $response[$key]['name'] = $profession['name'];
            }
        }
        return $response;
    }
}

<?php

namespace App\Helpers;

use App\CreditCard;
use Illuminate\Support\Facades\Validator;
use App\Helpers\CreditCardValidationHelper;
use App\Helpers\CreditCardMessageHelper;
use DB;

Class CreditCardHelper {
    /*
      |--------------------------------------------------------------------------
      | CreditCardHelper that contains all the freelancer related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use freelancer processes
      |
     */

    /**
     * Description of CreditCardHelper
     *
     * @author ILSA Interactive
     */

    public static function addCreditCards($inputs){
        $validation = Validator::make($inputs, CreditCardValidationHelper::addCreditCardRules()['rules'], CreditCardValidationHelper::addCreditCardRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $conditions = ['profile_uuid' => $inputs['profile_uuid'], 'card_no' => $inputs['card_no']];
        $is_exist = CreditCard::checkCardAlreadyExist($conditions);
        if(empty($is_exist)){
            $columns = ['logged_in_uuid' => $inputs['logged_in_uuid'], 'profile_uuid' => $inputs['profile_uuid'], 'card_no' => $inputs['card_no']];
            $created = CreditCard::create($columns);
            if($created){
                DB::commit();
                $response = self::setCreditCardResponse($created->toArray());
                return CommonHelper::jsonSuccessResponse(CreditCardMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
            }
            return CommonHelper::jsonErrorResponse(CreditCardMessageHelper::getMessageData('error', $inputs['lang'])['card_not_saved']);
        }
        return CommonHelper::jsonErrorResponse(CreditCardMessageHelper::getMessageData('error', $inputs['lang'])['already_exist']);
    }

    public static function getCreditCards($inputs){
        $validation = Validator::make($inputs, CreditCardValidationHelper::getCreditCardRules()['rules'], CreditCardValidationHelper::getCreditCardRules()['message_' . strtolower($inputs['lang'])]);
        
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $all_cards = CreditCard::getCreditCards('profile_uuid', $inputs['profile_uuid']);
        if(!empty($all_cards)){
            $response = [];
            foreach($all_cards as $key => $card){
                $response[$key] = self::setCreditCardResponse($card);
            }
            return CommonHelper::jsonSuccessResponse(CreditCardMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
        }
        return CommonHelper::jsonErrorResponse(CreditCardMessageHelper::getMessageData('error', $inputs['lang'])['no_record_found']);
    }

    public static function setCreditCardResponse($result){
        $response['profile_uuid'] = $result['profile_uuid'];
        $response['card_no'] = $result['card_no'];
        return $response;
    }
}
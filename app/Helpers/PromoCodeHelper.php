<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use DB;
use App\PromoCode;
use App\Notification;
use App\ClientPromoCode;

Class PromoCodeHelper {
    /*
      |--------------------------------------------------------------------------
      | PromoCodeHelper that contains all the freelancer related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use freelancer processes
      |
     */

    /**
     * Description of PromoCodeHelper
     *
     * @author ILSA Interactive
     */
    public static function addPromoCodes($inputs = []) {
        $validation = Validator::make($inputs, PromoCodeValidationHelper::addPromoCodesRules()['rules'], PromoCodeValidationHelper::addPromoCodesRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $promoCodeExit = PromoCode::checkPromoCodeExist($inputs['freelancer_uuid'], $inputs['coupon_code']);
        if (!empty($promoCodeExit)) {
            return CommonHelper::jsonErrorResponse(PromoCodeMessageHelper::getMessageData('error', $inputs['lang'])['code_exist']);
        }
        $promocode_data = PromoCodeDataHelper::makePromoCodeArray($inputs);
        if (empty($promocode_data)) {
            return CommonHelper::jsonErrorResponse(PromoCodeMessageHelper::getMessageData('error', $inputs['lang'])['empty_freelancer']);
        }
        $save_data = PromoCode::savePromoCode($promocode_data);
        if (empty($save_data)) {
            return CommonHelper::jsonErrorResponse(PromoCodeMessageHelper::getMessageData('success', $inputs['lang'])['success_error']);
        }
        $response = PromoCodeResponseHelper::prepareSinglePromoCodeResponse($save_data);
        DB::commit();
        return CommonHelper::jsonSuccessResponse(PromoCodeMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getActivePromoCodes($inputs = []) {
        $validation = Validator::make($inputs, PromoCodeValidationHelper::getPromoCodesRules()['rules'], PromoCodeValidationHelper::getPromoCodesRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $activePromoCodes = PromoCode::getActivePromoCodelist($inputs['freelancer_uuid'], (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null));
        $promoCodesArr = PromoCodeResponseHelper::preparePromoCodeListResponse($activePromoCodes);
        return CommonHelper::jsonSuccessResponse(PromoCodeMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $promoCodesArr);
    }

    public static function getExpiredPromoCodes($inputs = []) {
        $validation = Validator::make($inputs, PromoCodeValidationHelper::getPromoCodesRules()['rules'], PromoCodeValidationHelper::getPromoCodesRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $expiredPromoCodes = PromoCode::getExpiredPromoCodelist($inputs['freelancer_uuid'], (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null));
        $promoCodesArr = PromoCodeResponseHelper::preparePromoCodeListResponse($expiredPromoCodes);
        return CommonHelper::jsonSuccessResponse(PromoCodeMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $promoCodesArr);
    }

    public static function sendPromoCodes($inputs = []) {
        $validation = Validator::make($inputs, PromoCodeValidationHelper::sendPromoCodesRules()['rules'], PromoCodeValidationHelper::sendPromoCodesRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $notification = [];
        $promocode_data = PromoCodeDataHelper::makeSendPromoCodeArray($inputs);
        $save = ClientPromoCode::saveClientPromoCode($promocode_data);

        if (!$save) {
            DB::rollback();
            return CommonHelper::jsonErrorResponse(PromoCodeMessageHelper::getMessageData('error', $inputs['lang'])['success_error']);
        }
        $inputs['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);
        foreach ($inputs['customer_uuid'] as $key => $customer_uuid) {
            $inputs['customer_id'] = CommonHelper::getCutomerIdByUuid($customer_uuid);
            $notification[$key] = self::sendPromoCodeNotification($inputs, $customer_uuid);
        }
        DB::commit();
        return CommonHelper::jsonSuccessResponse(PromoCodeMessageHelper::getMessageData('success', $inputs['lang'])['successful_request']);
    }

    public static function sendPromoCodeNotification($inputs = [], $customer_uuid = null, $notificationType = 'promo_code') {
        $data = [];
        $inputs['customer_uuid'] = $customer_uuid;

        $sender_data = ProcessNotificationHelper::processFreelancerSender($inputs);
        $receiver_data = ProcessNotificationHelper::processCustomerReceiver($inputs);
        $data['sender'] = [];
        $data['receiver'] = [];
        if (!empty($sender_data['sender'])) {
            $data['sender'] = $sender_data['sender'];
        }
        if (!empty($receiver_data['receiver'])) {
            $data['receiver'] = $receiver_data['receiver'];
        }
        $messageData = [
            'type' => $notificationType,
            'message' => $sender_data['sender']['first_name'] . ' has sent you a promo code '.$inputs['coupon_code'],
            'save_message' => ' has sent you a promo code '.$inputs['coupon_code'],
            'data' => $data,
            'code_uuid' => $inputs['code_uuid'],
            'name' => $inputs['coupon_code'],
        ];
        $notification_inputs = self::preparePromoCodeNotificationInputs($messageData);
        $save_notification = Notification::addNotification($notification_inputs);
        if (isset($messageData['data']['receiver']['customer_uuid'])) {
            $customer_id = $messageData['data']['receiver']['user_id'];
        }
        return PushNotificationHelper::send_notification_to_user_devices($customer_id, $messageData);
//        if (!empty($receiver_data['device_token'])) {
//            return PushNotificationHelper::send_notification_to_user($receiver_data['device_token'], $messageData);
//        }
    }

    public static function preparePromoCodeNotificationInputs($messageData = []) {
        $notification_inputs = [];

        if (isset($messageData['data']['receiver']['customer_uuid'])) {
            $notification_inputs['receiver_id'] = $messageData['data']['receiver']['user_id'];
        } if (isset($messageData['data']['sender']['freelancer_uuid'])) {
            $notification_inputs['sender_id'] = $messageData['data']['sender']['user_id'];
        }
        $notification_inputs['notification_uuid'] = $messageData['code_uuid'];
        $notification_inputs['message'] = $messageData['save_message'];
        $notification_inputs['name'] = !empty($messageData['name']) ? $messageData['name'] : null;
        $notification_inputs['notification_type'] = $messageData['type'];
        $notification_inputs['is_read'] = 0;

        return $notification_inputs;
    }

    public static function validatePromoCodes($inputs = []) {
        $validation = Validator::make($inputs, PromoCodeValidationHelper::validatePromoCodes()['rules'], PromoCodeValidationHelper::validatePromoCodes()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $promocode_data = PromoCode::validatePromoCodeDetails($inputs['freelancer_uuid'], $inputs['coupon_code']);
        if (empty($promocode_data)) {
            return CommonHelper::jsonErrorResponse(PromoCodeMessageHelper::getMessageData('error', $inputs['lang'])['invalid_code']);
        }
        if ($promocode_data['discount_type'] == 'amount') {
            $checkCustomerCurrency = \App\Customer::where('customer_uuid', $inputs['logged_in_uuid'])->first()->default_currency;
            $checkFreelancerCurrency = \App\Freelancer::where('freelancer_uuid', $inputs['freelancer_uuid'])->first()->default_currency;
            if (!empty($checkCustomerCurrency) && !empty($checkFreelancerCurrency)) {
                if ($checkCustomerCurrency != $checkFreelancerCurrency) {
                    return CommonHelper::jsonErrorResponse(PromoCodeMessageHelper::getMessageData('error', $inputs['lang'])['currency_error']);
                }
            }
        }
        if ($promocode_data['valid_to'] < date('Y-m-d')) {
            return CommonHelper::jsonErrorResponse(PromoCodeMessageHelper::getMessageData('error', $inputs['lang'])['expired_code']);
        }
        $response = PromoCodeDataHelper::promocodeDetailsResponse($promocode_data);
        return CommonHelper::jsonSuccessResponse(PromoCodeMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function deletePromoCode($inputs = []) {
        $validation = Validator::make($inputs, PromoCodeValidationHelper::deletePromoCodeRules()['rules'], PromoCodeValidationHelper::deletePromoCodeRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $data = ['is_archive' => 1];
        $promo_code = PromoCode::updatePromoCode('code_uuid', $inputs['code_uuid'], $data);
        if (!$promo_code) {
            DB::rollback();
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['update_promo_code_error']);
        }
        DB::commit();
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData($type = 'success', $inputs['lang'])['successful_request']);
    }

}

?>

<?php

namespace App\Helpers;

use App\NotificationSetting;
use App\Freelancer;
use App\Customer;
use App\User;
use DB;
use Illuminate\Support\Facades\Validator;

class SettingsHelper
{
    /*
      |--------------------------------------------------------------------------
      | SettingsHelper that contains all the categpry related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use settings processes
      |
     */

    /**
     * Description of CategoryHelper
     *
     * @author ILSA Interactive
     */
    public static function getProfileSetting($inputs = [])
    {
        if (empty($inputs['freelancer_uuid']) && empty($inputs['customer_uuid'])) {
            return CommonHelper::jsonErrorResponse(LoginValidationHelper::validationMessages()['message_' . strtolower($inputs['lang'])]['logout_validation']);
        }
        $profile_uuid = !empty($inputs['freelancer_uuid']) ? CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid'], 'user_id') : CommonHelper::getCutomerIdByUuid($inputs['customer_uuid'], 'user_id');
        $setting_data = NotificationSetting::getSettings('user_id', $profile_uuid);
        $response = self::prepareSettingResponse($setting_data, $inputs['login_user_type']);
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function prepareSettingResponse($data = [], $userType = null)
    {
        $response = [];
        if (!empty($data)) {

            $response['notification_settings_uuid'] = $data['notification_settings_uuid'];
            $response['profile_uuid'] = User::getUserChildren($userType, $data['user_id']);
            $response['new_appointment'] = $data['new_appointment'];
            $response['cancellation'] = $data['cancellation'];
            $response['no_show'] = $data['no_show'];
            $response['new_follower'] = $data['new_follower'];
        }
        return $response;
    }

    public static function prepareChatStatusResponse($data = [])
    {
        $response = [];
        if (!empty($data)) {
            $response['chat_status'] = $data['public_chat'];
        }
        return $response;
    }

    public static function getUserChatSettings($inputs = [])
    {
        $validation = Validator::make($inputs, FreelancerValidationHelper::getUserChatSettingRules()['rules'], FreelancerValidationHelper::getUserChatSettingRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        if ($inputs['login_user_type'] == "customer") {
            $profile = Customer::getSingleCustomerDetail('customer_uuid', $inputs['logged_in_uuid']);
        } elseif ($inputs['login_user_type'] == "freelancer") {
            $profile = Freelancer::checkFreelancer('freelancer_uuid', $inputs['logged_in_uuid']);
        }
        $response = self::prepareChatStatusResponse($profile);
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function updateNotificationSettings($inputs)
    {
        $validation = Validator::make($inputs, FreelancerValidationHelper::updateSettingRules()['rules'], FreelancerValidationHelper::updateSettingRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        /*  ::   add_to_favourite column enabling notifications for add to favourites  */
        /*  :: before adding --> $data = ['new_appointment' => $inputs['new_appointment'], 'cancellation' => $inputs['cancellation'], 'no_show' => $inputs['no_show'], 'new_follower' => $inputs['new_follower']]; */
        $data = ['new_appointment' => $inputs['new_appointment'], 'cancellation' => $inputs['cancellation'], 'no_show' => $inputs['no_show'], 'new_follower' => $inputs['new_follower']];
        $update_settings = NotificationSetting::updateSettings('notification_settings_uuid', $inputs['notification_settings_uuid'], $data);

        if (!$update_settings) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['update_settings_error']);
        }

        $setting_data = NotificationSetting::getSettings('notification_settings_uuid', $inputs['notification_settings_uuid']);
        $response = self::prepareSettingResponse($setting_data,$inputs['login_user_type']);
        DB::commit();
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function updateChatSettings($inputs)
    {
        $validation = Validator::make($inputs, ChatValidationHelper::updateChatSettingRules()['rules'], ChatValidationHelper::updateChatSettingRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $data = ['public_chat' => $inputs['status']];
        if ($inputs['login_user_type'] == "freelancer") {
            $update_settings = Freelancer::updateStatus('freelancer_uuid', $inputs['logged_in_uuid'], $data);
        }
        if ($inputs['login_user_type'] == "customer") {
            $update_settings = Customer::updateStatus('customer_uuid', $inputs['logged_in_uuid'], $data);
        }
        if (!$update_settings) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['update_settings_error']);
        }
        DB::commit();
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request']);
    }
}

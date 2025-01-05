<?php

namespace App\Helpers;

use App\Customer;
use App\Freelancer;
use App\ScreenSetting;
use App\SystemSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

Class HomeScreenHelper {
    /*
      |--------------------------------------------------------------------------
      | HomeScreenHelper that contains all the home screen related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use home screen processes
      |
     */

    /**
     * Description of HomeScreenHelper
     *
     * @author ILSA Interactive
     */
    public static function customizeHomeScreen($inputs) {
        $validation = Validator::make($inputs, HomeScreenValidationHelper::customizeHomeScreenRules()['rules'], HomeScreenValidationHelper::customizeHomeScreenRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $inputs['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($inputs['profile_uuid']);
        $data_inputs = ['freelancer_id' => $inputs['freelancer_id'], 'show_option' => $inputs['show_option']];
        $result = ScreenSetting::createOrUpdate('freelancer_id', $inputs['freelancer_id'], $data_inputs);
        if ($result) {
            DB::commit();
            return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request']);
        }
        DB::rollBack();
        return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['success_error']);
    }

        /**
     * Description of HomeScreenHelper
     *
     * @author ILSA Interactive
     */
    public static function getSystemSettings($inputs = [])
    {
        $result = SystemSetting::getSystemSettings();
        $response = self::prepareSystemSettingsResponse($result);
        if ($result) {
            DB::commit();
            return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
        }
    }

    public static function prepareSystemSettingsResponse($data = []) {
        $response = [];
        if (!empty($data)) {
            $response['system_setting_uuid']         = $data['system_setting_uuid'];
            $response['vat']                         = $data['vat'];
            $response['transaction_charges']         = $data['transaction_charges'];
            $response['circl_fee']                   = $data['circl_fee'];
            $response['withdraw_scheduled_duration'] = config("general.withdraw_schedule." . $data['withdraw_scheduled_duration']);
        }
        return $response;
    }


    public static function deleteUser($inputs = [])
    {
        if(!empty($inputs))
        {
            if($inputs['login_user_type'] == 'customer')
            {
                $customer = Customer::where('customer_uuid',$inputs['logged_in_uuid'])->first();
                if($customer != null)
                {
                    $data = self::deleteUserParams($customer);
                    $is_updated=Customer::updateCustomer('customer_uuid',$inputs['logged_in_uuid'],$data);
                    return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request']);

                }
            }
            elseif($inputs['login_user_type'] == 'freelancer')
            {
                $freelancer = Freelancer::where('freelancer_uuid',$inputs['logged_in_uuid'])->first();
                if($freelancer != null)
                {
                    $data = self::deleteUserParams($freelancer);
                    $is_updated=Freelancer::updateFreelancer('freelancer_uuid',$inputs['logged_in_uuid'],$data);
                    return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request']);
                }
            }
            return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('false', $inputs['lang'])['delete_user']);

        }
    }

    public static function deleteUserParams($user)
    {
        $data['email'] = $user->email.'_archived';
        $data['phone_number'] = $user->phone_number.'_000';
        $data['is_archive'] = 1;
        return $data;
    }
}

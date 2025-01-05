<?php

namespace App\Helpers;

use App\Customer;
use Auth;
use Illuminate\Support\Facades\Validator;
use DB;
use App\Http\Controllers\ChatController;

Class CustomerLoginHelper {
    /*
      |--------------------------------------------------------------------------
      | CustomerLoginHelper that contains customer login related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use customer login processes
      |
     */

    /**
     * Description of CustomerHelper
     *
     * @author ILSA Interactive
     */
    public static function customerLogin($inputs = []) {
        $validation = Validator::make($inputs, LoginValidationHelper::loginRules()['rules'], LoginValidationHelper::loginRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        if (Auth::guard('customer')->attempt(['email' => $inputs['email'], 'password' => $inputs['password']])) {
            if (Auth::guard('customer')->attempt(['email' => $inputs['email'], 'password' => $inputs['password'], 'is_archive' => 1])) {
                return CommonHelper::jsonErrorResponse(LoginValidationHelper::validationMessages()['message_' . strtolower($inputs['lang'])]['deleted_account']);
            } elseif (Auth::guard('customer')->attempt(['email' => $inputs['email'], 'password' => $inputs['password'], 'is_active' => 0])) {
                return CommonHelper::jsonErrorResponse(LoginValidationHelper::validationMessages()['message_' . strtolower($inputs['lang'])]['blocked_account']);
            }
            $customer = Customer::getSingleCustomer('email', $inputs['email']);
            $data = ['is_login' => 1];
            $inputs['device'] = ['device_type' => (!empty($inputs['device_type'])) ? $inputs['device_type'] : '', 'device_token' => (!empty($inputs['device_token'])) ? $inputs['device_token'] : ''];

            $update_device = LoginHelper::updateDeviceData($inputs['device'], $customer);
            $update_customer = Customer::updateCustomer('customer_uuid', $customer['customer_uuid'], $data);

            if (!$update_device || !$update_customer) {
                DB::rollBack();
                return CommonHelper::jsonErrorResponse(LoginValidationHelper::validationMessages()['message_' . strtolower($inputs['lang'])]['login_error']);
            }

            $response = CustomerResponseHelper::prepareLoginResponse($customer);

            $customer['type'] = "customer";
//            $create_chat = ChatController::createAdminChat($customer);
            DB::commit();
            return CommonHelper::jsonSuccessResponse(LoginValidationHelper::validationMessages()['message_' . strtolower($inputs['lang'])]['login_success'], $response);
        }
        return CommonHelper::jsonErrorResponse(LoginValidationHelper::validationMessages()['message_' . strtolower($inputs['lang'])]['invalid_login']);
    }

    public static function checkCustomerInterests($data = []) {
        $interest_check = false;
        if (!empty($data)) {
            $interest_check = \App\Interest::getCustomerInterest('customer_id', $data['id']);
        }
        return !empty($interest_check) ? true : false;
    }

}

?>

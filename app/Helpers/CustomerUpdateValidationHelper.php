<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Validator;
use App\Customer;

Class CustomerUpdateValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | CustomerValidationHelper that contains all the customer Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use customer processes
      |
     */

     public static function updatePhoneWithPinCode() {
        $validate['rules'] = [
            'phone_number' => 'required',
            'verification_code' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }


    public static function customerUpdateRules() {
        $validate['rules'] = [
            'first_name' => 'required',
            'last_name' => 'required',
            'dob' => 'required',
            'gender' => 'required',
            'email' => 'required|email'
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function customerPhoneUpdateRules() {
        $validate['rules'] = [
            'phone_number' => 'required',
            'country_code' => 'required',
            'country_name' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function updateProfileRules() {
        $validate['rules'] = [
            'customer_uuid' => 'required',
//            'phone_number' => 'unique:customers'
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function updateUserNameRules() {
        $validate['rules'] = [
            'customer_uuid' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function uniqueCustomerEmailRules($id = 1) {
        $validate['rules'] = [
            'email' => 'required|unique:customers,email,' . $id,
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function uniqueCustomerPhoneRules() {
        $validate['rules'] = [
            'phone_number' => 'unique:customers',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'customer_uuid.required' => 'Customer uuid is missing',
            'first_name.required' => 'First name is missing',
            'last_name.required' => 'Last name is missing',
            'dob.required' => 'Date of birth is missing',
            'gender.required' => 'Gender is missing',
            'email.required' => 'Email is missing',
            'email.unique' => 'This email has already been taken.',
            'phone_number.required' => 'Phone number is missing',
            'phone_number.unique' => 'Phone number already exists',
            'country_code.required' => 'Phone number country code is missing',
            'country_name.required' => 'Phone number country name is missing',
            'password.required' => 'Password is missing',
            'verification_code.required' => 'Pin Code required for updating phone number',
        ];
    }

    public static function arabicMessages() {
        return [
            'customer_uuid.required' => 'Customer uuid is missing',
            'first_name.required' => 'First name is missing',
            'last_name.required' => 'Last name is missing',
            'dob.required' => 'Date of birth is missing',
            'email.unique' => 'This email has already been taken.',
            'gender.required' => 'Gender is missing',
            'email.required' => 'Email is missing',
            'phone_number.required' => 'Phone number is missing',
            'phone_number.unique' => 'Phone number already exists',
            'country_code.required' => 'Phone number country code is missing',
            'country_name.required' => 'Phone number country name is missing',
            'password.required' => 'Password is missing',
            'verification_code.required' => 'Pin Code required for updating phone number',
        ];
    }

    public static function processCustomerInputs($inputs = [], $check_customer = []) {
        $customer_inputs = ['success' => true, 'message' => 'Successful request', 'data' => []];
        if (!empty($inputs['customer_uuid'])) {
            $customer_inputs['data']['customer_uuid'] = $inputs['customer_uuid'];
        }
        if (!empty($inputs['first_name'])) {
            $customer_inputs['data']['first_name'] = $inputs['first_name'];
        }
        if (!empty($inputs['last_name'])) {
            $customer_inputs['data']['last_name'] = $inputs['last_name'];
        }
        if (!empty($inputs['gender'])) {
            $customer_inputs['data']['gender'] = $inputs['gender'];
        }
        if (!empty($inputs['email'])) {
            $email = !empty($check_customer['email']) ? $check_customer['email'] : null;
            $update_email = $inputs['email'];
            if (!empty($email) && $inputs['email'] == $email) {
                $update_email = null;
            }
            if (!empty($update_email)) {
                $customer_id = Customer::pluckCustomerAttribute('customer_uuid', $inputs['customer_uuid'], 'id');
                $validation = Validator::make($inputs, CustomerUpdateValidationHelper::uniqueCustomerEmailRules($customer_id[0])['rules'], CustomerUpdateValidationHelper::uniqueCustomerEmailRules($customer_id[0])['message_' . strtolower($inputs['lang'])]);
                if ($validation->fails()) {
                    return ['success' => false, 'message' => $validation->errors()->first(), 'data' => []];
                }
            }
            $customer_inputs['data']['email'] = !empty($update_email) ? $update_email : $inputs['email'];
//            $customer_id = Customer::pluckCustomerAttribute('customer_uuid', $inputs['customer_uuid'], 'id');
//            $validation = Validator::make($inputs, CustomerUpdateValidationHelper::uniqueCustomerEmailRules($customer_id[0])['rules'], CustomerUpdateValidationHelper::uniqueCustomerEmailRules($customer_id[0])['message_' . strtolower($inputs['lang'])]);
//            if ($validation->fails()) {
//                return ['success' => false, 'message' => $validation->errors()->first(), 'data' => []];
//            }
//            $customer_inputs['data']['email'] = $inputs['email'];
        }
        if (!empty($inputs['dob'])) {
            $customer_inputs['data']['dob'] = $inputs['dob'];
        }
        if (!empty($inputs['facebook_id'])) {
            $customer_inputs['data']['facebook_id'] = $inputs['facebook_id'];
        }
        if (!empty($inputs['google_id'])) {
            $customer_inputs['data']['google_id'] = $inputs['google_id'];
        }
        if (!empty($inputs['apple_id'])) {
            $customer_inputs['data']['apple_id'] = $inputs['apple_id'];
        }
        if (!empty($inputs['profile_image'])) {
            $customer_inputs['data']['profile_image'] = $inputs['profile_image'];
        }
        return self::continueCustomerInputs($customer_inputs, $inputs, $check_customer);
    }

    public static function continueCustomerInputs($customer_inputs, $inputs = [], $check_customer = []) {
        if (!empty($inputs['phone_number'])) {
            $phone_number = !empty($check_customer['phone_number']) ? $check_customer['phone_number'] : null;
            $update_number = $inputs['phone_number'];
            if (!empty($phone_number) && $inputs['phone_number'] == $phone_number) {
                $update_number = null;
            }
            if (!empty($update_number)) {
                $validation = Validator::make($inputs, CustomerUpdateValidationHelper::uniqueCustomerPhoneRules()['rules'], CustomerUpdateValidationHelper::uniqueCustomerPhoneRules()['message_' . strtolower($inputs['lang'])]);
                if ($validation->fails()) {
                    return ['success' => false, 'message' => $validation->errors()->first(), 'data' => []];
                }
            }
            $customer_inputs['data']['phone_number'] = !empty($update_number) ? $update_number : $inputs['phone_number'];
        }
        if (!empty($inputs['country_code'])) {
            $customer_inputs['data']['country_code'] = $inputs['country_code'];
        }
        if (!empty($inputs['country_name'])) {
            $customer_inputs['data']['country_name'] = $inputs['country_name'];
        }
        if (!empty($inputs['address'])) {
            $customer_inputs['data']['address'] = $inputs['address'];
        }
        if (!empty($inputs['lat'])) {
            $customer_inputs['data']['lat'] = $inputs['lat'];
        }
        if (!empty($inputs['lng'])) {
            $customer_inputs['data']['lng'] = $inputs['lng'];
        }
        if (!empty($inputs['onboard_count'])) {
            $customer_inputs['data']['onboard_count'] = $inputs['onboard_count'];
        }
        if (!empty($inputs['address_comments'])) {
            $customer_inputs['data']['address_comments'] = $inputs['address_comments'];
        }else{ 
            // if you want to leave this field blank then it will be empty
            //$customer_inputs['data']['address_comments'] = $inputs['address_comments'];
        }
        if (!empty($inputs['currency'])) {
            $customer_inputs['data']['default_currency'] = $inputs['currency'];
        }
        return $customer_inputs;
    }

}

?>
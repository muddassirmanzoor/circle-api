<?php

namespace App\Helpers;

Class CustomerFeedValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | CustomerValidationHelper that contains all the customer Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use customer processes
      |
     */

    public static function getCustomerHomeFeedRules() {
        $validate['rules'] = [
            'logged_in_uuid' => 'required',
            'customer_uuid' => 'required',
            'is_filtered' => 'required',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getMixFeedRules() {
        $validate['rules'] = [
            'logged_in_uuid' => 'required',
            'customer_uuid' => 'required',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getCustomerStoryFeedRules() {
        $validate['rules'] = [
            'logged_in_uuid' => 'required',
            'customer_uuid' => 'required',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'profile_uuid.required' => 'Profile uuid is missing',
            'customer_uuid.required' => 'customer uuid is missing',
            'first_name.required' => 'First name is missing',
            'last_name.required' => 'Last name is missing',
            'email.required' => 'Email is missing',
            'phone_number.required' => 'Phone number is missing',
            'country_code.required' => 'Phone number country code is missing',
            'country_name.required' => 'Phone number country name is missing',
            'password.required' => 'Password is missing',
            'device_token.required' => 'Device token is required',
            'device_type.required' => 'Device type is required',
            'lat.required' => 'lat is required',
            'lng.required' => 'lng is required',
            'sub_category_uuid.required' => 'Sub Category uuid is required',
            'local_timezone.required' => 'Timezone info is required',
            'is_filtered.required' => 'Feed filter info is required',
        ];
    }

    public static function arabicMessages() {
        return [
            'profile_uuid.required' => 'الملف الشخصي uuid مفقود',
            'customer_uuid.required' => 'الملف الشخصي uuid مفقود',
            'first_name.required' => 'الاسم مفقود',
            'last_name.required' => 'الاسم مفقود',
            'email.required' => 'البريد الإلكتروني مفقود',
            'email.unique' => 'وقد تم بالفعل اتخاذ هذا البريد الإلكتروني',
            'phone_number.required' => 'رقم الهاتف مفقود',
            'country_code.required' => 'Phone number country code is missing',
            'country_name.required' => 'Phone number country name is missing',
            'password.required' => 'كلمة المرور مفقودة',
            'device_token.required' => 'رمز الجهاز مطلوب',
            'device_type.required' => 'نوع الجهاز مطلوب',
            'lat.required' => 'نوع الجهاز مطلوب',
            'lng.required' => 'نوع الجهاز مطلوب',
            'local_timezone.required' => 'Timezone info is required',
            'is_filtered.required' => 'Feed filter info is required',
        ];
    }

}

?>

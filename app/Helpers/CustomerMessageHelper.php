<?php

namespace App\Helpers;

Class CustomerMessageHelper {
    /*
      |--------------------------------------------------------------------------
      | CustomerMessageHelper that contains all the Customer message methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use customer processes
      |
     */

    public static function getMessageData($type = '', $language = 'EN') {
        if (strtolower($language) == 'ar' && $type == 'error') {
            return self::returnArabicErrorMessage();
        } elseif (strtolower($language) == 'en' && $type == 'error') {
            return self::returnEnglishErrorMessage();
        } elseif (strtolower($language) == 'ar' && $type == 'success') {
            return self::returnArabicSuccessMessage();
        } elseif (strtolower($language) == 'en' && $type == 'success') {
            return self::returnEnglishSuccessMessage();
        }
    }

    public static function returnEnglishSuccessMessage() {
        return [
            'successful_request' => 'Request successful!',
            'update_success' => 'Profile updated successfully!',
            'change_password_success' => 'Password successfully updated',
            'save_interest_success' => 'Customer Interest saved successfully',
            
        ];
    }

    public static function returnArabicSuccessMessage() {
        return [
            'successful_request' => 'طلب ناجح!',
            'update_success' => 'تم تحديث الملف الشخصي بنجاح!',
            'change_password_success' => 'تم تحديث كلمة المرور بنجاح'
        ];
    }

    public static function returnEnglishErrorMessage() {
        return [
            'general_error' => 'Sorry, something went wrong. We are working on getting this fixed as soon as we can',
            'signup_error' => "Sorry, we couldn't register your data",
            'update_error' => 'Profile could not be updated',
            'change_password_error' => 'Change password failed',
            'invalid_data_error' => 'Invalid data provided',
            'save_interest_error' => 'Customer Interest not saved',
            'interest_exit_error' => 'Customer already added this Interest',
        ];
    }

    public static function returnArabicErrorMessage() {
        return [
            'general_error' => 'وجه الفتاة! حدث خطأ ما. أعد المحاولة من فضلك',
            'signup_error' => "عذرًا ، لم نتمكن من تسجيل بياناتك",
            'update_error' => 'لا يمكن تحديث الملف الشخصي',
            'change_password_error' => 'فشل تغيير كلمة المرور',
            'invalid_data_error' => 'البيانات غير صالحة المقدمة',
        ];
    }

}

?>
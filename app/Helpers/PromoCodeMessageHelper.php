<?php

namespace App\Helpers;

Class PromoCodeMessageHelper {
    /*
      |--------------------------------------------------------------------------
      | FreelancerMessageHelper that contains all the Freelancer message methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use Freelancer processes
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
            'update_success' => 'Profile updated successfully!'
        ];
    }

    public static function returnArabicSuccessMessage() {
        return [
            'successful_request' => 'طلب ناجح!',
            'update_success' => 'تم تحديث الملف الشخصي بنجاح!'
        ];
    }

    public static function returnEnglishErrorMessage() {
        return [
            'general_error' => 'Sorry, something went wrong. We are working on getting this fixed as soon as we can',
            'update_error' => 'Profile could not be updated',
            'invalid_code' => 'Invalid Coupon',
            'currency_error' => 'Promo code is not valid for this currency',
            'expired_code' => 'Expired Coupon',
            'code_exist' => 'Code is already exist',
            'empty_freelancer' => 'Profile does not exist',
            'success_error' => 'Sorry! your request could not be completed',
        ];
    }

    public static function returnArabicErrorMessage() {
        return [
            'general_error' => 'وجه الفتاة! حدث خطأ ما. أعد المحاولة من فضلك',
            'update_error' => 'لا يمكن تحديث الملف الشخصي',
            'invalid_code' => 'Invalid Coupon',
            'currency_error' => 'Promo code is not valid for this currency',
            'expired_code' => 'Expired Coupon',
            'success_error' => 'Sorry! your request could not be completed',
        ];
    }

}

?>
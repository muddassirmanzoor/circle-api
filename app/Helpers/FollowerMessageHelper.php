<?php

namespace App\Helpers;

Class FollowerMessageHelper {
    /*
      |--------------------------------------------------------------------------
      | ReviewMessageHelper that contains all the message methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use message processes
      |
     */

    /**
     * This function selects message language and message type
     *
     */
    public static function getMessageData($type = '', $language = 'EN') {
        if ($language == 'AR' && $type == 'error') {
            return self::returnArabicErrorMessage();
        } elseif ($language == 'EN' && $type == 'error') {
            return self::returnEnglishErrorMessage();
        } elseif ($language == 'AR' && $type == 'success') {
            return self::returnArabicSuccessMessage();
        } elseif ($language == 'EN' && $type == 'success') {
            return self::returnEnglishSuccessMessage();
        }
    }

    public static function returnEnglishSuccessMessage() {
        return [
            'successful_request' => 'Request successful!',
        ];
    }

    public static function returnArabicSuccessMessage() {
        return [
            'successful_request' => 'طلب ناجح!',
        ];
    }

    public static function returnEnglishErrorMessage() {
        return [
            'general_error' => 'Sorry, something went wrong. We are working on getting this fixed as soon as we can',
            'invalid_data' => 'Invalid data provided',
            'empty_error' => 'Sorry, No record found.',
            'delete_error' => 'Sorry, unfollow process not completed',
            'save_error' => 'Review could not be saved',
            'success_error' => 'Unsuccessful request',
            'follower_exists' => 'Already following',
        ];
    }

    public static function returnArabicErrorMessage() {
        return [
            'general_error' => 'وجه الفتاة! حدث خطأ ما. أعد المحاولة من فضلك',
            'invalid_data' => 'البيانات غير صالحة المقدمة',
            'delete_error' => 'آسف ، لم تكتمل العملية',
            'empty_error' => 'عذرا ، لا يوجد سجل.',
            'save_error' => 'لا يمكن حفظ المراجعة',
            'success_error' => 'طلب غير ناجح',
            'follower_exists' => 'Already following',
        ];
    }

}

?>

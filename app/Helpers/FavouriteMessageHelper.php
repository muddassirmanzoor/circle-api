<?php

namespace App\Helpers;

Class FavouriteMessageHelper {

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
            'save_error' => 'Sorry! the process could not be completed',
            'already_exists' => 'You have already liked this profile',
            'record_not_found' => 'Record not found.',
        ];
    }

    public static function returnArabicErrorMessage() {
        return [
            'save_error' => 'Sorry! the process could not be completed',
            'already_exists' => 'You have already liked this profile',
            'record_not_found' => 'Record not found.',
        ];
    }

}

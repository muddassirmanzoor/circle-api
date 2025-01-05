<?php

namespace App\Helpers;

Class CreditCardMessageHelper {
    /*
      |--------------------------------------------------------------------------
      | CreditCardMessageHelper that contains all the post Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use post processes
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
            'successful_request' => 'Request successful!'
        ];
    }

    public static function returnArabicSuccessMessage() {
        return [
            'successful_request' => 'طلب ناجح!'
        ];
    }

    public static function returnEnglishErrorMessage() {
        return [
            'already_exist' => 'You already add this card against this user.',
            'card_not_saved' => 'Card not saved successfully.',
            'no_record_found' => 'Record not found.',
        ];
    }

    public static function returnArabicErrorMessage() {
        return [
            'already_exist' => 'You already add this card against this user.',
            'card_not_saved' => 'Card not not saved.',
            'no_record_found' => 'Record not found.',
        ];
    }

}

?>
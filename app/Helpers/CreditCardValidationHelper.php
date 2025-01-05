<?php

namespace App\Helpers;

Class CreditCardValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | CreditCardValidationHelper that contains all the post Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use post processes
      |
     */

    public static function addCreditCardRules() {
        $validate['rules'] = [
            'profile_uuid' => 'required',
            'logged_in_uuid' => 'required',
            'card_no' => 'required|integer',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getCreditCardRules() {
        $validate['rules'] = [
            'profile_uuid' => 'required',
            'logged_in_uuid' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'profile_uuid.required' => 'Profile uuid is missing',
            'logged_in_uuid.required' => 'logged in user uuid is missing',
            'card_no.required' => 'Credit card is missing',
            'card_no.integer' => 'Credit card is only digits.'
        ];
    }

    public static function arabicMessages() {
        return [
            'profile_uuid.required' => 'الملف الشخصي uuid مفقود',
            'logged_in_uuid.required' => 'الملف الشخصي uuid مفقود',
            'card_no.required' => 'Credit card is missing'
        ];
    }

}

?>
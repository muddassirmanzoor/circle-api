<?php

namespace App\Helpers;

Class HomeScreenValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | HomeScreenValidationHelper that contains all the activity Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use activity processes
      |
     */

    public static function customizeHomeScreenRules() {
        $validate['rules'] = [
            'profile_uuid' => 'required',
            'show_option' => 'required'
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'profile_uuid.required' => 'Profile uuid is missing',
            'show_option.required' => 'Option is mising'
        ];
    }

    public static function arabicMessages() {
        return [
            'profile_uuid.required' => 'Profile uuid is missing',
            'show_option.required' => 'Option is mising'
        ];
    }

}

?>
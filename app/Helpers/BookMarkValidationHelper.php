<?php

namespace App\Helpers;

Class BookMarkValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | BookMarkValidationHelper that contains all the activity Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use activity processes
      |
     */

    public static function addBookMarkRules() {
        $validate['rules'] = [
            'post_uuid' => 'required',
            'type' => 'required',
            'customer_uuid' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getBookMarkRules() {
        $validate['rules'] = [
            'type' => 'required',
            'profile_uuid' => 'required',
            'logged_in_uuid' => 'required',
            'login_user_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'post_uuid.required' => 'Post uuid is missing',
            'customer_uuid.required' => 'Customer uuid is missing',
            'profile_uuid.required' => 'profile uuid is missing',
            'type.required' => 'Type is missing'
        ];
    }

    public static function arabicMessages() {
        return [
            'post_uuid.required' => 'Post uuid is missing',
            'customer_uuid.required' => 'Customer uuid is missing',
            'profile_uuid.required' => 'profile uuid is missing',
            'type.type' => 'Type is missing',
        ];
    }

}

?>
<?php

namespace App\Helpers;

Class ShareValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | ShareValidationHelper that contains all the sharing Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use sharing processes
      |
     */

    public static function shareContentRules() {
        $validate['rules'] = [
            'profile_uuid' => 'required',
            'content_uuid' => 'required',
            'sharing_channel' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'profile_uuid.required' => 'Profile uuid is required',
            'content_uuid.required' => 'Content uuid is required',
            'sharing_channel.required' => 'Sharing channel is required',
        ];
    }

    public static function arabicMessages() {
        return [
            'profile_uuid.required' => 'Profile uuid is required',
            'content_uuid.required' => 'Content uuid is required',
            'sharing_channel.required' => 'Sharing channel is required',
        ];
    }

}

?>

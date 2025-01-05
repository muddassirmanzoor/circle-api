<?php

namespace App\Helpers;

Class SocialValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | SocialValidationHelper that contains all the activity Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use activity processes
      |
     */

    public static function addSocialMediaRules() {
        $validate['rules'] = [
            'profile_uuid' => 'required',
            'social_media_link' => 'required',
            'social_media_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'profile_uuid.required' => 'Profile uuid is missing',
            'social_media_link.required' => 'Social media link is missing',
            'social_media_type.required' => 'Social media type is missing',
        ];
    }

    public static function arabicMessages() {
        return [
            'profile_uuid.required' => 'Profile uuid is missing',
            'social_media_link.required' => 'Social media link is missing',
            'social_media_type.required' => 'Social media type is missing',
        ];
    }

}

?>
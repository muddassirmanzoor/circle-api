<?php

namespace App\Helpers;

Class FeedBackValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | FeedBackValidationHelper that contains all the post Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use post processes
      |
     */

    public static function addFeedBackRules() {
        $validate['rules'] = [
            'profile_uuid' => 'required',
         
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }


    public static function englishMessages() {
        return [
            'profile_uuid.required' => 'Profile uuid is missing',
            'logged_in_uuid.required' => 'logged in user uuid is missing',
        ];
    }

    public static function arabicMessages() {
        return [
            'profile_uuid.required' => 'الملف الشخصي uuid مفقود',
            'logged_in_uuid.required' => 'الملف الشخصي uuid مفقود',
        ];
    }

    public static $add_post_image_uuid_rules = array(
        'post_image_uuid' => 'required|unique:post_images',
    );
    public static $add_post_video_uuid_rules = array(
        'post_video_uuid' => 'required|unique:post_videos',
    );

}

?>
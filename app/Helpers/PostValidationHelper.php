<?php

namespace App\Helpers;

Class PostValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | PostValidationHelper that contains all the post Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use post processes
      |
     */

    public static function addPostRules() {
        $validate['rules'] = [
            'profile_uuid' => 'required',
            'media_type' => 'required',
            'post_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function processPostLikeRules() {
        $validate['rules'] = [
            'liked_by_uuid' => 'required',
            'post_uuid' => 'required',
            'type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getProfilePostRules() {
        $validate['rules'] = [
            'profile_uuid' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getFolderPostRules() {
        $validate['rules'] = [
            'folder_uuid' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getProfileSubscriptionRules() {
        $validate['rules'] = [
            'profile_uuid' => 'required',
            'logged_in_uuid' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getPostDetailRules() {
        $validate['rules'] = [
            'post_uuid' => 'required',
            'logged_in_uuid' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function addReportPostRules() {
        $validate['rules'] = [
            'post_uuid' => 'required',
                //'logged_in_uuid' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function updatePostRules() {
        $validate['rules'] = [
            'post_uuid' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getLikesRules() {
        $validate['rules'] = [
            'content_uuid' => 'required',
            'logged_in_uuid' => 'required',
//            'content_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function hideContentRules() {
        $validate['rules'] = [
            'content_uuid' => 'required',
            'profile_uuid' => 'required',
            'logged_in_uuid' => 'required',
            'content_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'profile_uuid.required' => 'Profile uuid is missing',
            'post_uuid.required' => 'post uuid is missing',
            'folder_uuid.required' => 'folder uuid is missing',
            'logged_in_uuid.required' => 'logged in user uuid is missing',
            'media_type.required' => 'media type is missing',
            'post_type.required' => 'post type is missing',
            'liked_by_uuid.required' => 'liked by uuid is missing',
            'type.required' => 'type is missing',
        ];
    }

    public static function arabicMessages() {
        return [
            'profile_uuid.required' => 'الملف الشخصي uuid مفقود',
            'post_uuid.required' => 'الملف الشخصي uuid مفقود',
            'folder_uuid.required' => 'الملف الشخصي uuid مفقود',
            'logged_in_uuid.required' => 'الملف الشخصي uuid مفقود',
            'media_type.required' => 'نوع ملف التعريف مفقود',
            'post_type.required' => 'يجب أن توافق على الشروط والأحكام',
            'liked_by_uuid.required' => 'أحب uuid مفقود',
            'type.required' => ' نوع مفقود',
        ];
    }

    public static $add_post_image_uuid_rules = array(
        'post_image_uuid' => 'required|unique:post_images',
    );
    public static $add_post_video_uuid_rules = array(
        'post_video_uuid' => 'required|unique:post_videos',
    );

    public static function addPostMediaRules() {
        $validate['rules'] = [
            'media_height' => 'required',
            'media_width' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

}

?>

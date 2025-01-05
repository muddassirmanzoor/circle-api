<?php

namespace App\Helpers;

Class StoryValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | StoryValidationHelper that contains all the story Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use story processes
      |
     */

    public static function addStoryRules() {
        $validate['rules'] = [
            'profile_uuid' => 'required',
            'logged_in_uuid' => 'required',
            'media_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function removeStoryRules() {
        $validate['rules'] = [
            'story_uuid' => 'required',
            'logged_in_uuid' => 'required'
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getProfileStoryRules() {
        $validate['rules'] = [
            'profile_uuid' => 'required',
            'logged_in_uuid' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }
    
    public static function addStoryViewsRules() {
        $validate['rules'] = [
            'profile_uuid' => 'required',
            'logged_in_uuid' => 'required',
            'story_uuid' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'story_uuid.required' => 'story uuid is missing',
            'profile_uuid.required' => 'profile uuid is missing',
            'logged_in_uuid.required' => 'logged in uuid is missing',
            'media_type.required' => ' media type is missing',
        ];
    }

    public static function arabicMessages() {
        return [
            'story_uuid.required' => 'الملف الشخصي uuid مفقود',
            'profile_uuid.required' => 'الملف الشخصي uuid مفقود',
            'logged_in_uuid.required' => 'الملف الشخصي uuid مفقود',
            'media_type.required' => ' نوع الوسائط مفقود',
        ];
    }

    public static $add_story_rules = array(
        'story_uuid' => 'required|unique:stories',
    );
    public static $add_story_location_rules = array(
        'story_location_uuid' => 'required|unique:story_locations',
    );

}

?>

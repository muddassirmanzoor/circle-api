<?php

namespace App\Helpers;

Class FolderValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | PostValidationHelper that contains all the folder Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use folder processes
      |
     */

    public static function addFolderRules() {
        $validate['rules'] = [
            'profile_uuid' => 'required',
            'name' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getFolderRules() {
        $validate['rules'] = [
            'profile_uuid' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }
    
    public static function updateFolderRules() {
        $validate['rules'] = [
            'profile_uuid' => 'required',
            'folder_uuid' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }
    
    public static function deleteFolderRules() {
        $validate['rules'] = [
            'profile_uuid' => 'required',
            'folder_uuid' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'profile_uuid.required' => 'Profile uuid is missing',
            'name.required' => 'Media type is missing',
            'logged_in_uuid.required' => 'Login uuid is missing',
            'subscribed_uuid.required' => 'Subscribed uuid is missing',
            'currency.required' => 'Currency is missing',
            'freelancer_uuid.required' => 'Freelancer UUid is missing',
        ];
    }

    public static function arabicMessages() {
        return [
            'profile_uuid.required' => 'الملف الشخصي uuid مفقود',
            'name.required' => 'نوع ملف التعريف مفقود',
            'logged_in_uuid.required' => 'Login uuid is missing',
            'subscribed_uuid.required' => 'Subscribed uuid is missing',
            'currency.required' => 'Currency is missing',
            'freelancer_uuid.required' => 'Freelancer UUid is missing',
        ];
    }

    public static function buyPremiumFolder() {
        $validate['rules'] = [
            'folder_uuid' => 'required',
            'logged_in_uuid' => 'required',
            'currency' => 'required',
            
//            'transaction_id' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }
}

?>
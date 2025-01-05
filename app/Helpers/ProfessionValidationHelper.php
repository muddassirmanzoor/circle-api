<?php

namespace App\Helpers;

Class ProfessionValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | PackageValidationHelper that contains all the Package Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use Package processes
      |
     */


    public static function englishMessages() {
        return [
            'logged_in_uuid.required' => 'Logged in uuid is required',
        ];
    }

    public static function arabicMessages() {
        return [
            'logged_in_uuid.required' => 'uuid لحسابهم الخاص هو المطلوب',
        ];
    }

    public static function getAllProfessionsRules() {
       $validate['rules'] = [
            'logged_in_uuid' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate; 
    }
}

?>

<?php

namespace App\Helpers;

Class EmailValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | EmailValidationHelper that contains all the email Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use email processes
      |
     */

    public static function sendEmailRules() {
        $validate['rules'] = [
            'subject' => 'required',
            'email' => 'required',
//            'code' => 'required',
            'message' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'subject.required' => 'Email subject is missing',
        ];
    }

    public static function arabicMessages() {
        return [
            'subject.required' => 'Email subject is missing',
        ];
    }

}

?>
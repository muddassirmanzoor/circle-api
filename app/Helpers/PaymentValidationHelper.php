<?php

namespace App\Helpers;

Class PaymentValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | PaymentValidationHelper that contains all the payment Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use payment processes
      |
     */

    public static function addRegistrationIds() {
        $validate['rules'] = [
            'registration_id' => 'required',
            'card_last_digits' => 'required',
            'expiry_year' => 'required',
            'expiry_month' => 'required',
            'logged_in_uuid' => 'required',
            'login_user_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'registration_id.required' => 'Registration id is missing',
            'login_user_type.required' => 'Login user type is missing',
            'logged_in_uuid.required' => 'Login user uuid is missing',
        ];
    }

    public static function arabicMessages() {
        return [
            'registration_id.required' => 'Registration id is missing',
            'login_user_type.required' => 'Login user type is missing',
            'logged_in_uuid.required' => 'Login user uuid is missing',
        ];
    }

}

?>

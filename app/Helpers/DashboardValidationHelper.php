<?php

namespace App\Helpers;

Class DashboardValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | DashboardValidationHelper that contains all the dashboard Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      |
     */

    public static function englishMessages() {
        return [
            'login_user_type.required' => 'login user type is required',
            'logged_in_uuid.required' => 'logged in uuid is required',
            'local_timezone.required' => 'local timezone is required',
        ];
    }

    public static function arabicMessages() {
        return [
            'login_user_type.required' => 'login user type is required',
            'logged_in_uuid.required' => 'logged in uuid is required',
            'local_timezone.required' => 'local timezone is required',];
    }

    public static function getDashboardCountRules() {
        $validate['rules'] = [
            'login_user_type' => 'required',
            'logged_in_uuid' => 'required',
            'local_timezone' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

}

?>
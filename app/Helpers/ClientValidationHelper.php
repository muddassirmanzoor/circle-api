<?php

namespace App\Helpers;

Class ClientValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | ClientValidationHelper that contains all the client Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use client processes
      |
     */

    public static function addClientRules() {
        $validate['rules'] = [
            'client_uuid' => 'required',
            'freelancer_id' => 'required',
            'customer_id' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getClientDetails() {
        $validate['rules'] = [
//            'client_uuid' => 'required',
            'logged_in_uuid' => 'required',
            'local_timezone' => 'required',
            'type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'client_uuid.required' => 'Client uuid is missing',
            'freelancer_uuid.required' => 'Freelancer uuid is missing',
            'customer_uuid.required' => 'Customer uuid is missing',
            'logged_in_uuid.required' => 'Login user uuid is missing',
            'local_timezone.required' => 'Local timezone info is missing',
            'save_client_error' => 'Client could not be added',
            'save_client_success' => 'Client added successfully',
        ];
    }

    public static function arabicMessages() {
        return [
            'client_uuid.required' => 'Client uuid is missing',
            'freelancer_uuid.required' => 'Freelancer uuid is missing',
            'customer_uuid.required' => 'Customer uuid is missing',
            'logged_in_uuid.required' => 'Login user uuid is missing',
            'local_timezone.required' => 'Local timezone info is missing',
            'save_client_error' => 'Client could not be added',
            'save_client_success' => 'Client added successfully',
        ];
    }

}

?>

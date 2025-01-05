<?php

namespace App\Helpers;

Class CustomerChecksValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | CustomerChecksValidationHelper that contains all the customer Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use customer processes
      |
     */

    public static function checkCustomerAppointmentRules() {
        $validate['rules'] = [
            'date' => 'required',
            'from_time' => 'required',
            'to_time' => 'required',
            'customer_id' => 'required',
            'logged_in_uuid' => 'required',
            'local_timezone' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'from_time.required' => 'Start time is missing',
            'to_time.required' => 'End time is missing',
            'invalid_data' => 'Invalid data provided',
            'invalid_customer_uuid' => 'Customer data is invalid',
        ];
    }

    public static function arabicMessages() {
        return [
            'from_time.required' => 'Start time is missing',
            'to_time.required' => 'End time is missing',
            'invalid_data' => 'Invalid data provided',
            'invalid_customer_uuid' => 'Customer data is invalid',
        ];
    }

}

?>

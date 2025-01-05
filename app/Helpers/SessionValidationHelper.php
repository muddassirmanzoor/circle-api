<?php

namespace App\Helpers;

Class SessionValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | SessionValidationHelper that contains all the session Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use session processes
      |
     */

    public static function addSessionRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
            'customer_uuid' => 'required',
            'customer_name' => 'required',
            'session_date' => 'required',
            'from_time' => 'required',
            'to_time' => 'required',
            'title' => 'required',
            'service_uuid' => 'required',
            'address' => 'required',
            'lat' => 'required',
            'lng' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'freelancer_uuid.required' => 'Freelancer uuid is missing',
            'customer_uuid.required' => 'Customer uuid is missing',
            'customer_name.required' => 'Customer name is missing',
            'session_date.required' => 'Session date is missing',
            'from_time.required' => 'Start time is missing',
            'to_time.required' => 'End time is missing',
            'title.required' => 'Title is missing',
            'service_uuid.required' => 'Service uuid is missing',
            'address.required' => 'Address is missing',
            'lat.required' => 'Latitude is missing',
            'lng.required' => 'Longitude is missing',
        ];
    }

    public static function arabicMessages() {
        return [
            'freelancer_uuid.required' => 'Freelancer uuid is missing',
            'customer_uuid.required' => 'Customer uuid is missing',
            'customer_name.required' => 'Customer name is missing',
            'session_date.required' => 'Session date is missing',
            'from_time.required' => 'Start time is missing',
            'to_time.required' => 'End time is missing',
            'title.required' => 'Title is missing',
            'service_uuid.required' => 'Service uuid is missing',
            'address.required' => 'Address is missing',
            'lat.required' => 'Latitude is missing',
            'lng.required' => 'Longitude is missing',
        ];
    }

}

?>
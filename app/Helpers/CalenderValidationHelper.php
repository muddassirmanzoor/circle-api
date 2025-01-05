<?php

namespace App\Helpers;

Class CalenderValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | CalenderValidationHelper that contains all the calender Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use calender processes
      |
     */

    public static function getFreelancerCalenderRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
            'local_timezone' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'freelancer_uuid.required' => 'Freelancer uuid is missing',
            'local_timezone.required' => 'Timezone info is required',
        ];
    }

    public static function arabicMessages() {
        return [
            'freelancer_uuid.required' => 'uuid لحسابهم الخاص هو المطلوب',
            'local_timezone.required' => 'Timezone info is required',
        ];
    }

}

?>
<?php

namespace App\Helpers;

Class ScheduleValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | ScheduleValidationHelper that contains all the Freelancer Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use Freelancer processes
      |
     */

    public static function freelancerAddScheduleRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
            'local_timezone' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getFreelancerScheduleRules() {
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
            'freelancer_uuid.required' => 'Freelancer uuid is required',
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

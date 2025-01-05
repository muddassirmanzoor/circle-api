<?php

namespace App\Helpers;

Class FreelancerScheduleValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | FreelancerScheduleValidationHelper that contains all the Freelancer scheduler Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use Freelancer schedule processes
      |
     */

    public static function schedulerRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
            // 'freelancer_category_uuid' => 'required',
            'local_timezone' => 'required',
            'date' => 'required'
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'freelancer_uuid.required' => 'Freelancer uuid is required',
            'freelancer_category_uuid.required' => 'Freelancer category uuid is required',
            'local_timezone.required' => 'Local timezone is required',
            'date.required' => 'Date is required'
        ];
    }

    public static function arabicMessages() {
        return [
            'freelancer_uuid.required' => 'Freelancer uuid is required',
            'freelancer_category_uuid.required' => 'Freelancer category uuid is required',
            'local_timezone.required' => 'Local timezone is required',
            'date.required' => 'Date is required'
        ];
    }

}

?>

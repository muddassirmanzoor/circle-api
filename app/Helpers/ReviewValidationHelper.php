<?php

namespace App\Helpers;

Class ReviewValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | ReviewValidationHelper that contains all the activity Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use activity processes
      |
     */

    public static function addReviewRules() {
        $validate['rules'] = [
            'customer_uuid' => 'required',
            'freelancer_uuid' => 'required',
//            'rating' => 'required'
            'type' => 'required'
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function addReviewReplyRules() {
        $validate['rules'] = [
            'review_uuid' => 'required',
            'profile_uuid' => 'required',
            'reply' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getFreelancerReviewRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required'
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getSingleReviewRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
            'review_uuid' => 'required'
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'customer_uuid.required' => 'Customer uuid is missing',
            'freelancer_uuid.required' => 'Freelancer uuid is missing',
            'rating.required' => 'Star Rating is missing',
            'type.required' => 'Type is missing',
            'review_uuid.required' => 'Review uuid is missing',
            'profile_uuid.required' => 'Prifile uuid is missing',
            'review_uuid.required' => 'Review uuid is missing',
        ];
    }

    public static function arabicMessages() {
        return [
            'customer_uuid.required' => 'معرف المستخدم مفقود',
            'freelancer_uuid.required' => 'معرف المستخدم الحر مفقود',
            'rating.required' => 'تصنيف النجوم مفقود',
            'type.required' => 'Type is missing',
            'review_uuid.required' => 'Review uuid is missing',
            'profile_uuid.required' => 'Prifile uuid is missing',
            'review_uuid.required' => 'uuid للمراجعة مفقود',
        ];
    }

}

?>

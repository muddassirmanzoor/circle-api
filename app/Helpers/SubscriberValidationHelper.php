<?php

namespace App\Helpers;

Class SubscriberValidationHelper {

    public static function processFollowingRules() {
        $validate['rules'] = [
            'customer_uuid' => 'required',
            'freelancer_uuid' => 'required',
            'follow_type' => 'required'
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getSubscriberRules() {
        $validate['rules'] = [
            //'logged_in_uuid' => 'required',
            'freelancer_uuid' => 'required'
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function processSubscriptionRules() {
        $validate['rules'] = [
            'logged_in_uuid' => 'required',
            'login_user_type' => 'required',
            'type' => 'required',
            'subscription_uuid' => 'required'
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'customer_uuid.required' => 'Customer uuid is missing',
            'freelancer_uuid.required' => 'Freelancer uuid is missing',
            'follow_type.required' => 'Follow type is missing'
        ];
    }

    public static function arabicMessages() {
        return [
            'customer_uuid.required' => 'معرف المستخدم مفقود',
            'freelancer_uuid.required' => 'معرف المستخدم الحر مفقود',
            'follow_type.required' => 'اتبع نوع مفقود'
        ];
    }

}

?>

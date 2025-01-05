<?php

namespace App\Helpers;

Class CategoryValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | CategoryValidationHelper that contains all the category Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use category processes
      |
     */

    public static function saveCategoryRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
            'category_uuid' => 'required',
//            'currency' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function saveFreelancerCategoryRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
            'category_uuid' => 'required',
            'currency' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getCategoryRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
            'currency' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getActiveCategoryRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
            'currency' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getCategoryDetailsRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
            'freelancer_category_uuid' => 'required',
            'currency' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function updateCategoryRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
            'freelancer_category_uuid' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'freelancer_uuid.required' => 'Freelancer uuid is missing',
            'category_uuid.required' => 'Category uuid is missing',
        ];
    }

    public static function arabicMessages() {
        return [
            'freelancer_uuid.required' => 'معرف المستخدم الحر مفقود',
            'category_uuid.required' => 'فئة معرف المستخدم مفقودة',
        ];
    }

}

?>
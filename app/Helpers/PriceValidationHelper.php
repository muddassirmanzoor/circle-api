<?php

namespace App\Helpers;

Class PriceValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | PriceValidationHelper that contains all the price methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use price processes
      |
     */

    public static function saveFreelancerPricingRules() {
        $validate['rules'] = [
            'freelancer_id' => 'required',
            'freelancer_category_uuid' => 'required',
            'price' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'freelancer_uuid.required' => 'Freelancer uuid is missing',
            'freelancer_category_uuid.required' => 'Freelancer Category uuid is missing',
            'empty_data' => 'No data provided',
            'save_price_error' => 'Price could not be saved',
        ];
    }

    public static function arabicMessages() {
        return [
            'freelancer_uuid.required' => 'معرف المستخدم الحر مفقود',
            'freelancer_category_uuid.required' => 'مترجم مستقل لحساب فئة المستخدم مفقود',
            'price.required' => 'السعر مطلوب',
            'empty_data' => 'لم تقدم بيانات',
            'save_price_error' => 'لا يمكن حفظ السعر',
        ];
    }

}

?>

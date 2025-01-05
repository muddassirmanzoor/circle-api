<?php

namespace App\Helpers;

Class LocationValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | LocationValidationHelper that contains all the Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use validation processes
      |
     */

    public static function addLocationRules() {
        $validate['rules'] = [
            'address' => 'required',
//            'street_number' => 'required',
//            'route' => 'required',
//            'city' => 'required',
            'state' => 'required',
            'country' => 'required',
//            'zip_code' => 'required',
            'lat' => 'required',
            'lng' => 'required',
//            'location_id' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function updateFreelancerLocationRules() {
        $validate['rules'] = [
//            'freelancer_location_uuid' => 'required',
            'address' => 'required',
//            'street_number' => 'required',
//            'route' => 'required',
//            'city' => 'required',
            'state' => 'required',
            'country' => 'required',
//            'zip_code' => 'required',
            'lat' => 'required',
            'lng' => 'required',
//            'location_id' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function validationMessages() {
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        $validate['rules'] = [];
        return $validate;
    }

    public static function addPostLocationRules() {
        $validate['rules'] = [
            'address' => 'required',
//            'street_number' => 'required',
//            'route' => 'required',
//            'city' => 'required',
//            'state' => 'required',
//            'country' => 'required',
//            'zip_code' => 'required',
            'lat' => 'required',
            'lng' => 'required',
//            'location_id' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }
    
    public static function getCountriesRules() {
        $validate['rules'] = [
            'logged_in_uuid' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }
    
//    public static function getCitiesRules() {
//        $validate['rules'] = [
//            'logged_in_uuid' => 'required',
//            'country' => 'required'
//        ];
//        $validate['message_en'] = self::englishMessages();
//        $validate['message_ar'] = self::arabicMessages();
//        return $validate;
//    }
    

    public static function englishMessages() {
        return [
            'freelancer_location_uuid.required' => 'Freelancer location uuid is missing',
            'address.required' => 'Address is missing',
            'street_number.required' => 'Street number is missing',
            'route.required' => 'Address route is missing',
            'city.required' => 'Address city is missing',
            'state.required' => 'Address state is missing',
            'country.required' => 'Address country is missing',
            'zip_code.required' => 'Address zip code is missing',
            'lat.required' => 'Address latitude is missing',
            'lng.required' => 'Address logitude is missing',
            'location_id.required' => 'Location id is required',
        ];
    }

    public static function arabicMessages() {
        return [
            'freelancer_location_uuid.required' => 'Freelancer location uuid is missing',
            'address.required' => 'العنوان مفقود',
            'street_number.required' => 'رقم الشارع مفقود',
            'route.required' => 'مسار العنوان مفقود',
            'city.required' => 'مدينة العنوان مفقودة',
            'state.required' => 'حالة العنوان مفقودة',
            'country.required' => 'بلد العنوان مفقود',
            'zip_code.required' => 'عنوان الرمز البريدي مفقود',
            'lat.required' => 'خط عرض العنوان مفقود',
            'lng.required' => 'عنوان السجل مفقود',
            'location_id.required' => 'معرف الموقع مطلوب',
        ];
    }

}

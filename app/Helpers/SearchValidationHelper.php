<?php

namespace App\Helpers;

Class SearchValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | SearchValidationHelper that contains all the search Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use search processes
      |
     */

    public static function searchRules() {
        $validate['rules'] = [
            'search_type' => 'required',
            'search_query' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function searchClientRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
            'search_type' => 'required',
            'search_query' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'freelancer_uuid.required' => 'Freelancer uuid is required',
            'search_type.required' => 'Search type is required',
            'search_query.required' => 'Search data is missing',
        ];
    }

    public static function arabicMessages() {
        return [
            'freelancer_uuid.required' => 'Freelancer uuid is required',
            'search_type.required' => 'Search type is required',
            'search_query.required' => 'Search data is missing',
        ];
    }

}

?>
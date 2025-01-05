<?php

namespace App\Helpers;

Class WalkinCustomerValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | WalkinCustomerValidationHelper that contains all the customer Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use customer processes
      |
     */

    public static function addCustomerRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
            'first_name' => 'required',
            //'last_name' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'freelancer_uuid.required' => 'Freelancer uuid is missing',
            'first_name.required' => 'First name is missing',
            'last_name.required' => 'Last name is missing',
            'save_success' => 'Walkin customer saved successfully',
            'save_error' => 'Walkin customer could not be saved',
        ];
    }

    public static function arabicMessages() {
        return [
            'freelancer_uuid.required' => 'Freelancer uuid is missing',
            'first_name.required' => 'الاسم مفقود',
            'last_name.required' => 'الاسم مفقود',
            'save_success' => 'Walkin customer saved successfully',
            'save_error' => 'Walkin customer could not be saved'
        ];
    }

}

?>

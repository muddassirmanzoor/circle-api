<?php

namespace App\Helpers;

Class MoyasarValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | ChatValidationHelper that contains all the chat Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use chat processes
      |
     */

    /**
     * Description of ChatValidationHelper
     *
     * @author ILSA Interactive
     */
    public static function createFormRules() {
        $validate['rules'] = [
            'amount' => 'required|min:1',
            'currency' => 'required',
            'logged_in_uuid' => 'required',
            'description' => 'required',
            'expired_at' => 'nullable',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getFormRules() {
        $validate['rules'] = [
            'logged_in_uuid' => 'required',
            'moyasar_web_form_uuid' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function updateFormRules() {
        $validate['rules'] = [
            'logged_in_uuid' => 'required',
            'moyasar_web_form_uuid' => 'required',
            'amount' => 'nullable',
            'currency' => 'nullable',
            'description' => 'nullable',
            'expired_at' => 'nullable',
            'status' => 'nullable|in:pending,paid,failed',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'amount.required' => 'Amount is missing',
            'amount.min' => 'Minimum amount is 1',
            'moyasar_web_form_uuid.required' => 'Web form id is missing',
            'form_not_found' => 'Web form not found',
            'currency.required' => 'Currency is missing',
            'generalError' => ' Oops! Something went wrong, Please Try Again',
            'logged_in_uuid.required' => 'Login user id is required',
            'description.required' => 'Description is required',
            'successful_request' => 'Request successful!',
        ];
    }

    public static function arabicMessages() {
        return [
            'amount.required' => 'Amount is missing',
            'amount.min' => 'Minimum amount is 1',
            'moyasar_web_form_uuid.required' => 'Web form id is missing',
            'form_not_found' => 'Web form not found',
            'currency.required' => 'Currency is missing',
            'generalError' => ' Oops! Something went wrong, Please Try Again',
            'logged_in_uuid.required' => 'Login user id is required',
            'description.required' => 'Description is required',
            'successful_request' => 'طلب ناجح!',
        ];
    }

}

?>

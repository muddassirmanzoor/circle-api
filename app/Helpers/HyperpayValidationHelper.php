<?php

namespace App\Helpers;

Class HyperpayValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | HyperpayValidationHelper that contains all the payment Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use payment processes
      |
     */

    public static function prepareCheckoutRules() {
        $validate['rules'] = [
            'amount' => 'required',
            'currency' => 'required',
            'paymentType' => 'required',
            'logged_in_uuid' => 'required',
            'email' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }
    public static function transactionStatusRules() {
        $validate['rules'] = [
            'resource_path' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'amount.required' => 'Amount is missing',
        ];
    }

    public static function arabicMessages() {
        return [
            'amount.required' => 'Amount is missing',
        ];
    }

}

?>

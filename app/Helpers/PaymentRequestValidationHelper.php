<?php

namespace App\Helpers;

Class PaymentRequestValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | PaymentRequestValidationHelper that contains all the payment request Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use payment request processes
      |
     */

    public static function preparePaymentRequestRules() {
        $validate['rules'] = [
            'amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'currency' => 'required',
            'logged_in_uuid' => 'required'
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'amount.required' => 'Amount is missing',
            'amount.regex' => 'Amount is not in format',
            'successful_request' => 'Request successful!',
            'add_payment_request_error' => 'Payment Request could not be added',
            'bank_detail_error' => 'Bank Details not available , Please add first'
        ];
    }

    public static function arabicMessages() {
        return [
            'amount.required' => 'Amount is missing',
            'amount.regex' => 'Amount is not in format',
            'successful_request' => 'Request successful!',
            'add_payment_request_error' => 'Payment Request could not be added',
            'bank_detail_error' => 'Bank Details not available , Please add first'

        ];
    }

}

?>

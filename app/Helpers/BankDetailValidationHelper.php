<?php

namespace App\Helpers;

Class BankDetailValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | ClassValidationHelper that contains all the Class Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use Class processes
      |
     */

    public static function englishMessages() {
        return [
            'freelancer_uuid.required' => 'Freelancer uuid is required',
            'account_name.required' => 'Account name is required',
            'iban_account_number.required' => 'IBAN account number is required',
            'bank_name.required' => 'Bank name is required',
            'swift_code.required' => 'Swift code is required',
            'logged_in_uuid.required' => 'Logged in uuid is required',
            'uuid.required' => 'Uuid is required',
            'type.required' => 'Type is required',
            'local_timezone.required' => 'Local timezone is required',
            'billing_address.required' => 'Billing Address is required',
            'post_code.required' => 'Please enter Post Code or Zip Code',
        ];
    }

    public static function arabicMessages() {
        return [
            'freelancer_uuid.required' => 'Freelancer uuid is required',
            'location_type.required' => 'Please add location type ',
            'account_name.required' => 'Account name is required',
            'iban_account_number.required' => 'IBAN account number is required',
            'bank_name.required' => 'Bank name is required',
            'swift_code.required' => 'Swift code is required',
            'logged_in_uuid.required' => 'Logged in uuid is required',
            'uuid.required' => 'Uuid is required',
            'type.required' => 'Type is required',
            'local_timezone.required' => 'Local timezone is required',
        ];
    }

    public static function bankDetailRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
            'account_name' => 'required_if:location_type,==,UK',
            'iban_account_number' => 'required',
            'bank_name' => 'required_if:location_type,==,KSA',
            'billing_address' => 'required',
            'post_code' => 'required',
            'location_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    // overviewBankDetailRules => this function has same rules for getOverviewBankDetail and getAllTransactions.
    public static function overviewBankDetailRules() {
        $validate['rules'] = [
            //'freelancer_uuid' => 'required',
            'login_user_type' => 'required',
            'logged_in_uuid' => 'required',
            'local_timezone' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getWithdrawRequestsRules() {
        $validate['rules'] = [
            //'freelancer_uuid' => 'required',
            'login_user_type' => 'required',
            'logged_in_uuid' => 'required',
            'local_timezone' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getTransactionDetailRules() {
        $validate['rules'] = [
            'purchase_uuid' => 'required',
            'logged_in_uuid' => 'required',
            'login_user_type' => 'required',
            //'type' => 'required',
            'local_timezone' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

}

?>
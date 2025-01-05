<?php

namespace App\Helpers;

use App\PaymentDue;
use Illuminate\Support\Facades\Validator;
use App\PaymentRequest;
use App\BankDetail;
use DB;
Class PaymentRequestHelper {
    /*
      |--------------------------------------------------------------------------
      | PaymentRequestHelper that contains payment request related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use payment request processes
      |
     */

    /**
     * Description of PaymentRequestHelper
     *
     * @author ILSA Interactive
     */
    public static function processPaymentRequest($inputs) {
        $validation = Validator::make($inputs, PaymentRequestValidationHelper::preparePaymentRequestRules()['rules'], PaymentRequestValidationHelper::preparePaymentRequestRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $validate_bank_details = BankDetail::getBankDetail('freelancer_uuid',$inputs['logged_in_uuid']);
        if(!$validate_bank_details){
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(PaymentRequestValidationHelper::preparePaymentRequestRules()['message_' . strtolower($inputs['lang'])]['bank_detail_error']);
        }
        $request_inputs = self::processPreparePaymentRequestInput($inputs);
    
        $response_payment_request = PaymentRequest::savePaymentRequest($request_inputs);
        if (!$response_payment_request) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(PaymentRequestValidationHelper::preparePaymentRequestRules()['message_' . strtolower($inputs['lang'])]['add_payment_request_error']);
        }

        DB::commit();
        return CommonHelper::jsonSuccessResponseWithoutData(PaymentRequestValidationHelper::preparePaymentRequestRules()['message_' . strtolower($inputs['lang'])]['successful_request']);
    }

    public static function processPreparePaymentRequestInput($input) {
        $deduction_amount = strtolower($input['currency']) == 'pound' ? CommonHelper::$circle_commission['withdraw_pound_fee'] : CommonHelper::$circle_commission['withdraw_sar_fee'];
        $data = array(
            'user_uuid' => !empty($input['logged_in_uuid']) ? $input['logged_in_uuid'] : null,
            'requested_amount' => !empty($input['amount']) && $input['amount'] > 0 ? $input['amount'] : 0,
            'deductions' => $deduction_amount,
            'final_amount' => !empty($input['amount']) && $input['amount'] > 0 ? $input['amount'] - $deduction_amount : 0,
            'currency' => !empty($input['currency']) ? $input['currency'] : null,
            'notes_from_freelancer' => !empty($input['notes_from_freelancer']) ? $input['notes_from_freelancer'] : null
        );
        return $data;
    }
}

?>
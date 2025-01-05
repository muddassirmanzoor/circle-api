<?php

namespace App\Helpers;

use App\Appointment;
use App\ClassBooking;
use App\Classes;
use App\Subscription;
use App\SubscriptionMonthlyEntries;
use App\Traits\EarningAmount;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Validator;
use App\Registration;

Class PaymentHelper {
    /*
      |--------------------------------------------------------------------------
      | PaymentHelper that contains payment  related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use payment processes
      |
     */

    /**
     * Description of PaymentHelper
     *
     * @author ILSA Interactive
     */
    public static function getFreelancerBalance($inputs){
        $validation = Validator::make($inputs,AppointmentValidationHelper::freelancerBalanceRules()['rules'], AppointmentValidationHelper::freelancerBalanceRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $freelancerId = CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);
       return  CommonHelper::jsonSuccessResponse('Total Balance',[
           // 'pendingBalance'=> number_format((float) self::pendingBalance('freelancer_id',$freelancerId,'>'), 2, '.', ''),
            'pendingBalance'=> number_format((float) EarningAmount::getPendingAmount($freelancerId), 2, '.', ''),
            'availableBalance'=>number_format((float) EarningAmount::avaliableAmount($freelancerId), 2, '.', '')
        ]);
    }

    public static function pendingBalance($column,$value,$condition){
        $appointmentBalance = self::appointmentBalance($column,$value,$condition);
        $classBalance = self::classBalance($column,$value,$condition);
        $subscriptionBalance = self::subscriptionBalance('subscribed_id',$value,$condition);

        return $appointmentBalance + $classBalance + $subscriptionBalance;
    }



    public static function appointmentBalance($column,$value,$condition){
      return Appointment::getBalance($column,$value,$condition);
    }

    public static function subscriptionBalance($column,$value,$condition){
        $subscription = Subscription::getBalance($column,$value,$condition);
        $pendingAmount = 0.0;
        foreach ($subscription as $subscribe){
            $pendingAmount =$pendingAmount + SubscriptionMonthlyEntries::getMonthlyRecords($subscribe);
        }
        return $pendingAmount;
    }

    public static function classBalance($column,$value,$condition){
       $classes = Classes::getBalance($column,$value,$condition);

        $total = 0.00;
       foreach ($classes as $class){
           foreach ($class['schedule'] as $schdule){
               if(!empty($schdule['class_bookings'])){
                   foreach ($schdule['class_bookings'] as $booking){
                       $total = $total + $booking['paid_amount'];
                   }
               }

           }
       }
        return $total;
    }



    public static function saveRegistrationId($inputs, $registration_id = null) {
        $inputs['registration_id'] = $registration_id;
        $inputs['card_last_digits'] = !empty($inputs['card_info']->last4Digits) ? $inputs['card_info']->last4Digits : null;
        $inputs['expiry_month'] = !empty($inputs['card_info']->expiryMonth) ? $inputs['card_info']->expiryMonth : null;
        $inputs['expiry_year'] = !empty($inputs['card_info']->expiryYear) ? $inputs['card_info']->expiryYear : null;
        $inputs['card_holder'] = !empty($inputs['card_info']->holder) ? $inputs['card_info']->holder : null;
        $inputs['card_country'] = !empty($inputs['card_info']->binCountry) ? $inputs['card_info']->binCountry : null;
        $validation = Validator::make($inputs, PaymentValidationHelper::addRegistrationIds()['rules'], PaymentValidationHelper::addRegistrationIds()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return ['success' => false, 'message' => $validation->errors()->first()];
        }
        $get_existing = Registration::checkRegistration($inputs);
        if ($get_existing) {
            return ['success' => true, 'message' => 'Your payment information already exists'];
        }
        $registration_inputs = self::makeRegistrationInputs($inputs, $registration_id);
        $save_id = Registration::saveData($registration_inputs);
        if (empty($save_id)) {
            return ['success' => false, 'message' => 'Your payment information could not be saved'];
        }
        return ['success' => true, 'message' => 'Your payment information saved'];
    }

    public static function makeRegistrationInputs($inputs) {
        $registration_inputs = [];
        $registration_inputs['registration_id'] = $inputs['registration_id'];
        $registration_inputs['profile_uuid'] = $inputs['logged_in_uuid'];
        $registration_inputs['profile_type'] = $inputs['login_user_type'];
        $registration_inputs['card_last_digits'] = $inputs['card_last_digits'];
        $registration_inputs['expiry_month'] = $inputs['expiry_month'];
        $registration_inputs['expiry_year'] = $inputs['expiry_year'];
        $registration_inputs['card_holder'] = $inputs['card_holder'];
        $registration_inputs['card_country'] = $inputs['card_country'];
        return $registration_inputs;
    }

    public static function sendOrderSplitAuthRequest(){

        $client = new Client();
        $data = [
            'json' => [
                'email' => 'philip@al-anazi.com',
                'password' => '12fg345df'
            ],
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ];

        $res = $client->request('POST', 'https://splits.sandbox.hyperpay.com/api/v1/login', $data);
        $res = $res->getBody()->getContents();

        return json_decode($res, true);
    }

    public static function sendInquiryOrderSplitRequest($req_uuid, $token){

        $auth_token = "Bearer ". $token;
        $headers = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => $auth_token
            ]
        ];

        $client = new Client();
        $res = $client->request('GET', 'https://splits.sandbox.hyperpay.com/api/v1/orders/'.$req_uuid, $headers);
        $res = $res->getBody()->getContents();

        return json_decode($res, true);
    }

}

?>

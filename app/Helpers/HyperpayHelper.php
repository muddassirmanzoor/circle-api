<?php

namespace App\Helpers;

use App\TransactionLog;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

Class HyperpayHelper {
    /*
      |--------------------------------------------------------------------------
      | HyperpayHelper that contains payment  related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use payment processes
      |
     */

    /**
     * Description of HyperpayHelper
     *
     * @author ILSA Interactive
     */
    public static function prepareCheckout($inputs) {
        $validation = Validator::make($inputs, HyperpayValidationHelper::prepareCheckoutRules()['rules'], HyperpayValidationHelper::prepareCheckoutRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $trans_log = TransactionLog::create($inputs); 
        // $trans_log = TransactionLog::create(['user_uuid' => $inputs['logged_in_uuid'], 'amount' => $inputs['amount'], 'currency' => $inputs['currency']]);
        $inputs['transaction_log_uuid'] = $trans_log->transaction_log_uuid ?? '';
        $request_inputs = self::processPrepareCheckoutInputs($inputs);
        $url = config('general.globals.hyperpay_full_address');
        $request_response = self::sendHyperpayRequest($url, 'post', $request_inputs);
        TransactionLog::where('id', $trans_log->id)->update(['request_params' => $request_inputs, 'gateway_response' => $request_response]);
        $decoded_response = json_decode($request_response);
        
        if (empty($decoded_response)) {
            return CommonHelper::jsonErrorResponse('Your transaction could not be initiated');
        }
        if (!empty($decoded_response->result->code) && !in_array($decoded_response->result->code, HyperpayResponseCodesHelper::$success_codes)) {
            return CommonHelper::jsonErrorResponse($decoded_response->result->description);
        }
        $response = self::prepareCheckoutResponse($decoded_response);
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function processPrepareCheckoutInputs($inputs) {
        $data = [];
        $registration_ids = \App\Registration::getRegistrations('profile_uuid', $inputs['logged_in_uuid']);
        $add_string = '';
        if (!empty($registration_ids)) {
            foreach ($registration_ids as $key => $id) {
                $add_string .= "&registrations[" . $key . "].id=" . $id['registration_id'];
//                "&registrations[0].id=8ac7a4a17300cab301730a5bf2423239" .
//                "&registrations[1].id=8ac7a4a17300cab301730a5bf3a23245" .
            }
        }
        $amount = !empty($inputs['amount']) ? $inputs['amount'] : 0;
        $currency = 'SAR';
        $entity_id = config('general.globals.hyperpay_entity_id');
        if (isset($inputs['currency']) && !empty($inputs['currency'])){
            if (strtolower($inputs['currency']) == 'pound' && $amount > 0){
                $amount = CommonHelper::getConvertedCurrency($amount, 'Pound', 'SAR');
                $amount = round($amount, 2);
                /*$currency = 'GBP';
                $entity_id = config('general.globals.hyperpay_pound_entity_id');*/
            }
            /*else{
                $currency = 'SAR';
                $entity_id = config('general.globals.hyperpay_entity_id');
            }*/
        }
        $amount = sprintf('%.2f', $amount);
        $paymentType = !empty($inputs['paymentType']) ? $inputs['paymentType'] : 'DB';
        $notificationUrl = !empty($inputs['notificationUrl']) ? $inputs['notificationUrl'] : '';
        $recurring_type = !empty($inputs['recurring_type']) ? strtoupper($inputs['recurring_type']) : 'REGISTRATION_BASED';
        $data = "entityId=" . $entity_id .
                "&amount=" . round($amount, 2) .
                "&currency=" . $currency .
                "&paymentType=" . $paymentType .
                "&notificationUrl=" . $notificationUrl .
                "&recurringType=" . $recurring_type .
                "&merchantTransactionId=" . $inputs['transaction_log_uuid'] ?? '' .
                "&customer.email=" . $inputs['email'] . $add_string;
//                "&testMode=" . 'EXTERNAL' . $add_string;
        return $data;
    }

    public static function prepareCheckoutResponse($data = []) {
        $response = [];
        if (!empty($data)) {
            $response['id'] = !empty($data->id) ? $data->id : '';
            $response['buildNumber'] = !empty($data->buildNumber) ? $data->buildNumber : '';
            $response['ndc'] = !empty($data->ndc) ? $data->ndc : '';
        }
        return $response;
    }

    public static function sendHyperpayRequest($url = null, $request_type = 'get', $data = []) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization:Bearer ' . config('general.globals.hyperpay_access_token')));

        if (strtolower($request_type) == 'post') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } elseif (strtolower($request_type) == 'get') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // this should be set to true in production
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseData = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        return $responseData;
    }

    public static function checkTransactionStatus($inputs) {
        $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
        $validation = Validator::make($inputs, HyperpayValidationHelper::transactionStatusRules()['rules'], HyperpayValidationHelper::transactionStatusRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return ['success' => false, 'message' => $validation->errors()->first()];
        }
        $entity_id = config('general.globals.hyperpay_entity_id');
        /*if (isset($inputs['currency']) && !empty($inputs['currency'])){
            if (strtolower($inputs['currency']) == 'pound'){
                $entity_id = config('general.globals.hyperpay_pound_entity_id');
            } else{
                $entity_id = config('general.globals.hyperpay_entity_id');
            }
        }*/
        $url = config('general.globals.hyperpay_base_address') . $inputs['resource_path'];
//        $url = "https://test.oppwa.com/v1/payments/8ac7a49f72da96b80172dbca8c7729e6";
        $url .= "?entityId=" . $entity_id;
        $request_response = self::sendHyperpayRequest($url, 'get');
        $decoded_response = json_decode($request_response);
        if (empty($decoded_response)) {
            return ['success' => false, 'message' => 'Your transaction has been rejected'];
        }
        if (!empty($decoded_response->result->code) && in_array($decoded_response->result->code, HyperpayResponseCodesHelper::$success_codes)) {
            if (empty($decoded_response->id)) {
                return ['success' => false, 'message' => 'Your transaction has been rejected'];
            }
            return ['success' => true, 'transaction_id' => $decoded_response->id, 'registration_id' => (!empty($decoded_response->registrationId) ? $decoded_response->registrationId : null), 'message' => $decoded_response->result->description, 'payment_details' => $decoded_response];
        }
        if (!empty($decoded_response->result->code)) {
            return ['success' => false, 'message' => $decoded_response->result->description];
        }
        return ['success' => false, 'message' => 'Your transaction has been rejected'];
    }

    public static function getCurrencyRate() {
        $url = config('general.globals.hyperpay_base_address') . '/v1/currencies/conversionRates';
        $url .= "?entityId=" . config('general.globals.hyperpay_entity_id');
        $request_response = self::sendHyperpayRequest($url, 'get');
        $decoded_response = json_decode($request_response);
        if (!empty($decoded_response->result->code) && in_array($decoded_response->result->code, HyperpayResponseCodesHelper::$success_codes)) {
            return ['success' => true, 'response' => $decoded_response, 'message' => $decoded_response->result->description];
        }
        return ['success' => false, 'response' => $decoded_response, 'message' => $decoded_response->result->description];
    }

    public static function refundTransactionApi($payment_id, $amount, $currency,
                                                $merchantTransactionId = null,
                                                $notificationURL = null,
                                                $recurringType = 'REGISTRATION_BASED')
    {
        $currency = strtolower($currency ) == "pound" ? 'GBP' : 'SAR';
        $entityId = config('general.globals.hyperpay_entity_id');
        $access_token = 'Bearer '. config('general.globals.hyperpay_access_token');
        $body = [
            'headers' => [
                'Authorization' => $access_token
            ],
            'form_params' => [
                'entityId' => $entityId,
                'paymentType' => 'RF',
                'amount' => round($amount, 2),
                'currency' => $currency,
                'testMode' => 'EXTERNAL',
                'recurringType' => $recurringType
            ]
        ];

        if (!empty($merchantTransactionId)):
            $body['form_params']['merchantTransactionId'] = $merchantTransactionId;
        endif;

        if (!empty($notificationURL)):
            $body['form_params']['notificationUrl'] = $notificationURL;
        endif;

        $res = "";
        try{
            $client = new Client();
            $res = $client->request('POST', config('general.globals.hyperpay_base_address').'/v1/payments/'.$payment_id, $body);
            $res = $res->getBody()->getContents();
            Log::info('HyperPay Refund Response: ', [
                'url' => config('general.globals.hyperpay_base_address').'/v1/payments/'.$payment_id,
                'inputs' => $body,
                'response' => $res,
            ]);
        }catch (\GuzzleHttp\Exception\ClientException $e){
            Log::error('HyperPay Refund Error: ', [
                'url' => config('general.globals.hyperpay_base_address').'/v1/payments/'.$payment_id,
                'inputs' => $body,
                'response' => $e->getResponse()->getBody()->getContents(),
            ]);
            return false;
        }

        return json_decode($res, true);
    }

}

?>

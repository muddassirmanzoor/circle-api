<?php

namespace App\payment\checkout;

use App\Appointment;
use App\Customer;
use App\CustomerCard;
use App\Helpers\AfterPayment\Transition\Transition;
use App\Helpers\AppointmentValidationHelper;
use App\Helpers\CommonHelper;
use App\Helpers\ExceptionHelper;
use App\Helpers\MessageHelper;
use \App\payment\checkout\Interfaces\CheckoutInterface;
use App\payment\Wallet\Repository\WalletRepo;
use App\PurchasesTransition;
use App\Traits\Checkout\PaymentHelper;
use App\Wallet;
use Checkout\CheckoutApi;
use Checkout\Models\Payments\TokenSource;
use Checkout\tests\CheckoutApiTest;
use Checkout\tests\Models\Payments\TokenSourceTest;
use Checkout\Models\Payments\Payment;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use DB;

class Checkout implements CheckoutInterface
{

    use PaymentHelper;

    private $key = '';
    protected $token = '';

    public static function paymentType($inputs, $save_appointment, $paramsFor)
    {
        try {
            //code...
            \Log::info('in payment type method');
            $paymentResult = '';
            if ($inputs['paid_amount'] < 0.1) {
                $record = self::entryInPurchases($inputs, $paymentResult, $save_appointment, $paramsFor);
                return ['res' => true];
            }
            if ((isset($inputs['card_id'])) && (!empty($inputs['card_id'])) && $inputs['card_id'] == 'wallet') {
                \Log::info('===========in wallet case=========');
                if (WalletRepo::makePaymentThroughWallet($inputs, $save_appointment) >= $inputs['paid_amount']) {
                    \Log::info('===========in wallet case=========');
                    $inputs['status'] = 'pending';
                    $record = self::entryInPurchases($inputs, $paymentResult, $save_appointment, $paramsFor);
                    if ($record) {
                        DB::commit();
                        return ['res' => 'verify'];
                    }
                } else {
                    return ['res' => false, 'message' => CommonHelper::jsonErrorResponse('You do not have enough money to book this')];
                }
            } else {
                \Log::info('===========in card case=========');
                $inputs['status'] = 'pending';
                $records = self::entryInPurchases($inputs, $paymentResult, $save_appointment, $paramsFor);
                $inputs['purchase_transition'] = $records;
                // for apple pay remove and improve after testing

                if ((isset($inputs['apple_token'])) && !empty($inputs['apple_token'])) {
                    \Log::info('======== in apple pay case===========');
                    $paymentResult = Checkout::processApplePayment($inputs, 'payments');
                    if (is_string($paymentResult)) {
                        $message = self::errorHandlingForApplePay($paymentResult);
                        return ['res' => false, 'message' => CommonHelper::jsonErrorResponse($message)];
                    }
                    \Log::info(print_r($paymentResult, true));
                } else {
                    \Log::info('===========in checkout payment result=========');
                    $paymentResult = Checkout::processPayment($inputs, 'payments', $paramsFor);
                    \Log::info("==========payment result after json decode==========");
                    \Log::info(print_r($paymentResult, true));
                }
                if ((isset($paymentResult->status)) && (($paymentResult->status == 'Pending') || ($paymentResult->status == 'Authorized'))) {
                    // update purchases status to suceeded
                    if ($paramsFor == 'package' && isset($inputs['class']) && !empty($inputs['class']) && !empty($save_appointment['purchased_package_uuid'])) {
                        $purchasePackageID = \App\PurchasesPackage::where('purchased_packages_uuid', $save_appointment['purchased_package_uuid'])->first()->package_id;
                        // $updatePurchases = \App\Purchases::where('purchased_package_id', $purchasePackageID)->update(['status' => 'succeeded']);
                    }
                    $pay_id = (isset($paymentResult->id) && (!empty($paymentResult->id))) ? $paymentResult->id : null;
                    if (isset($paymentResult->_links->redirect)) {
                        $failure_url = null;
                        $success_url = null;
                        $payment_params = self::paymentParams('payments', $inputs, $paramsFor);
                        \Log::info('<============payment params=======>');
                        \Log::info(print_r($payment_params, true));
                        if ((isset($payment_params['success_url'])) && (!empty($payment_params['success_url']))) {
                            $success_url = $payment_params['success_url'];
                            \Log::info('<============success url=======>');
                            \Log::info(print_r($success_url, true));
                        }
                        if ((isset($payment_params['failure_url'])) && (!empty($payment_params['failure_url']))) {
                            $failure_url = $payment_params['failure_url'];
                            \Log::info('<============failure url ==========>');
                            \Log::info(print_r($failure_url, true));
                        }
                        // $pay_id = (isset($paymentResult->id) && (!empty($paymentResult->id))) ? $paymentResult->id : null;
                        \Log::info('<============pay id ==========>');
                        \Log::info(print_r($pay_id, true));
                        return ['res' => 'verify', 'link' => $paymentResult->_links->redirect->href, 'purchase_transition_id' => $records['purchase_transition']['id'], 'pay_id' => $pay_id, 'success_url' => $success_url, 'failure_url' => $failure_url];
                    } else {
                        PurchasesTransition::where('id', $records['purchase_transition']['id'])->update(['checkout_transaction_id' => $pay_id]);
                        return ['res' => 'verify', 'purchase_transition_id' => $records['purchase_transition']['id']];
                    }
                }
                if ((isset($paymentResult->status)) && (!empty($paymentResult->status)) && ($paymentResult->status == 'Declined')) {
                    return ['res' => false, 'message' => CommonHelper::jsonErrorResponse(isset($paymentResult->response_summary) ? $paymentResult->response_summary : 'Payment has been declined')];
                }
                $checkError = json_decode((string) $paymentResult->getBody());
                \Log::info("==========Error Response==========");
                \Log::info(print_r($checkError, true));
                if (isset($checkError->error_codes) && !empty($checkError->error_codes)) {
                    foreach ($checkError->error_codes as $key => $error) {
                        $message = CommonHelper::paymentErrors($error);
                        \Log::info('===========Error from checkout=========');
                        \Log::info(print_r($message, true));
                        if (!empty($message)) {
                            DB::rollback();
                            return ['res' => false, 'message' => CommonHelper::jsonErrorResponse($message)];
                        }
                    }
                }

                return ['res' => false, 'message' => CommonHelper::jsonErrorResponse('Something went wrong while processing payment')];
            }
        } catch (\Throwable $th) {
            //throw $th;
            return ['res' => false, 'message' => CommonHelper::jsonErrorResponse($th->getMessage())];
        }
    }

    public static function entryInPurchases($inputs, $paymentResult, $save_appointment, $paramsFor)
    {
        return Transition::insertDataIntoTables($inputs, $paymentResult, $save_appointment, $paramsFor);
    }

    public static function getPaymentDetail($ckoId, $slug)
    {
        \Log::info('in payment detail method');
        \Log::info('cko ID or payment id');
        \Log::info(print_r($ckoId, true));
        if (env('ENVIRONMENT') == 'live') {
            $url = env('CHECKOUT_BASE_URL');
            $key = env('CHECKOUT_KEY');
        } else {
            $url = env('CHECKOUT_SANDBOX_URL');
            $key = env('CHECKOUT__SANDBOX_KEY');
        }
        $response = (new Client())->get(
            $url . $slug . '/' . $ckoId,
            [
                'headers' =>
                [
                    'Authorization' =>  'Bearer '.$key,
                    'Content-Type' => 'application/json;charset=UTF-8'
                ]
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    public static function processPayment($params, $slug, $paramsFor = null)
    {

        return self::makeGhuzzleRequest(self::paymentParams($slug, $params, $paramsFor), $slug, 'post');
    }

    // TODO: Implement makePaymentRequest() method.
    public static function makeGhuzzleRequest($params, $slug, $requestType = 'post')
    {

        try {
            // previous code
            $client = new Client();
            if (env('ENVIRONMENT') == 'live') {
                $url = env('CHECKOUT_BASE_URL');
                $key = env('CHECKOUT_KEY');
            } else {
                $url = env('CHECKOUT_SANDBOX_URL');
                $key = env('CHECKOUT__SANDBOX_KEY');
            }
            $response = $client->$requestType($url . $slug, [
                'json' => $params,
                'headers' => [
                    'Authorization' => 'Bearer '.$key,
                    'Content-Type' => 'application/json;charset=UTF-8'
                ]
            ]);
            \Log::info('========check Authorization response===========');
            \Log::info(print_r($response, true));
            return json_decode($response->getBody()->getContents());
        } catch (\GuzzleHttp\Exception\RequestException $ex) {
            if ($ex->hasResponse()) {
                $response = $ex->getResponse();
                \Log::info(print_r($response->getStatusCode(), true)); // HTTP status code;
                \Log::info(print_r($response->getReasonPhrase(), true)); // HTTP status code;
                \Log::info(print_r(json_decode((string) $response->getBody()), true)); // HTTP status code;
                \Log::info(print_r($response->getStatusCode(), true)); // HTTP status code;
            }
            return $response;
        }
    }

    public static function prepareCurlRequest($url, $slug, $requestParams)
    {
        $data = json_encode($requestParams);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url . $slug);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $headers = array();
        // circl key live
        $headers[] = 'Authorization: sk_be1bb18e-92e6-404d-b6c2-d50494549aa0';
        // sandbox
        //        $headers[] = 'Authorization: sk_test_a7b684cf-a6c0-44d4-911d-ccba92b4284d';
        //        $headers[] = 'Cko-Idempotency-Key: string';
        $headers[] = 'Content-Type: application/json;charset=UTF-8';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        \Log::info('==============curl result received============');
        \Log::info(print_r($result, true));
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        $decoded_data = json_decode($result);
        \Log::info(print_r($result, true));
        \Log::info('==============curl decoded data============');
        \Log::info(print_r($decoded_data, true));

        return $decoded_data;

        //        $ch = curl_init($url . $slug);
        //        $headers = [
        ////             'Authorization' => env('CHECKOUT_KEY'),
        //            'Authorization' => env('CHECKOUT__SANDBOX_KEY'),
        //            'Content-Type' => 'application/json;charset=UTF-8'
        //        ];
        //
        //        $data = json_encode($requestParams);
        //        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        //        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        ////        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'sk_test_a7b684cf-a6c0-44d4-911d-ccba92b4284d'));
        //        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //        $result = curl_exec($ch);
        //        curl_close($ch);
        //        $decoded_data = json_decode($result);
        //        return $decoded_data;
    }

    public static function processApplePayment($params, $slug, $requestType = 'post')
    {
        if (isset($params['purchasing_type']) && $params['purchasing_type'] == 'premium_folder') {
            $appleParams = self::premiumFolderParamsForApplePayment($params);
        } else {
            $appleParams = self::bookingParamsForApplePayment($params);
        }
        if (env('ENVIRONMENT') == 'live') {
            $url = env('CHECKOUT_BASE_URL');
            $key = env('CHECKOUT_KEY');
        } else {
            $url = env('CHECKOUT_SANDBOX_URL');
            $key = env('CHECKOUT__SANDBOX_KEY');
        }
        // remove
        \Log::info('Apple Pay Params');
        \Log::info(print_r($appleParams, true));
        try {

            $client = new Client();
            // $response = $client->$requestType(env('CHECKOUT_SANDBOX_URL') . $slug, [
            $response = $client->$requestType($url . $slug, [
                'json' => $appleParams,
                'headers' => [
                    'Authorization' => 'Bearer '.$key,
                    // 'Authorization' => env('CHECKOUT__SANDBOX_KEY'),
                    'Content-Type' => 'application/json;charset=UTF-8'
                ]
            ]);

            $result = json_decode($response->getBody()->getContents());
            \Log::info('print result');
            return $result;
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public static function processAppleRecurringPayment($appleParams, $slug, $requestType = 'post')
    {
        Log::channel('recurring_payments')->debug('processAppleRecurringPayment');
        if (env('ENVIRONMENT') == 'live') {
            $url = env('CHECKOUT_BASE_URL');
            $key = env('CHECKOUT_KEY');
        } else {
            $url = env('CHECKOUT_SANDBOX_URL');
            $key = env('CHECKOUT__SANDBOX_KEY');
        }
        // remove
        try {

            $client = new Client();
            // $response = $client->$requestType(env('CHECKOUT_SANDBOX_URL') . $slug, [
            $response = $client->$requestType($url . $slug, [
                'json' => $appleParams,
                'headers' => [
                    'Authorization' => 'Bearer '.$key,
                    'Content-Type' => 'application/json;charset=UTF-8'
                ]
            ]);

            $result = json_decode($response->getBody()->getContents());
            return $result;
        } catch (\Exception $ex) {
            Log::channel('recurring_payments')->debug($ex->getMessage());
            return $ex->getCode();
        }
    }

    public static function getToken()
    {
        $params = [
            'type' => 'card',
            'number' => '4658584090000001',
            'expiry_month' => '04',
            'expiry_year' => '2025',
            'cvv' => '257',
            'name:waqas'
        ];

        $client = new Client();

        //        $response = $client->post(env('CHECKOUT_BASE_URL') . 'tokens', [
        $response = $client->post(env('CHECKOUT_SANDBOX_URL') . 'tokens', [
            'json' => $params,
            'headers' => [
                // live public key
                // 'Authorization' => 'pk_4cade985-2476-4a6d-895a-9ee58d3bd1b4',
                // Sandbox public key
                'Authorization' => 'pk_test_982d73c4-5c47-4381-89d1-090b25785ce6',
                'Content-Type' => 'application/json;charset=UTF-8'
            ]
        ]);

        dd(json_decode($response->getBody()->getContents()));
    }

    public static function deleteUserCard($inputs)
    {
        $validation = Validator::make($inputs, AppointmentValidationHelper::deleteCardRules()['rules'], AppointmentValidationHelper::TorkenRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return ['msg' => 'false', 'error' => $validation->errors()->first()];
        }
        $customerId = CommonHelper::getCutomerIdByUuid($inputs['customer_uuid']);

        $response = CustomerCard::where('customer_id', $customerId)->where('card_id', $inputs['card_id'])->update(['is_archive' => 1]);

        if ($response) {
            return CommonHelper::jsonSuccessResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['delete_card_success']);
        } else {
            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['delete_card_error']);
        }
    }

    public static function addCustomerToken($inputs)
    {
        $validation = Validator::make($inputs, AppointmentValidationHelper::TorkenRules()['rules'], AppointmentValidationHelper::TorkenRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return ['msg' => 'false', 'error' => $validation->errors()->first()];
        }
        $params = Checkout::paymentParams('instruments', $inputs);

        $cardDetail = self::makeGhuzzleRequest($params, 'instruments');
        if (isset($cardDetail->error_codes) && !empty($cardDetail->error_codes)) {
            foreach ($cardDetail->error_codes as $key => $error) {
                if ($error == 'token_invalid') {
                    return CommonHelper::jsonSuccessResponse('invalid token provided');
                }
            }
        }
        if (isset($cardDetail) && method_exists($cardDetail, 'getStatusCode') && $cardDetail->getStatusCode()) {
            if($cardDetail->getStatusCode() == '422')
                return CommonHelper::jsonErrorResponse('Invalid data was sent');
        }

        \Log::info('================Response after adding card detail===========');
        \Log::info(print_r($cardDetail, true));
        $inputs['customer_id'] = CommonHelper::getCutomerIdByUuid($inputs['customer_uuid']);

        $tableParams = self::makeCustomerCardParams($inputs, $cardDetail);

        if (!CustomerCard::checkCardEntry($inputs['customer_id'], $cardDetail->id)) {
            $response = self::makeCardResponse(CustomerCard::create($tableParams), $inputs['customer_id']);
        } else {
            return CommonHelper::jsonSuccessResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['card_already_inserter']);
        }
        if ($response) {
            return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
        } else {
            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['save_card_error']);
        }
    }

    public static function getCardDetail($inputs)
    {
        $inputs['customer_id'] = CommonHelper::getCutomerIdByUuid($inputs['customer_uuid']);
        $cards = CustomerCard::where('customer_id', $inputs['customer_id'])->where('is_archive', 0)->get()->toArray();
        $creditAmount = Wallet::getAmountByType($inputs, 'credit');
        $debitAmount = Wallet::getAmountByType($inputs, 'debit');
        $balance = $creditAmount - $debitAmount;
        $response = [
            'balance' => $balance,
            'cards' => self::getCustomerCardsDetail($cards, $inputs)
        ];

        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getCustomerCardsDetail($cards, $inputs)
    {
        $response = [];
        foreach ($cards as $card) {
            $response[] = self::makeCardResponse($card, $inputs['customer_id']);
        }
        return $response;
    }

    public static function makeCardResponse($card, $customerUUId)
    {
        return [
            'card_id' => $card['card_id'],
            'customer_uuid' => $customerUUId,
            'type' => strtolower($card['card_name']),
            'last_digits' => $card['last_digits'],
            'expiry' => $card['expiry'],
        ];
    }

    public static function makeCustomerCardParams($params, $cardDetail)
    {
        $data = [];
        if (empty($cardDetail)) {
            return CommonHelper::jsonErrorResponse('Please enter a valid card');
        } elseif (!empty($cardDetail)) {
            $data = [
                'customer_id' => $params['customer_id'],
                'card_id' => $cardDetail->id,
                'token' => $params['token'],
                'card_name' => (isset($cardDetail->scheme)) ? $cardDetail->scheme : null,
                'type' => (isset($cardDetail->card_type)) ? $cardDetail->card_type : null,
                'last_digits' => (isset($cardDetail->last4)) ? $cardDetail->last4 : null,
                'expiry' => $cardDetail->expiry_month . '-' . $cardDetail->expiry_year,
                'customer_checkout_id' => (isset($cardDetail->customer->id)) ? $cardDetail->customer->id : null,
                'bin' => (isset($cardDetail->bin)) ? $cardDetail->bin : null,
            ];
        }
        return $data;
    }

    public static function capturePayment($inputs)
    {
        if (env('ENVIRONMENT') == 'live') {
            $url = env('CHECKOUT_BASE_URL');
            $key = env('CHECKOUT_KEY');
        } else {
            $url = env('CHECKOUT_SANDBOX_URL');
            $key = env('CHECKOUT__SANDBOX_KEY');
        }
        \Log::info('cko ID or payment id');
        \Log::info(print_r($inputs['payment_id'], true));
        $paymentDetail = self::getPaymentDetail($inputs['payment_id'], 'payments');
        \Log::info('=======================payment detail====================');
        \Log::info(print_r($paymentDetail, true));
        $slug = 'payments/' . $inputs['payment_id'] . '/captures';
        $params = [
            'amount' => $paymentDetail->amount
        ];
        try {
            if ((isset($paymentDetail->status)) && ($paymentDetail->status == 'Authorized') && ($paymentDetail->status != 'Captured')) {

                $client = new Client();
                $response = $client->post($url . $slug, [
                    'json' => $params,
                    'headers' => [
                        'Cko-Idempotency-Key' => $inputs['cko_id'],
                        'Authorization' => 'Bearer '.$key,
                    ]
                ]);

                $content = $response->getBody()->getContents();
                sleep(5);
            }
            $response = self::getPaymentDetail($inputs['payment_id'], 'payments');
            \Log::info('=======================Response after capture====================');
            \Log::info(print_r($response, true));
            return $response;
        } catch (\Exception $ex) {
            DB::rollback();
            return ['success' => false, 'message' => $ex->getMessage(), 'code' => $ex->getCode()];
        }
    }

    public static function topUp($inputs)
    {
        $customer = Customer::where('customer_uuid', $inputs['customer_uuid'])->with('token')->first();
        if (empty($customer)) {
            return CommonHelper::jsonErrorResponse('Customer does not exist');
        }
        $customer = $customer->toArray();
        $customerId = $customer['id'];
        $wallet = Wallet::create(WalletRepo::topUpInWallet($inputs));
        $inputs['wallet_id'] = $wallet->id;
        $bankConformation = self::makeGhuzzleRequest(PaymentHelper::paymentParams('topUp', $inputs), 'payments', 'post');
        \Log::info('============Top up confirmation response================');
        \Log::info(print_r($bankConformation, true));
        if ($bankConformation->status == 'Pending') {
            $failure_url = null;
            $success_url = null;
            $inputs['topup'] = true;
            $redirect_url = self::redirectUrl($inputs, $customer);
            if ((isset($redirect_url['success_url'])) && (!empty($redirect_url['success_url']))) {
                $success_url = $redirect_url['success_url'];
                \Log::info('<============success url=======>');
                \Log::info(print_r($success_url, true));
            }
            if ((isset($redirect_url['failure_url'])) && (!empty($redirect_url['failure_url']))) {
                $failure_url = $redirect_url['failure_url'];
                \Log::info('<============failure url ==========>');
                \Log::info(print_r($failure_url, true));
            }
            $pay_id = (isset($bankConformation->id) && (!empty($bankConformation->id))) ? $bankConformation->id : null;
            \Log::info('<============pay id ==========>');
            \Log::info(print_r($pay_id, true));
            return CommonHelper::jsonSuccessResponse(
                AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['save_appointment_success'],
                [
                    'res' => 'verify',
                    'link' => $bankConformation->_links->redirect->href,
                    'wallet_id' => $wallet->id,
                    'customer_id' => $customerId,
                    'pay_id' => $pay_id,
                    'success_url' => $success_url,
                    'failure_url' => $failure_url,
                ]
            );
        }
        return ['res' => false];
    }

    public static function refundThroughCard($amount, $paymentId, $ckoId)
    {
        try {
            if (env('ENVIRONMENT') == 'live') {
                $url = env('CHECKOUT_BASE_URL');
                $key = env('CHECKOUT_KEY');
            } else {
                $url = env('CHECKOUT_SANDBOX_URL');
                $key = env('CHECKOUT__SANDBOX_KEY');
            }
            $amount = $amount * 100;
            $client = new Client();
            $client->post($url . "/payments/{$paymentId}/refunds", [
                //            $client->post(env('CHECKOUT_BASE_URL') . "/payments/{$paymentId}/refunds", [
                'json' => ['amount' => $amount],
                'headers' => [
                    'Cko-Idempotency-Key' => $ckoId,
                    'Authorization' => $key,
                    'Content-Type' => 'application/json;charset=UTF-8'
                ]
            ]);
            return self::getPaymentDetail($paymentId, 'payments');
        } catch (\Exception $ex) {

            return $ex->getCode();
        }
    }

    public static function voidThroughCard($paymentId, $ckoId)
    {
        try {
            if (env('ENVIRONMENT') == 'live') {
                $url = env('CHECKOUT_BASE_URL');
                $key = env('CHECKOUT_KEY');
            } else {
                $url = env('CHECKOUT_SANDBOX_URL');
                $key = env('CHECKOUT__SANDBOX_KEY');
            }
            $client = new Client();
            $client->post($url . "/payments/{$paymentId}/voids", [
                //            $client->post(env('CHECKOUT_BASE_URL') . "/payments/{$paymentId}/voids", [
                'headers' => [
                    'Cko-Idempotency-Key' => $ckoId,
                    'Authorization' => 'Bearer '.$key,
                    'Content-Type' => 'application/json;charset=UTF-8'
                ]
            ]);
            return self::getPaymentDetail($paymentId, 'payments');
        } catch (\Exception $ex) {

            return $ex->getCode();
        }
    }

    public static function appointmentRefund($content_id, $appointment, $status)
    {
        try {
            Log::channel('daily_change_status')->debug('appointmentRefund');
            Log::channel('daily_change_status')->debug($appointment);
            \Log::info('---------here in appointment refund method---------------');
            $paymentDetail = [];
            \Log::info('=========payment detail init===========');
            \Log::info('-----appointment in appointment refund method----------------');
            \Log::info(print_r($appointment, true));
            $paymentWith = !empty($appointment['purchase']['purchased_by']) ? $appointment['purchase']['purchased_by'] : ' ';
            \Log::info('-----payment with-----------');
            \Log::info(print_r($paymentWith, true));
            Log::channel('daily_change_status')->debug('paymentWith');
            Log::channel('daily_change_status')->debug($paymentWith);
            if ($paymentWith == 'card'  || $paymentWith == 'apple_pay') {
                \Log::info('---------------in card case-------------');
                // get payment detail from checkout
                $paymentId = !empty($appointment['purchase']['purchases_transition']['checkout_transaction_id']) ? $appointment['purchase']['purchases_transition']['checkout_transaction_id'] : null;
                \Log::info('---------------after payment id-------------');
                $ckoId = !empty($appointment['purchase']['purchases_transition']['cko_id']) ? $appointment['purchase']['purchases_transition']['cko_id'] : null;
                \Log::info('---------------after cko id-------------');
                Log::channel('daily_change_status')->debug('paymentId');
                Log::channel('daily_change_status')->debug($paymentId);
                if (!empty($paymentId)) {
                    \Log::info('---------------if not empty payment id-------------');
                    $paymentDetail = self::getPaymentDetail($paymentId, 'payments');
                    Log::channel('daily_change_status')->debug('getPaymentDetail');
                    Log::channel('daily_change_status')->debug(print_r($paymentDetail, true));
                    \Log::info('---------------after payment detail-------------');

                    if ((isset($paymentDetail->status)) && (!empty($paymentDetail->status)) && ($paymentDetail->status == 'Captured')) {
                        // then fully refund customer by adding the amount to its wallet
                        $data = self::prepareWalletData($appointment, $paymentDetail);
                        $refund = Wallet::createWalletData($data);
                        // uncomment the line after this code to refund amount to customers account through checkout
                        //                $refund = self::refundThroughCard($appointment['paid_amount'], $paymentId, $ckoId);
                        //                if ((isset($refund->status)) && (!empty($refund->status)) && ($refund->status == 'Refunded')) {
                        //                    return ['success' => true, 'message' => 'Refunded Successfully', 'status' => 'Refunded', 'response' => $refund];
                        //                }
                        if ($refund) {
                            return ['success' => true, 'message' => 'Refunded Successfully', 'status' => 'Refunded', 'response' => $refund];
                        }
                    } elseif ((isset($paymentDetail->status)) && (!empty($paymentDetail->status)) && ($paymentDetail->status == 'Authorized')) {
                        //                // then void
                        Log::channel('daily_change_status')->debug('Void Call before response');
                        Log::channel('daily_change_status')->debug($appointment);
                        $void = self::voidThroughCard($paymentId, $ckoId);
                        Log::channel('daily_change_status')->debug('Void Call response');
                        Log::channel('daily_change_status')->debug($void->status);

                        if ((isset($void->status)) && (!empty($void->status)) && ($void->status == 'Voided')) {
                            return ['success' => true, 'message' => 'Voided Successfully', 'status' => 'Voided', 'response' => $void];
                        } else {
                            return ['success' => true, 'message' => 'Payment Status Neither Captured Nor Authorized', 'status' => null, 'response' => $void];
                        }
                    } else {
                        return ['success' => true, 'message' => 'Payment Status Neither Captured Nor Authorized', 'status' => $paymentDetail->status, 'response' => $paymentDetail];
                    }
                }
            } elseif ($paymentWith == 'wallet') {
                $paymentDetail = [];
                $data = self::prepareWalletData($appointment, $paymentDetail);
                $refund = Wallet::createWalletData($data);
                if ($refund) {
                    return ['success' => true, 'message' => 'Refunded Successfully', 'status' => 'Refunded', 'response' => $refund];
                }
                return ['success' => false, 'message' => 'Error occurred while refunding to wallet'];
                // if payment with wallet
            }
        } catch (\Throwable $th) {
            Log::channel('daily_change_status')->debug($th->getMessage() . 'Line ' . $th->getLine());
        }
        //         if ($paymentWith == 'card') {
        //            $paymentId = $appointment['purchase']['purchases_transition']['checkout_transaction_id'];
        //            $ckoId = $appointment['purchase']['purchases_transition']['cko_id'];
        //            if ($status == 'rejected') {
        //                return self::voidThroughCard($paymentId, $ckoId);
        //            } else {
        //                return self::refundThroughCard($appointment, $paymentId, $ckoId);
        //            }
        //        }
    }

    public static function appointmentFullRefund($appointment, $status)
    {

        $refund = [];
        $paymentWith = $appointment['purchase']['purchased_by'];
        if ($paymentWith == 'card') {
            // get payment detail from checkout
            $paymentId = $appointment['purchase']['purchases_transition']['checkout_transaction_id'];
            $ckoId = $appointment['purchase']['purchases_transition']['cko_id'];
            $paymentDetail = self::getPaymentDetail($paymentId, 'payments');
            if ((isset($paymentDetail->status)) && (!empty($paymentDetail->status)) && ($paymentDetail->status == 'Captured')) {
                // then fully refund customer and add the amount to its wallet
                $data = self::prepareWalletData($appointment, $paymentDetail);

                $refund = Wallet::createWalletData($data);

                if ($refund) {
                    return ['success' => true, 'message' => 'Refunded Successfully', 'status' => 'Refunded', 'response' => $refund];
                }
                // $refund = self::refundThroughCard($appointment['paid_amount'], $paymentId, $ckoId);
                // if ((isset($refund->status)) && (!empty($refund->status)) && ($refund->status == 'Refunded')) {
                //     return ['success' => true, 'message' => 'Refunded Successfully', 'status' => 'Refunded', 'response' => $refund];
                // }
            } else {
                DB::rollback();
                return ['success' => true, 'message' => 'Payment Not in Captured status nothing to refund', 'status' => 'Cancelled'];
            }
        } else {
            // if payment with wallet
            $paymentDetail = [];
            $data = self::prepareWalletData($appointment, $paymentDetail);
            $refund = Wallet::createWalletData($data);
            if ($refund) {
                return ['success' => true, 'message' => 'Refunded Successfully', 'status' => 'Refunded', 'response' => $refund];
            }
            return ['success' => false, 'message' => 'Error occurred while refunding to wallet'];
        }
    }

    public static function refundAmount($type, $content_id)
    {
        $response = '';
        switch ($type) {
                //            case 'appointment':
                //                 self::appointmentRefund($content_id);
                //                break;
            case 'package_appointment':
                $params = Appointment::getAppointmentPackageWithPurchase($content_id);
                break;

            case 'single_class_booking':
                $params = self::singleClassEarningParams(ClassBooking::getBookingWithPurchase($content_id));
                break;

            case 'multi_class_booking':
                $params = self::multipleClassEarningParams(ClassBooking::getClassPackageBookingWithPurchase($content_id));
                break;

            default:
                dd('no default');
        }
    }

    public static function appointmentFinalAmount($appointment)
    {
        $appointmentDate = CommonHelper::convertMyDBDateIntoLocalDate($appointment['appointment_end_date_time'], $appointment['saved_timezone'], $appointment['local_timezone']);
        return self::calculateAmount($appointmentDate, $appointment['purchase']['purchases_transition']['amount']);
    }

    public static function calculateAmount($date, $amount)
    {

        $date1 = new \DateTime($date);
        $date2 = new \DateTime(date('Y-m-d'));
        $interval = $date1->diff($date2);
        if ($interval->d < 2) {
            $amount = $amount - (($amount * 10) / 100);
        }

        return $amount;
    }

    public static function prepareWalletData($appointment, $paymentResponse = [])
    {
        $data['customer_id'] = $appointment['customer_id'];
        $data['amount'] = $appointment['paid_amount'];
        $data['purchase_id'] = $appointment['purchase']['id'];
        $data['type'] = 'credit';
        $data['is_refunded'] = 1;
        $data['gatway_response'] = !empty($paymentResponse) ? serialize($paymentResponse) : null;
        $data['customer_card_id'] = !empty($appointment['purchase']['customer_card_id']) ? $appointment['purchase']['customer_card_id'] : null;
        $data['checkout_transaction_reference'] = null;
        $data['payment_status'] = 'succeeded';
        return $data;
    }
    public static function errorHandlingForApplePay($paymentResult)
    {
        if (strpos($paymentResult, "token_invalid")) {
            return 'Invalid Token';
        }
        if (strpos($paymentResult, "card_expired")) {
            return 'Your card is expired';
        }
        if (strpos($paymentResult, "token_used")) {
            return 'Your token is used already';
        }
        if (strpos($paymentResult, "token_expired")) {
            return 'Your token is expired';
        }
        return ['res' => false, 'message' => CommonHelper::jsonErrorResponse($paymentResult)];
    }
}

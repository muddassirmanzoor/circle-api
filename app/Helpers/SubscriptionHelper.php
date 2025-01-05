<?php

namespace App\Helpers;

use App\MoyasarWebForm;
use App\payment\checkout\Checkout;
use App\PurchasesTransition;
use App\SubscriptionMonthlyEntries;
use App\SubscriptionSetting;
use App\Subscription;
use App\Customer;
use App\Purchases;
use App\Traits\EarningAmount;
use App\Wallet;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\DB as FacadesDB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

Class SubscriptionHelper {
    /*
      |--------------------------------------------------------------------------
      | SubscriptionHelper that contains all the categpry related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use settings processes
      |
     */

    /**
     * Description of CategoryHelper
     *
     * @author ILSA Interactive
     */
    public static function addSubscription($inputs) {

        $validationMessages = FreelancerValidationHelper::addSubscription()['message_' . strtolower($inputs['lang'])];
        $validation = Validator::make($inputs, FreelancerValidationHelper::addSubscription()['rules'], $validationMessages);

        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $inputs['subscriber_id'] = CommonHelper::getRecordByUuid('customers', 'customer_uuid', $inputs['subscriber_uuid']);
        $inputs['subscribed_id'] = CommonHelper::getRecordByUuid('freelancers', 'freelancer_uuid', $inputs['subscribed_uuid']);
        $inputs['subscription_settings_id'] = CommonHelper::getRecordByUuid('subscription_settings', 'subscription_settings_uuid', $inputs['subscription_settings_uuid']);
        $inputs['transaction_id'] = null;
        $subscription_setting = SubscriptionSetting::getSingleSubscriptionSetting('subscription_settings_uuid', $inputs['subscription_settings_uuid']);
        if (empty($subscription_setting)) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['invalid_data']);
        }

        $subscription_term = $subscription_setting['type'] ?? 'monthly';

        $inputs['total_amount'] = CommonHelper::getConvertedCurrency($subscription_setting['price'], $subscription_setting['currency'], $inputs['currency']);
        $inputs['actual_amount'] = CommonHelper::getConvertedCurrency($subscription_setting['price'], $subscription_setting['currency'], $inputs['currency']);
        $inputs['from_currency'] = $subscription_setting['currency'];

        $inputs['to_currency'] = $inputs['currency'];

        $inputs['exchange_rate'] = config('general.globals.' . $inputs['currency']);

        $inputs['payment_brand'] = !empty($inputs['payment_details']->source) && !empty($inputs['payment_details']->source->type) ? $inputs['payment_details']->source->type : null;

        $inputs['moyasar_fee'] = !empty($inputs['payment_details']->fee) ? $inputs['payment_details']->fee : 0;
        $data = [
            'subscriber_id' => $inputs['subscriber_id'],
            'subscribed_id' => $inputs['subscribed_id'],
            'subscription_settings_id' => $inputs['subscription_settings_id'],
            'subscription_date' => date("Y-m-d H:i:s"),
            'subscription_end_date' => date('Y-m-d H:i:s', self::setSubscriptionEndDate($subscription_setting['type'])), // in this fun we set the end date of the subscription package
            'transaction_id' => $inputs['transaction_id'],
            'card_registration_id' => $inputs['registration_id'] ?? '',
        ];
        $check = Subscription::checkSubscriber($inputs['subscriber_id'], $inputs['subscribed_id']);
        if ($check) {
            return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['already_subscribed_error']);
        }
        if (isset($inputs['card_id']) && ($inputs['card_id'] == 'wallet')) {
            $data['payment_status'] = 'captured';
        }
        $save = Subscription::createSubscription($data);
        $inputs['subscription_uuid'] = $save['subscription_uuid'];
        if (!$save) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['add_transaction_log_error']);
        }
        $inputs['customer_uuid'] = $inputs['subscriber_uuid'];
        $inputs['freelancer_uuid'] = $inputs['subscribed_uuid'];


        $inputs['purchasing_type'] = 'subscription';
        $response = Checkout::paymentType($inputs, $save, 'subscription');
        if ($response['res'] == false) {
            DB::rollBack();
            return $response['message'];
        }
        if ($response['res'] == 'verify') {
            EarningAmount::CreateEarningRecords('subscription', $save['id']);
            DB::commit();
            return CommonHelper::jsonSuccessResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['save_appointment_success'], $response);
        }

    }

    public static function setSubscriptionEndDate($type) {

        if ($type == 'monthly') {
            return strtotime('+1 month', strtotime(date('Y-m-d H:i:s')));
        } elseif ($type == 'quarterly') {
            return strtotime('+3 month', strtotime(date('Y-m-d H:i:s')));
        } else {
            return strtotime('+12 month', strtotime(date('Y-m-d H:i:s')));
        }
    }

    public static function addSubscriptionSettings($inputs) {
        if (empty($inputs['settings'])) {
            return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['invalid_data_error']);
        }
        foreach ($inputs['settings'] as $key => $setting) {
            $setting['freelancer_id'] = $inputs['freelancer_uuid'];
            $inputs['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);
            $validation = Validator::make($setting, FreelancerValidationHelper::addSubscriptionSettings()['rules'], FreelancerValidationHelper::addSubscriptionSettings()['message_' . strtolower($inputs['lang'])]);
            if ($validation->fails()) {
                return CommonHelper::jsonErrorResponse($validation->errors()->first());
            }
            $data[$key] = ['subscription_settings_uuid' => UuidHelper::generateUniqueUUID('subscription_settings', 'subscription_settings_uuid'),
                'freelancer_id' => $inputs['freelancer_id'],
                'type' => $setting['type'],
                'price' => $setting['price'],
                'currency' => $setting['currency']];
            $validation = Validator::make($data[$key], FreelancerValidationHelper::addSubscriptionSettings()['rules'], FreelancerValidationHelper::addSubscriptionSettings()['message_' . strtolower($inputs['lang'])]);
            if ($validation->fails()) {
                return CommonHelper::jsonErrorResponse($validation->errors()->first());
            }
        }
        $add_settings = SubscriptionSetting::saveSubscriptionSetting($data);
        if (!$add_settings) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['update_settings_error']);
        }

        $profile_inputs = ['freelancer_uuid' => $inputs['freelancer_uuid'], 'has_subscription' => 1, 'lang' => $inputs['lang']];
        if (array_key_exists('profile_type', $inputs) && !empty($inputs['profile_type'])) {
            $profile_inputs['profile_type'] = $inputs['profile_type'];
        }
        if (isset($inputs['receive_subscription_request'])) {
            $profile_inputs['receive_subscription_request'] = $inputs['receive_subscription_request'];
        }
        $save_profile = FreelancerHelper::updateFreelancer($profile_inputs);
        $result = json_decode(json_encode($save_profile));
        if (!$result->original->success) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['update_settings_error']);
        }
        $subscriptions = SubscriptionSetting::getFreelancerSubscriptions('freelancer_id', $inputs['freelancer_id']);
        $response = FreelancerResponseHelper::makeFreelancerSucscriptionArr($subscriptions);
        DB::commit();
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function updateFreelancerSubscriptionSettings($inputs) {
        $inputs['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);
        if (!empty($inputs['settings'])) {
            foreach ($inputs['settings'] as $setting) {

                if (!empty($setting['subscription_settings_uuid'])) {
                    $update_data = [
                        'subscription_settings_uuid' => $setting['subscription_settings_uuid'],
                        'type' => $setting['type'],
                        'price' => $setting['price'],
                        'currency' => $setting['currency'],
                    ];

                    $validation = Validator::make($update_data, FreelancerValidationHelper::updateSubscriptionSettings()['rules'], FreelancerValidationHelper::updateSubscriptionSettings()['message_' . strtolower($inputs['lang'])]);
                    if ($validation->fails()) {
                        return CommonHelper::jsonErrorResponse($validation->errors()->first());
                    }

                    $update_settings = SubscriptionSetting::updateSubscriptionSetting('subscription_settings_uuid', $update_data['subscription_settings_uuid'], $update_data);

                    if (!$update_settings) {
                        DB::rollBack();
                        return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['update_settings_error']);
                    }
                } else {
                    $add_data = ['subscription_settings_uuid' => UuidHelper::generateUniqueUUID('subscription_settings', 'subscription_settings_uuid'),
                        'freelancer_id' => $inputs['freelancer_id'],
                        'type' => $setting['type'],
                        'price' => $setting['price'],
                        'currency' => $setting['currency']
                    ];

                    $validation = Validator::make($add_data, FreelancerValidationHelper::addSubscriptionSettings()['rules'], FreelancerValidationHelper::addSubscriptionSettings()['message_' . strtolower($inputs['lang'])]);
                    if ($validation->fails()) {
                        return CommonHelper::jsonErrorResponse($validation->errors()->first());
                    }
                    $add_settings = SubscriptionSetting::createSubscriptionSetting($add_data);
                    if (empty($add_settings)) {
                        DB::rollBack();
                        return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['update_settings_error']);
                    }
                }
            }
        }


        if (!empty($inputs['delete_setting'])) {
            foreach ($inputs['delete_setting'] as $delete_setting) {
                $delete_setting_data = ['subscription_settings_uuid' => $delete_setting['subscription_settings_uuid'], 'is_archive' => 1, 'freelancer_id' => $inputs['freelancer_id']];
                $delete_settings = SubscriptionSetting::updateSubscriptionSetting('subscription_settings_uuid', $delete_setting_data['subscription_settings_uuid'], $delete_setting_data);
                if (!$delete_settings) {
                    DB::rollBack();
                    return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['update_settings_error']);
                }
            }
        }



        $check_subscriptions = SubscriptionSetting::checkActiveSubscriptionSetting('freelancer_id', $inputs['freelancer_id']);
        if ($check_subscriptions) {
            $profile_inputs = ['freelancer_uuid' => $inputs['freelancer_uuid'], 'has_subscription' => 1, 'lang' => $inputs['lang']];
        } elseif (!$check_subscriptions) {
            $profile_inputs = ['freelancer_uuid' => $inputs['freelancer_uuid'], 'has_subscription' => 0, 'lang' => $inputs['lang']];
        }
        if (isset($inputs['receive_subscription_request'])) {
            $profile_inputs['receive_subscription_request'] = $inputs['receive_subscription_request'];
        }

        $save_profile = FreelancerHelper::updateFreelancer($profile_inputs);

        $result = json_decode(json_encode($save_profile));
        if (!$result->original->success) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['update_settings_error']);
        }
        $subscriptions = SubscriptionSetting::getFreelancerSubscriptions('freelancer_id', $inputs['freelancer_id']);
        $response = FreelancerResponseHelper::makeFreelancerSucscriptionArr($subscriptions);
        DB::commit();
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function processSubscription($inputs = []) {

        $validation = Validator::make($inputs, SubscriberValidationHelper::processSubscriptionRules()['rules'], SubscriberValidationHelper::processSubscriptionRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        if (strtolower($inputs['login_user_type']) != 'customer') {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['invalid_data']);
        }

        $customer = Customer::checkCustomer('customer_uuid', $inputs['logged_in_uuid']);
        if (!$customer) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['invalid_data']);
        }

        $check_subscription = Subscription::checkSubscription('subscription_uuid', $inputs['subscription_uuid']);

        if (!$check_subscription) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['invalid_data']);
        }

        if ($inputs['type'] == 'cancel') {
            $data = ['auto_renew' => 0];
            $message = "has cancelled their subscription";
        } elseif ($inputs['type'] == 'activate') {
            $data = ['auto_renew' => 1];
            $message = "has activated their subscription";
        }

        $data = Subscription::cancelSubscription('subscription_uuid', $inputs['subscription_uuid'], $data);
        if (!$data) {
            DB::rollback();
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['success_error']);
        }
        $text = ($inputs['type'] == "cancel") ? "cancelled" : "activated";
        $message = !empty($message) ? "has " . $text . " their subscription" : null;
        $notification_data = ['subscriber_id' => $check_subscription['subscriber_id'], 'subscribed_id' => $check_subscription['subscribed_id'], 'subscription_uuid' => $inputs['subscription_uuid'], 'message' => $message];
        ProcessNotificationHelper::updateSubscriptionNotification($notification_data);
        DB::commit();
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData($type = 'success', $inputs['lang'])['successful_request']);
    }

    public static function checkFreelancerSubscriptionSettingExists($data = []) {

        $check_settings = false;
        if (!empty($data)) {
            $get_settings = SubscriptionSetting::checkSubscriptionSetting('freelancer_id', $data['id']);
            $check_settings = !empty($get_settings) ? true : false;
        }
        return $check_settings ? true : false;
    }

    public static function subscriptionRenewalAlert() {
        $input = [];
        $isNotifiable = Subscription::subscriptionRenewalAlert();

        if (!empty($isNotifiable)) {
            foreach ($isNotifiable as $notify) {
                $input['subscriber_id'] = $notify['subscriber_id'];
                $input['subscribed_id'] = $notify['subscribed_id'];
                ProcessNotificationHelper::sendSubscriberReminderNotification($input, $notify, '', self::extraChecks($notify));
            }
        }
        return CommonHelper::jsonSuccessResponse('notification sent');
    }

    public static function extraChecks($notification) {
        if ($notification['purchase']['purchased_by'] == 'wallet') {
            $input['customer_uuid'] = CommonHelper::getCutomerUUIDByid($notification['purchase']['wallet']['customer_id']);
            $walletAmount = Wallet::getWalletTotalAmount($input);
            $subscriptionAmount = $notification['subscription_setting']['price'];
            if ($walletAmount < $subscriptionAmount) {
                return [
                    'subscriptionAmount' => $subscriptionAmount,
                    'walletAmount' => $walletAmount,
                    'msg' => "your Wallet balance is {$walletAmount} and your subscription amount is {$subscriptionAmount} please recharge your wallet"
                ];
            }
        }
    }

    public static function recurringSubscriptionPayment() {
        $renewalRecords = Subscription::renewalSubscriptionsRecords();
        Log::channel('recurring_payments')->debug('Current JSON'. json_encode($renewalRecords));
        foreach ($renewalRecords as $record) {

            if ($record['auto_renew'] == 1) {
                if ($record['purchase']['purchased_by'] == 'card') {

                    $params = \App\Traits\Checkout\PaymentHelper::recurringSubscriptionPaymentParams($record);
                    $paymentStatus = Checkout::makeGhuzzleRequest($params, 'payments');

                    if (!empty($paymentStatus) && isset($paymentStatus->id)) {

                        $paymentDetail = Checkout::getPaymentDetail($paymentStatus->id, 'payments');

                        if ($paymentDetail->status == 'Authorized')
                        {
                            $payment_status = 'Authorized';
                            while($payment_status == 'Authorized')
                            {
                                sleep(3);

                                $paymentDetail = Checkout::getPaymentDetail($paymentStatus->id, 'payments');

                                if ($paymentDetail->status == 'Captured') {
                                    self::updateSubscription($record, $paymentDetail);
                                    self::pushNotification($record, ['msg' => 'Your Subscription is renewed Thanks you'], 'subscription_renewal_captured');
                                    break;
                                } else {
                                    self::stopSubscriptionAndContent($record);
                                    self::pushNotification($record, ['msg' => 'your subscription has stop because your payment problem'], 'subscription_renewal_fail');
                                }
                            }
                        }else{
                            self::captureThroughWallet($record);
                        }
                    }
                } else if ($record['purchase']['purchased_by'] == 'apple_pay') {
                    try {
                        Log::channel('recurring_payments')->debug('Apple Pay Case');
                        $paymentDetail = Checkout::getPaymentDetail($record['purchase']['purchases_transition']['checkout_transaction_id'], 'payments');
                        $params = \App\Traits\Checkout\PaymentHelper::bookingParamsForAppleRecurringPayment($record,$paymentDetail);
                        Log::channel('recurring_payments')->debug('Booking Params For apple pay recurring');
                        Log::channel('recurring_payments')->debug($params);

                        $paymentStatus = Checkout::processAppleRecurringPayment($params, 'payments');
                        sleep(2);
                        $paymentDetail = Checkout::getPaymentDetail($paymentStatus->id, 'payments');
                        Log::channel('recurring_payments')->debug('paymentStatus');
                        Log::channel('recurring_payments')->debug(print_r($paymentDetail, true));
                        if ($paymentDetail->status == 'Captured') {
                            self::updateSubscription($record, $paymentDetail);
                            self::pushNotification($record, ['msg' => 'Your Subscription is renewed Thanks you'], 'subscription_renewal_captured');
                            break;
                        } else {
                            self::stopSubscriptionAndContent($record);
                            self::pushNotification($record, ['msg' => 'your subscription has stop because your payment problem'], 'subscription_renewal_fail');
                        }
                    } catch (\Throwable $th) {
                        Log::channel('recurring_payments')->debug($th->getMessage());
                    }
                }
                else
                {
                    self::captureThroughWallet($record);
                }
            }
        }
        return true;
    }

    public static function pushNotification($record, $msg, $title) {
        $input['subscriber_id'] = $record['subscriber_id'];
        $input['subscribed_id'] = $record['subscribed_id'];

        ProcessNotificationHelper::sendSubscriberReminderNotification($input, $record, $title, $msg);
    }

    public static function stopSubscriptionAndContent($record) {
        Subscription::where('id', $record['id'])->update(['is_archive' => 1]);
        return true;
    }

    public static function captureThroughWallet($record) {
        $inputs['customer_uuid'] = CommonHelper::getCutomerUUIDByid($record['subscriber_id']);
        $walletAmount = Wallet::getWalletTotalAmount($inputs);
        if ($walletAmount > $record['subscription_setting']['price']) {
            Log::channel('daily_change_status')->debug('wallet amount is greater');
            Wallet::insert(self::makerecurringWallPayment($record));
            self::updateSubscription($record);
            self::pushNotification($record, ['msg' => 'Your Subscription is renewed Thanks you'], 'subscription_renewal_captured');
        } else {
            Log::channel('daily_change_status')->debug('Wallet amount is smaller');
            self::stopSubscriptionAndContent($record);
            self::pushNotification($record, ['msg' => 'your subscription has stop because your payment problem'], 'subscription_renewal_fail');
        }
    }

    public static function makerecurringWallPayment($record) {
        return [
            'customer_id' => $record['subscriber_id'],
            'amount' => $record['subscription_setting']['price'],
            'type' => 'debit',
            'payment_status' => 'succeeded',
            'purchase_id' => $record['purchase']['id'],
        ];
    }

    public static function updateSubscription($record, $paymentDetail = '') {
        $date = new \DateTime(date('Y-m-d H:i:s'));
        $addedMonths = self::loopSize($record['subscription_setting']['type']);
        $date->modify('+' . $addedMonths . ' months');
        Subscription::where('id', $record['id'])->update(['subscription_end_date' => $date,'is_archive' => 0]);
        EarningAmount::CreateEarningRecords('subscription', $record['id']);

        $tPurchaseRec = Purchases::where('subscription_id', $record['id'])->first();
        if($tPurchaseRec) {
            $tPurchaseRec->circl_fee = $tPurchaseRec->circl_fee + $tPurchaseRec->circl_fee;
            $tPurchaseRec->transaction_charges = $tPurchaseRec->transaction_charges + $tPurchaseRec->transaction_charges;
            $tPurchaseRec->service_amount = $tPurchaseRec->service_amount + $tPurchaseRec->service_amount;
            $tPurchaseRec->total_amount = $tPurchaseRec->total_amount + $tPurchaseRec->total_amount;
            $tPurchaseRec->save();
        }

        $tr = PurchasesTransition::find($record['purchase']['purchases_transition']['id']);
        $newTr = $tr->replicate();
        $newTr->gateway_response = serialize($paymentDetail);
        $newTr->checkout_transaction_id = (isset($paymentDetail->id)) ? $paymentDetail->id : null;
        $newTr->save();

        return true;
    }

    public static function loopSize($condition) {
        if ($condition == 'monthly') {
            return 1;
        } elseif ($condition == 'quarterly') {
            return 3;
        } elseif ($condition == 'annual') {
            return 12;
        }
    }

}

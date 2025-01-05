<?php

namespace App\Helpers;

use App\FreelancerTransaction;
use App\PaymentDue;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

Class TransactionHelper {

//    public static $transaction_default = [
//        'commission_rate' => 5,
//    ];

    public static function saveSubscriptionTransaction($inputs = []) {
        $percentToGet = CommonHelper::$circle_commission['commision_rate_percentage'];
//        $hyperpay_rate = CommonHelper::$circle_commission['hyperpay_fee'];

        $percent = $hyperpay_fee = 0;
        if (!empty($inputs['total_amount'])) {
            $percent = self::getPercentOfAmount($inputs['total_amount'], $percentToGet);
            $hyperpay_fee =  $inputs['moyasar_fee'] ?? 0; // self::getPercentOfAmount($inputs['total_amount'], $hyperpay_rate);
        }
        $from_currency = $inputs['from_currency'];
        $to_currency = $inputs['to_currency'];
        // With Fixer
//        $exchange_rate = CommonHelper::getCurrencyExchangeRate($from_currency, $to_currency, $inputs['total_amount']);
        //$exchange_rate = CommonHelper::getExchangeRate($inputs['from_currency'], $inputs['to_currency']);
        $get_settings = \App\SubscriptionSetting::getSingleSubscriptionSetting('subscription_settings_uuid', $inputs['subscription_settings_uuid']);
        $freelancer_currency_amount = $get_settings['price'];
        $exchange_rate = CommonHelper::getExchangeRate($inputs['from_currency'], $inputs['to_currency']);
        $data = [
            'freelancer_transaction_uuid' => UuidHelper::generateUniqueUUID(),
            'customer_uuid' => !empty($inputs['subscriber_uuid']) ? $inputs['subscriber_uuid'] : null,
            'freelancer_uuid' => !empty($inputs['subscribed_uuid']) ? $inputs['subscribed_uuid'] : null,
            'transaction_id' => !empty($inputs['transaction_id']) ? $inputs['transaction_id'] : null,
            'content_uuid' => !empty($inputs['subscription_uuid']) ? $inputs['subscription_uuid'] : null,
            'transaction_type' => 'subscription',
            'transaction_user' => $inputs['login_user_type'],
            'actual_amount' => !empty($inputs['actual_amount']) ? $inputs['actual_amount'] : 0,
            'total_amount' => !empty($inputs['total_amount']) ? $inputs['total_amount'] : 0,
            'commission_rate' => !empty($percent) ? $percent : 0.0,
            'status' => 'confirmed',
            'transaction_date' => date('Y-m-d H:i:s'),
            'exchange_rate' => !empty($exchange_rate) ? $exchange_rate : null,
//            'freelancer_currency_amount' => !empty($freelancer_currency_amount) ? $freelancer_currency_amount : 0.0,
//            'exchange_rate' => !empty($inputs['exchange_rate']) ? $inputs['exchange_rate'] : null,
            'from_currency' => !empty($inputs['from_currency']) ? $inputs['from_currency'] : null,
            'to_currency' => !empty($inputs['to_currency']) ? $inputs['to_currency'] : null,
            'payment_brand' => !empty($inputs['payment_brand']) ? $inputs['payment_brand'] : null,
            'hyperpay_fee' => !empty($hyperpay_fee) ? $hyperpay_fee : 0.0,
            'circl_charges' => !empty($percent) ? $percent : 0.0,
        ];
        return FreelancerTransaction::saveTransaction($data);
    }

    public static function saveClassTransaction($inputs = []) {
        $percentToGet = CommonHelper::$circle_commission['commision_rate_percentage'];
//        $hyperpay_rate = CommonHelper::$circle_commission['hyperpay_fee'];
        $percent = $hyperpay_fee = 0;
        if (!empty($inputs['total_amount'])) {
            $percent = self::getPercentOfAmount($inputs['total_amount'], $percentToGet);
            $hyperpay_fee = $inputs['moyasar_fee'] ?? 0; //self::getPercentOfAmount($inputs['total_amount'], $hyperpay_rate);
        }
        $from_currency = $inputs['from_currency'];
        $to_currency = $inputs['to_currency'];
        // With Fixer
//        $exchange_rate = CommonHelper::getCurrencyExchangeRate($from_currency, $to_currency, $inputs['total_amount']);
        $exchange_rate = CommonHelper::getExchangeRate($inputs['from_currency'], $inputs['to_currency']);
        $data = [
            'freelancer_transaction_uuid' => UuidHelper::generateUniqueUUID(),
            'customer_uuid' => !empty($inputs['customer_uuid']) ? $inputs['customer_uuid'] : null,
            'freelancer_uuid' => !empty($inputs['freelancer_uuid']) ? $inputs['freelancer_uuid'] : null,
            'transaction_id' => !empty($inputs['transaction_id']) ? $inputs['transaction_id'] : null,
            'content_uuid' => !empty($inputs['class_booking_uuid']) ? $inputs['class_booking_uuid'] : null,
            'transaction_type' => 'class_booking',
            'transaction_user' => $inputs['login_user_type'],
            'actual_amount' => !empty($inputs['actual_amount']) ? $inputs['actual_amount'] : 0,
            'total_amount' => !empty($inputs['total_amount']) ? $inputs['total_amount'] : 0,
            'commission_rate' => !empty($percent) ? $percent : 0.0,
            'status' => 'confirmed',
            'transaction_date' => date('Y-m-d H:i:s'),
            'exchange_rate' => !empty($exchange_rate) ? $exchange_rate : null,
//            'exchange_rate' => !empty($inputs['exchange_rate']) ? $inputs['exchange_rate'] : null,
            'from_currency' => !empty($inputs['from_currency']) ? $inputs['from_currency'] : null,
            'to_currency' => !empty($inputs['to_currency']) ? $inputs['to_currency'] : null,
            'payment_brand' => !empty($inputs['payment_brand']) ? $inputs['payment_brand'] : null,
            'hyperpay_fee' => !empty($hyperpay_fee) ? $hyperpay_fee : 0.0,
            'circl_charges' => !empty($percent) ? $percent : 0.0,
        ];
        return FreelancerTransaction::saveTransaction($data);
    }

    public static function saveAppointmentTransaction($inputs = []) {
        $percentToGet = CommonHelper::$circle_commission['commision_rate_percentage'];
//        $hyperpay_rate = CommonHelper::$circle_commission['hyperpay_fee'];

        $percent = $hyperpay_fee = 0;
        if (!empty($inputs['paid_amount'])) {
            $percent = self::getPercentOfAmount($inputs['paid_amount'], $percentToGet);
            $hyperpay_fee = $inputs['moyasar_fee'] ?? 0; //Moyasar Fee     //self::getPercentOfAmount($inputs['paid_amount'], $hyperpay_rate);
        }
        $get_category = \App\FreelanceCategory::getCategory('freelancer_category_uuid', $inputs['service_uuid']);

        if (empty($get_category)):
            return ['success' => false, 'message' => FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['invalid_service_uuid']];
        endif;

        Log::info('Appointment save transaction: ', [
            'inputs' => $inputs,
            'percent' => $percent,
            'category' => $get_category,
            'service_uuid' => $inputs['service_uuid'] ?? '',
        ]);

//        $freelancer_currency_amount = $get_category['price'];
//        $from_currency = $get_category['currency'];
        $from_currency = $inputs['from_currency'] ?? $get_category['currency'];
//        $from_currency = $inputs['currency'];
        $to_currency = $inputs['to_currency'];
        // With Fixer
//        $exchange_rate = CommonHelper::getCurrencyExchangeRate($from_currency, $to_currency, $inputs['paid_amount']);
        $exchange_rate = CommonHelper::getExchangeRate($from_currency, $inputs['to_currency']);
//        $exchange_rate = CommonHelper::getExchangeRate($from_currency, $inputs['to_currency']);
        $data = [
            'freelancer_transaction_uuid' => UuidHelper::generateUniqueUUID(),
            'customer_uuid' => !empty($inputs['customer_uuid']) ? $inputs['customer_uuid'] : null,
            'freelancer_uuid' => !empty($inputs['freelancer_uuid']) ? $inputs['freelancer_uuid'] : null,
            'transaction_id' => !empty($inputs['transaction_id']) ? $inputs['transaction_id'] : null,
            'content_uuid' => !empty($inputs['appointment_uuid']) ? $inputs['appointment_uuid'] : null,
            'transaction_type' => 'appointment_bookoing',
            'transaction_user' => $inputs['login_user_type'],
            'actual_amount' => !empty($inputs['price']) ? $inputs['price'] : 0,
            'total_amount' => !empty($inputs['paid_amount']) ? $inputs['paid_amount'] : 0,
            'commission_rate' => !empty($percent) ? $percent : 0.0,
            'status' => 'confirmed',
            'transaction_date' => date('Y-m-d H:i:s'),
//            'freelancer_currency_amount' => !empty($freelancer_currency_amount) ? $freelancer_currency_amount : 0.0,
            'exchange_rate' => !empty($exchange_rate) ? $exchange_rate : null,
            'from_currency' => $from_currency,
            'to_currency' => !empty($inputs['to_currency']) ? $inputs['to_currency'] : null,
            'payment_brand' => !empty($inputs['payment_brand']) ? $inputs['payment_brand'] : null,
            'hyperpay_fee' => !empty($hyperpay_fee) ? $hyperpay_fee : 0.0,
            'circl_charges' => !empty($percent) ? $percent : 0.0,
        ];
        $transaction = FreelancerTransaction::saveTransaction($data);

        if (empty($transaction)):
            return ['success' => false, 'message' => FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['add_transaction_error'] ];
        endif;

        return ['success' => true, 'message' => 'success', 'transaction' => $transaction ];
    }

    public static function getPercentOfAmount($amount, $percent) {
        return ($percent / 100) * $amount;
    }

    public static function saveTransactionPaymentDue($save_transaction, $subscription_term, $dateActual) {
        $term = config('general.subscriptions.' . $subscription_term) ?? 1;
        $dateTransaction = $dateActual;
        $already_exist = PaymentDue::where('freelancer_transaction_uuid', $save_transaction['freelancer_transaction_uuid'])->where('status', 0)->first();
        if ($already_exist){
            return false;
        }
        if ($save_transaction['total_amount'] > 0){
            $amount = ($save_transaction['total_amount'] - ($save_transaction['commission_rate'] + $save_transaction['hyperpay_fee'])) / $term;
            if ($amount == 0) {
                return false;
            }
            $payment_dues = [];
            for ($i = 0; $i < $term; $i++) {
                if ($save_transaction['transaction_type'] == 'subscription') {
                    $date = Carbon::parse($dateTransaction)->addMonths(1)->timestamp;  // date('Y-m-d', strtotime('+1 month', strtotime($dateTransaction)));
                } else {
                    $date = Carbon::parse($dateTransaction)->addDays(1)->timestamp;    //$date = date('Y-m-d', strtotime('+1 day', strtotime($dateTransaction)));
                }
                $dateTransaction = $date;
                $payment_dues[$i]['payment_due_uuid'] = UuidHelper::generateUniqueUUID('payment_due');
                $payment_dues[$i]['user_uuid'] = $save_transaction['freelancer_uuid'];
                if ($save_transaction['transaction_type'] == 'subscription') {
                    $payment_dues[$i]['subscription_uuid'] = $save_transaction['content_uuid'];
                }
                if ($save_transaction['transaction_type'] == 'appointment_bookoing') {
                    $payment_dues[$i]['appointment_uuid'] = $save_transaction['content_uuid'];
                }
                if ($save_transaction['transaction_type'] == 'class_booking') {
                    $payment_dues[$i]['class_uuid'] = $save_transaction['content_uuid'];
                }
                $payment_dues[$i]['due_no'] = $i+1;
                $payment_dues[$i]['total_dues'] = $term;
                $payment_dues[$i]['amount'] = $amount;
                $payment_dues[$i]['from_currency'] = $save_transaction['from_currency'];
                $payment_dues[$i]['to_currency'] = $save_transaction['to_currency'];
                $payment_dues[$i]['exchange_rate'] = $save_transaction['exchange_rate'];
                $payment_dues[$i]['due_date'] = $date;
                $payment_dues[$i]['freelancer_transaction_uuid'] = $save_transaction['freelancer_transaction_uuid'];
                if (($save_transaction['from_currency'] == $save_transaction['to_currency']) && $save_transaction['from_currency'] == 'SAR') {
                    $payment_dues[$i]['pound_amount'] = $amount * $save_transaction['exchange_rate'];
                    $payment_dues[$i]['sar_amount'] = $amount;
                } else if (($save_transaction['from_currency'] == $save_transaction['to_currency']) && $save_transaction['from_currency'] == 'Pound') {
                    $payment_dues[$i]['sar_amount'] = $amount * $save_transaction['exchange_rate'];
                    $payment_dues[$i]['pound_amount'] = $amount;
                } else {
                    if ($save_transaction['from_currency'] == 'SAR') {
                        $payment_dues[$i]['sar_amount'] = $amount / $save_transaction['exchange_rate'];
                        $payment_dues[$i]['pound_amount'] = $amount;
                    } else {
                        $payment_dues[$i]['pound_amount'] = $amount / $save_transaction['exchange_rate'];
                        $payment_dues[$i]['sar_amount'] = $amount;
                    }
                }
            }
            $payment_dues_response = PaymentDue::savePaymentDue($payment_dues);
            return ($payment_dues_response == 1) ? true : false;
        } else{
            return true;
        }
    }

}

?>

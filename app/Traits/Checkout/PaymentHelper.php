<?php

namespace App\Traits\Checkout;

use App\Appointment;
use App\ClassBooking;
use App\Customer;
use App\CustomerCard;
use App\Helpers\CommonHelper;
use App\MadaCardsBin;
use App\PurchasesTransition;
use Illuminate\Support\Facades\URL;

trait PaymentHelper {

    public static function paymentParams($slug, $params, $paramsFor = null) {
        $bankParams = '';
        $customer = '';
        \Log::info(' in payment params');
        if (isset($params['customer_uuid'])) {
            $customer = Customer::where('customer_uuid', $params['customer_uuid'])->with('token')->first()->toArray();

            \Log::info('<=======customer=========>');
            \Log::info(print_r($customer, true));
        }
        $paramsFor = ($paramsFor == 'package') ? 'class' : $paramsFor;
        $slug = ($paramsFor != null && ($paramsFor == 'premium_folder' || $paramsFor == 'subscription' || $paramsFor == 'class' || (isset($params['class']) && $params['class'] == 'class'))) ? $paramsFor : $slug;
        \Log::info('<=======slug=========>');
        \Log::info(print_r($slug, true));

        switch ($slug) {
            case 'payments':
                $bankParams = self::bookingParams($params, $customer);
                break;
            case 'appointment_package':
                $bankParams = self::packageParams($params, $customer);
                break;
            case 'class':
                $bankParams = self::classParams($params, $customer);
                break;
            case 'paymentDetail':
                $bankParams = self::paymentDetailParams($params);
                break;

            case 'instruments':
                $bankParams = self::instrumentsParams($params, $customer);
                break;

            case 'capture':
                $bankParams = self::capturePaymentParams($params, $customer);
                break;

            case 'topUp':
                $bankParams = self::topPaymentParams($params, $customer);
                break;

            case 'subscription':
                $params['subscription'] = 'true';
                $bankParams = self::subscriptionPaymentParams($params, $customer);

                break;
            case 'premium_folder':
                $params['premiumFolder'] = 'true';
                $bankParams = self::premiumFolderPaymentParams($params, $customer);
                break;
            default:
                dd('no default');
        }
        \Log::info('bank params');
        \Log::info(print_r($bankParams, true));
        return $bankParams;
    }

    public static function paymentDetailParams($params) {
        return [
            'source' => [
                'type' => 'id',
                'id' => $params
            ],
            'currency' => self::currencyCheck($params['currency']),
        ];
    }

    public static function capturePaymentParams($params) {
        return [
            'source' => [
                'type' => 'id',
                'id' => 'sid_oo7t5olevnuelnhltilfookcrm'
            ],
            'amount' => 10000,
            'reference' => $params['payment_id']
        ];
    }

    public static function subscriptionPaymentParams($params, $customer) {
        if (env('ENVIRONMENT') == 'live') {
            $channel_id = env('CHECKOUT_CHANNEL_id');
        }
        else{
            $channel_id = env('CHECKOUT_SANDBOX_CHANNEL_id');
        }
        return [
            'source' => [
                'type' => 'id',
                'id' => $params['card_id']
            ],
            'amount' => $params['paid_amount'] * 100,
            'currency' => self::currencyCheck($params['currency']),
            "processing_channel_id"=> $channel_id,
            'capture' => true,
            "payment_type" => "Recurring",
            "merchant_initiated" => false,
            'customer' => [
                'email' => $customer['email'],
                'name' => $customer['first_name'] . ' ' . $customer['last_name']
            ],
            'success_url' => self::redirectUrl($params, $customer)['success_url'],
            'failure_url' => self::redirectUrl($params, $customer)['failure_url'],
            "3ds" => [
                "enabled" => true,
                "version" => "2.0.1"
            ]
        ];
    }

    public static function recurringSubscriptionPaymentParams($record) {

        $record['recurringSubscription'] = true;
        if (env('ENVIRONMENT') == 'live') {
            $channel_id = env('CHECKOUT_CHANNEL_id');
        }
        else{
            $channel_id = env('CHECKOUT_SANDBOX_CHANNEL_id');
        }
        return [
            'source' => [
                'type' => 'id',
                'id' => $record['purchase']['purchases_transition']['customer_cards']['card_id']
            ],
            'amount' => $record['subscription_setting']['price'] * 100,
            'currency' => self::currencyCheck($record['subscription_setting']['currency']),
            "processing_channel_id"=> $channel_id,
            'capture' => true,
            "payment_type" => "Recurring",
            "merchant_initiated" => true,
            "source.stored" => true,
            'success_url' => self::redirectUrl($record)['success_url'],
            'failure_url' => self::redirectUrl($record)['failure_url'],
            "previous_payment_id" => 'pay_eceg6eogpdj2zih4zxtz4cupne',
            "3ds" => [
                "enabled" => false,
                "version" => "2.0.1"
            ]
        ];
    }


    public static function classParams($params, $customer) {
        if (env('ENVIRONMENT') == 'live') {
            $channel_id = env('CHECKOUT_CHANNEL_id');
        }
        else{
            $channel_id = env('CHECKOUT_SANDBOX_CHANNEL_id');
        }
        return [
            'source' => [
                'type' => 'id',
                'id' => $params['card_id']
            ],
            'amount' => $params['paid_amount'] * 100,
            'currency' => self::currencyCheck($params['currency']),
            "processing_channel_id"=> $channel_id,
            'capture' => true,
            'customer' => [
                'email' => $customer['email'],
                'name' => $customer['first_name'] . ' ' . $customer['last_name']
            ],
            'success_url' => self::redirectUrl($params, $customer)['success_url'],
            'failure_url' => self::redirectUrl($params, $customer)['failure_url'],
            "3ds" => [
                "enabled" => true,
                "version" => "2.0.1"
            ]
        ];
    }

    public static function bookingParams($params, $customer) {
        if (env('ENVIRONMENT') == 'live') {
            $channel_id = env('CHECKOUT_CHANNEL_id');
        }
        else{
            $channel_id = env('CHECKOUT_SANDBOX_CHANNEL_id');
        }
        \Log::info('in booking params');
        return [
            'source' => [
                'type' => 'id',
                'id' => $params['card_id']
            ],
            'amount' => $params['paid_amount'] * 100,
            'currency' => self::currencyCheck($params['currency']),
            "processing_channel_id"=> $channel_id,
//            'currency' => self::currencyCheck($params['currency']),
            'capture' => false,
            'customer' => [
                'email' => $customer['email'],
                'name' => $customer['first_name'] . ' ' . $customer['last_name']
            ],
            'success_url' => self::redirectUrl($params, $customer)['success_url'],
            'failure_url' => self::redirectUrl($params, $customer)['failure_url'],
            "3ds" => [
                "enabled" => true,
//                "attempt_n3d" => true,
                "version" => "2.0.1"
            ]
        ];
    }

    public static function packageParams($params, $customer) {

        if (env('ENVIRONMENT') == 'live') {
            $channel_id = env('CHECKOUT_CHANNEL_id');
        }
        else{
            $channel_id = env('CHECKOUT_SANDBOX_CHANNEL_id');
        }
        return [
            'source' => [
                'type' => 'id',
                'id' => $params['card_id']
            ],
            'amount' => $params['package_paid_amount'] * 100,
            'currency' => self::currencyCheck($params['currency']),
            "processing_channel_id"=> $channel_id,
            'capture' => false,
            'customer' => [
                'email' => $customer['email'],
                'name' => $customer['first_name'] . ' ' . $customer['last_name']
            ],
            'success_url' => self::redirectUrl($params, $customer)['success_url'],
            'failure_url' => self::redirectUrl($params, $customer)['failure_url'],
            "3ds" => [
                "enabled" => true,
                "version" => "2.0.1"
            ]
        ];
    }

    public static function bookingParamsForApplePayment($params) {
        \Log::info('preparig apple params');
        $payment_type = 'Regular';
        $is_capture = true;
        if($params['purchasing_type'] == 'subscription'){
            $payment_type = "Recurring";
            $is_capture = true;
        }elseif($params['purchasing_type'] == 'appointment'){
            $payment_type = "Regular";
            $is_capture = false;
        }elseif($params['purchasing_type'] == 'class'){
            $payment_type = "Regular";
            $is_capture = true;
        }
        if (env('ENVIRONMENT') == 'live') {
            $channel_id = env('CHECKOUT_CHANNEL_id');
        }
        else{
            $channel_id = env('CHECKOUT_SANDBOX_CHANNEL_id');
        }
        return [
            'source' => [
                'type' => 'token',
                'token' => $params['apple_token']
            ],
            'amount' => $params['paid_amount'] * 100,
            'currency' => self::currencyCheck($params['currency']),
            "processing_channel_id"=> $channel_id,
            "payment_type" => $payment_type,
            'capture' => $is_capture,
            "merchant_initiated" => false,
            "3ds" => [
                "enabled" => true,
                "challenge_indicator" => "challenge_requested_mandate"
            ]
        ];
    }

    public static function bookingParamsForAppleRecurringPayment($record,$paymentDetail) {

        \Log::info('preparig apple params');
        $record['recurringSubscription'] = true;
        if (env('ENVIRONMENT') == 'live') {
            $channel_id = env('CHECKOUT_CHANNEL_id');
        }
        else{
            $channel_id = env('CHECKOUT_SANDBOX_CHANNEL_id');
        }
        return [
            'source' => [
                'type' => 'id',
                'id' => $paymentDetail->source->id
            ],
            'amount' => $record['subscription_setting']['price'] * 100,
            'currency' => self::currencyCheck($record['subscription_setting']['currency']),
            "processing_channel_id"=> $channel_id,
            "payment_type" => "Recurring",
            "previous_payment_id" => $record['purchase']['purchases_transition']['checkout_transaction_id'],
        ];
    }
    public static function premiumFolderParamsForApplePayment($params) {
        \Log::info('preparig apple params');
        return [
            'source' => [
                'type' => 'token',
                'token' => $params['apple_token']
            ],
            'amount' => $params['paid_amount'] * 100,
            'currency' => self::currencyCheck($params['currency']),
        ];
    }
    public static function currencyCheck($currency) {
        if ($currency == 'Pound') {
            return 'GBP';
        }

        if ($currency == 'SAR') {
            return 'SAR';
        }
    }

    public static function topPaymentParams($params, $customer) {
        if (env('ENVIRONMENT') == 'live') {
            $channel_id = env('CHECKOUT_CHANNEL_id');
        }
        else{
            $channel_id = env('CHECKOUT_SANDBOX_CHANNEL_id');
        }
        return [
            'source' => [
                'type' => 'id',
                'id' => $params['card_id'],
            ],
            'amount' => $params['paid_amount'] * 100,
            'currency' => self::currencyCheck($params['currency']),
            "processing_channel_id"=> $channel_id,
            'capture' => true,
            'customer' => [
                'email' => $customer['email'],
                'name' => $customer['first_name'] . ' ' . $customer['last_name']
            ],
            'success_url' => self::redirectUrl($params, $customer)['success_url'],
            'failure_url' => self::redirectUrl($params, $customer)['failure_url'],
            "3ds" => [
                "enabled" => true,
                "version" => "2.0.1"
            ]
        ];
    }
    public static function premiumFolderPaymentParams($params, $customer) {
        if (env('ENVIRONMENT') == 'live') {
            $channel_id = env('CHECKOUT_CHANNEL_id');
        }
        else{
            $channel_id = env('CHECKOUT_SANDBOX_CHANNEL_id');
        }
        return [
            'source' => [
                'type' => 'id',
                'id' => $params['card_id'],
            ],
            'amount' => $params['paid_amount'] * 100,
            'currency' => self::currencyCheck($params['currency']),
            "processing_channel_id"=> $channel_id,
            'capture' => true,
            'customer' => [
                'email' => $customer['email'],
                'name' => $customer['first_name'] . ' ' . $customer['last_name']
            ],
            'success_url' => self::redirectUrl($params, $customer)['success_url'],
            'failure_url' => self::redirectUrl($params, $customer)['failure_url'],
            "3ds" => [
                "enabled" => true,
                "version" => "2.0.1"
            ]
        ];
    }
    public static function redirectUrl($params, $customer = null) {
        \Log::info('=================params in redirect url');
        \Log::info(print_r($params, true));
        if (isset($params['topup'])) {
            return [
                'success_url' => URL::to('/') . "/paymentSuccessForTopUp?customer_id={$customer['id']}&wallet_id={$params['wallet_id']}",
                'failure_url' => URL::to('/') . "/paymentFailForTopUp?customer_id={$customer['id']}&wallet_id={$params['wallet_id']}",
            ];
        } elseif (isset($params['subscription'])) {
            return [
                'success_url' => URL::to('/') . "/paymentSuccessForSubscription?subscription_id={$params['purchase_transition']['purchase_transition']['id']}",
                'failure_url' => URL::to('/') . "/paymentFailForSubscription?subscription_id={$params['purchase_transition']['purchase_transition']['id']}",
            ];
        } elseif (isset($params['recurringSubscription'])) {
            return [
                'success_url' => URL::to('/') . "/paymentSuccessForRecurringSubscription?subscription_id={$params['id']}",
                'failure_url' => URL::to('/') . "/paymentFailForRecurringSubscription?subscription_id={$params['id']}",
            ];
        }elseif (isset($params['premiumFolder'])) {
            return [
                'success_url' => URL::to('/') . "/paymentSuccessForPremiumFolder?purchase_transition_id={$params['purchase_transition']['purchase_transition']['id']}",
                'failure_url' => URL::to('/') . "/paymentFailForPremiumFolder?purchase_transition_id={$params['purchase_transition']['purchase_transition']['id']}",
            ];
        } else {
            return [
                'success_url' => URL::to('/') . '/paymentSuccess?purchase_transition_id=' . $params['purchase_transition']['purchase_transition']['id'],
                'failure_url' => URL::to('/') . '/paymentFail?purchase_transition_id=' . $params['purchase_transition']['purchase_transition']['id'],
            ];
        }
    }

    public static function checkMadaCard($customer, $params) {

        $customerCard = CustomerCard::where('customer_id', $customer['id'])->where('card_id', $params['card_id'])->first();
        return MadaCardsBin::where('number', $customerCard->bin)->exists();
    }

    public static function instrumentsParams($params, $customer) {
        return [
            'type' => 'token',
            'token' => $params['token'],
            'customer' => [
                'email' => $customer['email'],
                'name' => $customer['first_name'] . ' ' . $customer['last_name']
            ]
        ];
    }

    public function RevertRecords($transitionId) {
        $records = PurchasesTransition::getPurchaseTransition($transitionId);
        return $this->checkBookingType($records);
    }

    public function checkBookingType($record) {

        if ($record[0]['purchase']['class_booking_id'] != null) {
            return ClassBooking::where('id', $record[0]['purchase']['class_booking_id'])->update(['is_archive' => 1]);
        } elseif ($record[0]['purchase']['appointment_id'] != null) {
            return Appointment::where('id', $record[0]['purchase']['appointment_id'])->update(['is_archive' => 1]);
        } elseif ($record[0]['purchase']['purchased_package_id'] != null) {
            Appointment::where('purchased_package_uuid', $record[0]['purchase']['purchased_package_id'])->update(['is_archive' => 1]);
            ClassBooking::where('purchased_package_uuid', $record[0]['purchase']['purchased_package_id'])->update(['is_archive' => 1]);
        }
    }

}

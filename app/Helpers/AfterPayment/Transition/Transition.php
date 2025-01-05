<?php

namespace App\Helpers\AfterPayment\Transition;

use App\ApplicationCharges;
use App\CurrencyConversion;
use App\Customer;
use App\CustomerCard;
use App\Freelancer;
use App\Helpers\AfterPayment\Transition\Interfaces\TransitionInterface;
use App\Helpers\CommonHelper;
use App\payment\Wallet\Repository\WalletRepo;
use App\Purchases;
use App\PurchasesTransition;
use App\Wallet;
use phpDocumentor\Reflection\Types\Self_;
use Illuminate\Support\Str;

class Transition implements TransitionInterface {

    public static function insertDataIntoTables($params, $paymentResponse, $appointment, $paramsFor) {
        $purchaseTransition = '';
        $commonData = '';
        if ($paramsFor == 'class') {
            $appointment['paid_amount'] = $params['paid_amount'];
            $commonData = self::getRawDataForClass($params, $paymentResponse, $appointment);
        }
        if ($paramsFor == 'appointment') {
            $commonData = self::getRawData($params, $paymentResponse, $appointment);
        }
        if ($paramsFor == 'package' || $paramsFor == 'appointment_package') {
            $appointment['paid_amount'] = $params['paid_amount'];
            $params['paramsFor'] = $paramsFor;
            $commonData = self::getRawDataForPackage($params, $paymentResponse, $appointment);
        }
        if ($paramsFor == 'subscription') {
            $commonData = self::getRawDataForSubscription($params, $paymentResponse, $appointment);
        }
        if ($paramsFor == 'premium_folder') {

            $commonData = self::getRawDataForPremiumFolder($params, $paymentResponse, $appointment);
            if($params['paid_amount'] < 0.1)
            {
                $params['status'] = 'completed';
            }
        }
        $purchase = Purchases::createPurchase(self::makeParamsArray($commonData));
        if (!empty($purchase)) {

            if ((isset($params['card_id'])) && $params['card_id'] == 'wallet') {
                $params['purchase_id'] = $purchase['id'];
                if ($paramsFor == 'package') {
                    $params['paid_amount'] = $appointment['package_paid_amount'];
                }
                $wallet = Wallet::create(WalletRepo::topUpInWallet($params));
                return ['res' => true, 'wallet' => $wallet];
            } else {
                $purchaseTransition = PurchasesTransition::createPurchase(self::makePurchaseTransition($params, $purchase, $paymentResponse, $commonData));
                if (!empty($purchaseTransition)) {
                    return ['res' => true, 'purchase_transition' => $purchaseTransition];
                }
            }
        }
        return ['res' => false];
    }

    public static function getPurchasedTypeID($purchase = []) {
        $id = null;
        if (!empty($purchase['appointment_id'])) {
            $id = $purchase['appointment_id'];
        }
        if (!empty($purchase['class_booking_id'])) {
            $id = $purchase['class_booking_id'];
        }
        if (!empty($purchase['purchased_package_id'])) {
            $id = $purchase['purchased_package_id'];
        }
        if (!empty($purchase['subscription_id'])) {
            $id = $purchase['subscription_id'];
        }
        return $id;
    }

    public static function makeWalletParams($params, $purchase, $paymentResponse, $commonData) {
        return [
            'customer_id' => $params['customer']['id'],
            'amount' => [''],
            'purchase_id' => [''],
            'type' => [''],
            'is_refunded' => [''],
            'customer_card_id' => [''],
            'checkout_transaction_reference' => [''],
        ];
    }

    public static function makeParamsArray($params) {
        return [
            'purchase_unique_id' => strtoupper(Str::random(10)),
            'customer_id' => $params['customer']['id'],
            'freelancer_id' => $params['freelancer']['id'],
            'purchase_datetime' => $params['data_time'],
            'type' => $params['type'],
            'purchased_by' => $params['purchased_by'],
            'purchased_in_currency' => $params['purchased_in_currency'],
            'service_provider_currency' => $params['service_provider_currency'],
            'conversion_rate' => $params['conversion_rate'],
            'appointment_id' => $params['appointment_id'],
            'class_booking_id' => $params['class_booking_id'],
            'purchased_package_id' => $params['purchased_package_id'],
            'subscription_id' => $params['subscription_id'],
            'purchased_premium_folders_id' => $params['purchased_premium_folders_id'],
            'customer_card_id' => $params['customer_card_id'],
            'circl_fee' => $params['circl_fee'],
            'transaction_charges' => $params['transaction_charges'],
            'service_amount' => $params['service_amount'],
            'total_amount' => $params['total_amount'],
            'discount' => $params['discount'],
            'discount_type' => $params['discount_type'],
            'total_amount_percentage' => $params['total_amount_percentage'],
            'tax' => $params['tax'],
            'circl_fee_percentage' => $params['circl_fee_percentage'],
            'is_refund' => $params['is_refund'],
            'status' => $params['status'],
        ];
    }

    public static function makePurchaseTransition($params, $purchase, $paymentResponse, $commonData) {

        return [
            'purchase_id' => $purchase['id'],
            'currency' => (isset($paymentResponse->currency)) ? $paymentResponse->currency : $params['currency'],
            'amount' => $commonData['total_amount'],
            'transaction_type' => $commonData['transaction_type'],
            'gateway_response' => serialize($paymentResponse),
            'request_parameters' => serialize($params),
            'transaction_status' => (isset($params['status'])) ? $params['status'] : null,
            'checkout_transaction_id' => (isset($paymentResponse->id)) ? $paymentResponse->id : null,
            'customer_card_id' => $commonData['customer_card_id'],
        ];
    }

    public static function globalRecods($params, $paymentResponse, $appointment) {
        $customer = Customer:: getSingleCustomer('customer_uuid', $params['customer_uuid']);
        $freelancerId = (isset($params['freelancer_uuid'])) ? $params['freelancer_uuid'] : CommonHelper::getFreelancerUuidByid($appointment['freelancer_id']);
        $freelancer = Freelancer:: getFreelancerDetail('freelancer_uuid', $freelancerId);
        return [
            'customer' => Customer:: getSingleCustomer('customer_uuid', $params['customer_uuid']),
            'freelancerId' => $freelancerId,
            'freelancer' => $freelancer,
            'rate' => CurrencyConversion::getCurrency($customer['default_currency'], $freelancer['default_currency']),
        ];
    }

    public static function getRawData($params, $paymentResponse, $appointment) {
        $globalRecords = self::globalRecods($params, $paymentResponse, $appointment);
        $customerCardId = null;
        $serviceAmount = null;
        $charges = \App\SystemSetting::getSystemSettings();
        if ((isset($params['card_id'])) && (!empty($params['card_id'])) && $params['card_id'] != 'wallet') {
            $customerCardId = CustomerCard::getCustomerCard($globalRecords['customer']['id'], $params['card_id']);
        }
        $purchasedBy = 'apple_pay';
        if ((isset($params['card_id'])) && (!empty($params['card_id']))) {
            $purchasedBy = 'card';
            if ($params['card_id'] == 'wallet') {
                $purchasedBy = 'wallet';
            }
        }
        if (isset($params['service_uuid']) && !empty($params['service_uuid'])) {
            $freelancerCategory = \App\FreelanceCategory::getCategory('freelancer_category_uuid', $params['service_uuid']);
            $serviceAmount = !empty($freelancerCategory) ? $freelancerCategory['price'] : null;
        }
        \Log::info('<==========service amount=============>');
        \Log::info(print_r($serviceAmount, true));
        return [
            'purchased_by' => $purchasedBy,
            'purchased_in_currency' => $globalRecords['customer']['default_currency'],
            'service_provider_currency' => $globalRecords['freelancer']['default_currency'],
            'status' => (isset($params['status'])) ? $params['status'] : null,
            'customer_card_id' => $customerCardId,
            'checkout_transaction_reference' => (isset($paymentResponse->id)) ? $paymentResponse->id : null,
            'customer' => $globalRecords['customer'],
            'freelancer' => $globalRecords['freelancer'],
            'data_time' => strtotime(date('Y-m-d H:i:s')),
            'type' => 'appointment',
            'conversion_rate' => ($globalRecords['rate'] != null) ? $globalRecords['rate']->rate : null,
            'appointment_id' => $appointment['id'],
            'class_booking_id' => null,
            'purchased_package_id' => null,
            'subscription_id' => null,
            'purchased_premium_folders_id'=>null,
            'circl_fee' => !empty($charges['circl_fee']) ? $charges['circl_fee'] : 0,
            'transaction_charges' => !empty($charges['transaction_charges']) ? $charges['transaction_charges'] : 0,
            'service_amount' => (isset($serviceAmount)) ? $serviceAmount : $globalRecords['freelancer']['freelancer_categories'][0]['price'],
            'total_amount' => $appointment['paid_amount'],
            'discount' => (isset($params['discount_amount'])) ? $params['discount_amount'] : null,
            'discount_type' => (isset($params['discount_type'])) ? $params['discount_type'] : null,
            'total_amount_percentage' => (isset($params['discount_amount'])) ? $params['discount_amount'] : null,
            'tax' => null,
            'circl_fee_percentage' => !empty($charges['circl_fee']) ? $charges['circl_fee'] : 0,
            'is_refund' => null,
            'transaction_type' => 'appointment_bookoing'
        ];
    }

    public static function getRawDataForClass($params, $paymentResponse, $appointment) {
        $globalRecords = self::globalRecods($params, $paymentResponse, $appointment);
        $customerCardId = null;
        $charges = \App\SystemSetting::getSystemSettings();
        if ((isset($params['card_id'])) && (!empty($params['card_id'])) && $params['card_id'] != 'wallet') {
            $customerCardId = CustomerCard::getCustomerCard($globalRecords['customer']['id'], $params['card_id']);
        }
        $purchasedBy = 'apple_pay';
        if ((isset($params['card_id'])) && (!empty($params['card_id']))) {
            $purchasedBy = 'card';
            if ($params['card_id'] == 'wallet') {
                $purchasedBy = 'wallet';
            }
        }
        return [
            'customer' => $globalRecords['customer'],
            'freelancer' => $globalRecords['freelancer'],
            'data_time' => strtotime(date('Y-m-d H:i:s')),
            'type' => 'class_booking',
            'purchased_by' => $purchasedBy,
            'purchased_in_currency' => $globalRecords['customer']['default_currency'],
            'service_provider_currency' => $globalRecords['freelancer']['default_currency'],
            'status' => 'succeeded',
            'customer_card_id' => $customerCardId,
            'checkout_transaction_reference' => (isset($paymentResponse->id)) ? $paymentResponse->id : null,
            'conversion_rate' => ($globalRecords['rate'] != null) ? $globalRecords['rate']->rate : null,
            'appointment_id' => null,
            'class_booking_id' => $appointment['id'],
            'purchased_package_id' => null,
            'subscription_id' => null,
            'purchased_premium_folders_id'=>null,
            'circl_fee' => !empty($charges['circl_fee']) ? $charges['circl_fee'] : 0,
            'transaction_charges' => !empty($charges['transaction_charges']) ? $charges['transaction_charges'] : 0,
            'circl_fee_percentage' => !empty($charges['circl_fee']) ? $charges['circl_fee'] : 0,
            'service_amount' => $params['actual_price'],
            'total_amount' => $appointment['paid_amount'],
            'discount' => $appointment['discount_amount'],
            'discount_type' => (isset($params['discount_type'])) ? $params['discount_type'] : null,
            'total_amount_percentage' => $appointment['discount_amount'],
            'tax' => null,
            'is_refund' => null,
            'transaction_type' => 'class_booking'
        ];
    }

    public static function getRawDataForPackage($params, $paymentResponse, $appointment) {
        $packagePrice = null;
        $globalRecords = self::globalRecods($params, $paymentResponse, $appointment);
        $packageId = CommonHelper::getPurchasedPackageIdByUuid($params['purchased_package_uuid']);
        $customerCardId = null;
        $charges = \App\SystemSetting::getSystemSettings();
        if ((isset($params['card_id'])) && (!empty($params['card_id'])) && $params['card_id'] != 'wallet') {
            $customerCardId = CustomerCard::getCustomerCard($globalRecords['customer']['id'], $params['card_id']);
        }
        $status = 'pending';
        if (isset($params['paramsFor']) && !empty($params['paramsFor']) && $params['paramsFor'] == 'package') {
            $status = 'succeeded';
        }
        $purchasedBy = 'apple_pay';
        if ((isset($params['card_id'])) && (!empty($params['card_id']))) {
            $purchasedBy = 'card';
            if ($params['card_id'] == 'wallet') {
                $purchasedBy = 'wallet';
            }
        }
        if ((isset($params['package_uuid'])) && (!empty($params['package_uuid']))) {
            $packageDetail = \App\Package::getPurchasedPackageDetails('package_uuid', $params['package_uuid']);
            if (!empty($packageDetail)) {
                $packagePrice = $packageDetail['price'];
            }
        }
        return [
            'purchased_by' => $purchasedBy,
            'purchased_in_currency' => $globalRecords['customer']['default_currency'],
            'service_provider_currency' => $globalRecords['freelancer']['default_currency'],
            'status' => $status,
//            'status' => (isset($params['status'])) ? $params['status'] : null,
            'customer_card_id' => $customerCardId,
            'checkout_transaction_reference' => (isset($paymentResponse->id)) ? $paymentResponse->id : null,
            'type' => 'package',
            'customer' => $globalRecords['customer'],
            'freelancer' => $globalRecords['freelancer'],
            'data_time' => strtotime(date('Y-m-d H:i:s')),
            'conversion_rate' => ($globalRecords['rate'] != null) ? $globalRecords['rate']->rate : null,
            'appointment_id' => null,
            'class_booking_id' => null,
            'purchased_package_id' => $packageId,
            'subscription_id' => null,
            'purchased_premium_folders_id' => null,
            'circl_fee' => !empty($charges['circl_fee']) ? $charges['circl_fee'] : 0,
            'transaction_charges' => !empty($charges['transaction_charges']) ? $charges['transaction_charges'] : 0,
            'circl_fee_percentage' => !empty($charges['circl_fee']) ? $charges['circl_fee'] : 0,
            'service_amount' => $packagePrice,
//            'service_amount' => $globalRecords['freelancer']['freelancer_categories'][0]['price'],
            'total_amount' => $params['paid_amount'],
            'discount' => $params['discount_amount'],
            'discount_type' => null,
            'total_amount_percentage' => $params['discount_amount'],
            'tax' => null,
            'is_refund' => null,
            'transaction_type' => 'package'
        ];
    }

    public static function updatePurchaseTransition($paymentResponse, $ckoId) {

        return [
            'gateway_response' => serialize($paymentResponse),
            'transaction_status' => (isset($paymentResponse->status)) ? $paymentResponse->status : null,
            'checkout_transaction_id' => (isset($paymentResponse->id)) ? $paymentResponse->id : null,
            'cko_id' => $ckoId
        ];
    }

    public static function getRawDataForSubscription($params, $paymentResponse, $subscription) {
        $globalRecords = self::globalRecods($params, $paymentResponse, $subscription);
        $customerCardId = null;
        $charges = \App\SystemSetting::getSystemSettings();
        if ((isset($params['card_id'])) && (!empty($params['card_id'])) && $params['card_id'] != 'wallet') {

            $customerCardId = CustomerCard::getCustomerCard($globalRecords['customer']['id'], $params['card_id']);
        }
        $purchasedBy = 'apple_pay';
        if ((isset($params['card_id'])) && (!empty($params['card_id']))) {
            $purchasedBy = 'card';
            if ($params['card_id'] == 'wallet') {
                $purchasedBy = 'wallet';
            }
        }
        return [
            'purchased_by' => $purchasedBy,
            'purchased_in_currency' => $globalRecords['customer']['default_currency'],
            'service_provider_currency' => $globalRecords['freelancer']['default_currency'],
            'status' => 'succeeded',
//            'status' => (isset($params['status'])) ? $params['status'] : null,
            'customer_card_id' => $customerCardId,
            'checkout_transaction_reference' => (isset($paymentResponse->id)) ? $paymentResponse->id : null,
            'type' => 'subscription',
            'customer' => $globalRecords['customer'],
            'freelancer' => $globalRecords['freelancer'],
            'data_time' => strtotime(date('Y-m-d H:i:s')),
            'conversion_rate' => ($globalRecords['rate'] != null) ? $globalRecords['rate']->rate : null,
            'appointment_id' => null,
            'class_booking_id' => null,
            'purchased_package_id' => null,
            'subscription_id' => $subscription['id'],
            'purchased_premium_folders_id' => null,
            'circl_fee' => !empty($charges['circl_fee']) ? $charges['circl_fee'] : 0,
            'transaction_charges' => !empty($charges['transaction_charges']) ? $charges['transaction_charges'] : 0,
            'circl_fee_percentage' => !empty($charges['circl_fee']) ? $charges['circl_fee'] : 0,
            'service_amount' => ((isset($globalRecords['freelancer']['freelancer_categories'])) && (!empty($globalRecords['freelancer']['freelancer_categories']))) ? $globalRecords['freelancer']['freelancer_categories'][0]['price'] : null,
            'total_amount' => $params['paid_amount'],
            'discount' => null,
            'discount_type' => null,
            'total_amount_percentage' => null,
            'tax' => null,
            'is_refund' => null,
            'transaction_type' => 'subscription'
        ];
    }
    public static function getRawDataForPremiumFolder($params, $paymentResponse, $purchasedPremiumFolder)
    {
        $globalRecords = self::globalRecods($params, $paymentResponse, $purchasedPremiumFolder);
        $charges = \App\SystemSetting::getSystemSettings();
        $customerCardId = null;
        if ((isset($params['card_id'])) && (!empty($params['card_id'])) && $params['card_id'] != 'wallet') {
            $customerCardId = CustomerCard::getCustomerCard($globalRecords['customer']['id'], $params['card_id']);
        }
        if ((isset($params['card_id'])) && (!empty($params['card_id']))) {
            $purchasedBy = 'card';
            if ($params['card_id'] == 'wallet') {
                $purchasedBy = 'wallet';
            }
        }
        elseif(isset($params['apple_token']))
        {
            $purchasedBy = 'apple_pay';
        }
        else
        {
            $purchasedBy = '';
        }
        $status = ($params['paid_amount'] < 0.1)?'completed':'succeeded';
        return [
            'purchased_by' => $purchasedBy,
            'purchased_in_currency' => $globalRecords['customer']['default_currency'],
            'service_provider_currency' => $globalRecords['freelancer']['default_currency'],
            'status' => $status,
            'customer_card_id' => $customerCardId,
            'checkout_transaction_reference' => (isset($paymentResponse->id)) ? $paymentResponse->id : null,
            'type' => 'premium_folder',
            'customer' => $globalRecords['customer'],
            'freelancer' => $globalRecords['freelancer'],
            'data_time' => strtotime(date('Y-m-d H:i:s')),
            'conversion_rate' => ($globalRecords['rate'] != null) ? $globalRecords['rate']->rate : null,
            'appointment_id' => null,
            'class_booking_id' => null,
            'purchased_package_id' => null,
            'subscription_id' => null,
            'purchased_premium_folders_id' => $purchasedPremiumFolder['id'],
            'circl_fee' => !empty($charges['circl_fee']) ? $charges['circl_fee'] : 0,
            'transaction_charges' => !empty($charges['transaction_charges']) ? $charges['transaction_charges'] : 0,
            'circl_fee_percentage' => !empty($charges['circl_fee']) ? $charges['circl_fee'] : 0,
            'service_amount' => ((isset($globalRecords['freelancer']['freelancer_categories'])) && (!empty($globalRecords['freelancer']['freelancer_categories']))) ? $globalRecords['freelancer']['freelancer_categories'][0]['price'] : null,
            'total_amount' => $params['paid_amount'],
            'discount' => null,
            'discount_type' => null,
            'total_amount_percentage' => null,
            'tax' => null,
            'is_refund' => null,
            'transaction_type' => 'premium_folder'
        ];
    }


}

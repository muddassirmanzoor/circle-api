<?php

namespace App\Traits;

use App\Appointment;
use App\ClassBooking;
use App\FreelancerEarning;
use App\Helpers\CommonHelper;
use App\Helpers\UuidHelper;
use App\payment\checkout\Checkout;
use App\PurchasedPremiumFolder;
use App\Subscription;
use App\SubscriptionMonthlyEntries;

trait EarningAmount {

    public static function CreateEarningRecords($type, $content_id) {
        $record = '';
        $params = '';
        $status = '';
        $captureParams = '';
        switch ($type) {
            case 'appointment':


                break;
            case 'package_appointment':
                $record = self::packageAppointmentEarningParams(Appointment::getAppointmentPackageWithPurchase($content_id));
                $params = $record['params'];
                $captureParams = $record['captures'];
                $status = $record['status'];
                break;

            case 'single_class_booking':
                $record = self::singleClassEarningParams(ClassBooking::getBookingWithPurchase($content_id));
                $params = $record['params'];
                $captureParams = $record['captures'];
                break;

            case 'multi_class_booking':
                $record = self::multipleClassEarningParams(ClassBooking::getClassPackageBookingWithPurchase($content_id));
                $params = $record['params'];
                $captureParams = $record['captures'];
                break;

            case 'subscription':
                $params = self::subscriptionEarningParams($content_id);
                return FreelancerEarning::createRecords($params);
                break;
            case 'premium_folder':
                $params = self::premiumFolderEarningParams($content_id);
                return FreelancerEarning::createRecords($params);
                break;

            default:
                dd('no default');
        }
        $earnedAmount = (isset($params['earned_amount'])) ? $params['earned_amount'] : $params[0]['earned_amount'];

        if ($earnedAmount > 0) {
            return FreelancerEarning::createRecords($params);
        }
        return true;
    }

    public static function appointmentEarningParams($appointment) {
        $record = $appointment[0];
        \Log::info('Appointment record');
        \Log::info(print_r($record, true));
//        $date = CommonHelper::convertMyDBDateIntoLocalDateTime($record['appointment_end_date_time'], $record['saved_timezone'], $record['local_timezone']);
        $end_date = date('Y-m-d H:i:s', $record['appointment_end_date_time']);
        $date = new \DateTime($end_date, new \DateTimeZone($record['local_timezone']));
//        $date->setTimezone(new DateTimeZone($record['local_timezone']));
//        $date->modify('+1 day');
        $date->modify('+1 hour');
        $dueDate = $date->format('Y-m-d H:i:s');
        $transaction = \App\Purchases::where('appointment_id', $record['id'])->first()->toArray();
        $circlFeeAmount = \App\Helpers\BankResponseHelper::prepareCirclFeeAmount($transaction);
        $transactionCharges = \App\Helpers\BankResponseHelper::prepareTransactionChargesAmount($transaction);

        return [
            'params' => [
                'freelancer_earnings_uuid' => UuidHelper::generateUniqueUUID('freelancer_earnings', 'freelancer_earnings_uuid'),
                'freelancer_id' => (isset($record['freelancer_id'])) ? $record['freelancer_id'] : null,
                'earned_amount' => $record['paid_amount'] - $circlFeeAmount - $transactionCharges,
                'purchase_id' => $record['purchase']['id'],
                'subscription_id' => null,
                'purchased_package_id' => null,
                'class_booking_id' => null,
                'appointment_id' => $record['id'],
                'amount_due_on' => strtotime($dueDate),
                'currency' => $record['currency'],
            ],
            'captures' => [
                'payment_by' => $record['purchase']['purchased_by'],
                'amount' => $record['paid_amount'],
                'cko_id' => (isset($record['purchase']['purchases_transition']['cko_id'])) ? $record['purchase']['purchases_transition']['cko_id'] : null,
                'payment_id' => (isset($record['purchase']['purchases_transition']['checkout_transaction_id'])) ? $record['purchase']['purchases_transition']['checkout_transaction_id'] : null,
            ],
            'status' => [
                'purchase_status' => 'succeeded',
                'purchase_transition_status' => 'Captured',
                'purchase_id' => $record['purchase']['id'],
                'purchase_transition_id' => (isset($record['purchase']['purchases_transition']['id'])) ? $record['purchase']['purchases_transition']['id'] : null,
            ]
        ];
    }

    public static function appointmentPackageEarningParams($appointments, $transaction) {
//        $record = $appointment[0];
        $data = [];
        if (!empty($appointments)) {
            foreach ($appointments as $key => $appointment) {
                $package_paid_amount = $appointment['package_paid_amount'];
//                $date = CommonHelper::convertMyDBDateIntoLocalDate($appointment['appointment_end_date_time'], $appointment['saved_timezone'], $appointment['local_timezone']);
                $end_date = date('Y-m-d H:i:s', $appointment['appointment_end_date_time']);
                $date = new \DateTime($end_date, new \DateTimeZone($appointment['local_timezone']));
//        $date->modify('+1 day');
                $date->modify('+1 hour');
                $dueDate = $date->format('Y-m-d H:i:s');
                $transaction['total_amount'] = $appointment['paid_amount'];
                $circlFeeAmount = \App\Helpers\BankResponseHelper::prepareCirclFeeAmount($transaction);
                $transactionCharges = \App\Helpers\BankResponseHelper::prepareTransactionChargesAmount($transaction);
                $data['params'][$key] = [
                    'freelancer_earnings_uuid' => UuidHelper::generateUniqueUUID('freelancer_earnings', 'freelancer_earnings_uuid'),
                    'freelancer_id' => (isset($appointment['freelancer_id'])) ? $appointment['freelancer_id'] : null,
                    'earned_amount' => $appointment['paid_amount'] - $circlFeeAmount - $transactionCharges,
                    'purchase_id' => $transaction['id'],
                    'subscription_id' => null,
                    'purchased_package_id' => $transaction['purchased_package_id'],
                    'class_booking_id' => null,
                    'appointment_id' => $appointment['id'],
                    'amount_due_on' => strtotime($dueDate),
                    'currency' => $appointment['currency'],
                ];
            }
        }
        $data['captures'] = [
            'payment_by' => $transaction['purchased_by'],
            'amount' => $package_paid_amount,
            'cko_id' => (isset($transaction['purchases_transition']['cko_id'])) ? $transaction['purchases_transition']['cko_id'] : null,
            'payment_id' => (isset($transaction['purchases_transition']['checkout_transaction_id'])) ? $transaction['purchases_transition']['checkout_transaction_id'] : null,
        ];
        $data ['status'] = [
            'purchase_status' => 'succeeded',
            'purchase_transition_status' => 'Captured',
            'purchase_id' => $transaction['id'],
            'purchase_transition_id' => (isset($transaction['purchases_transition']['id'])) ? $transaction['purchases_transition']['id'] : null,
        ];
        return $data;

        // get package last appointment and add one day in it to calculate amount due on
//        $lastAppointment = Appointment::where('purchased_package_uuid', $record['purchased_package_uuid'])
//                        ->orderBy('id', 'desc')
//                        ->first()->toArray();
//
//        $date = CommonHelper::convertMyDBDateIntoLocalDate($lastAppointment['appointment_end_date_time'], $lastAppointment['saved_timezone'], $lastAppointment['local_timezone']);
//        $date = new \DateTime($date);
//        $date->modify('+1 day');
//        $dueDate = $date->format('Y-m-d H:i:s');
//        $transaction = $record['purchase'];
//        $circlFeeAmount = \App\Helpers\BankResponseHelper::prepareCirclFeeAmount($transaction);
//        $transactionCharges = \App\Helpers\BankResponseHelper::prepareTransactionChargesAmount($transaction);
//        return [
//            'params' => [
//                'freelancer_earnings_uuid' => UuidHelper::generateUniqueUUID('freelancer_earnings', 'freelancer_earnings_uuid'),
//                'freelancer_id' => (isset($record['freelancer_id'])) ? $record['freelancer_id'] : null,
//                'earned_amount' => $record['package_paid_amount'] - $circlFeeAmount - $transactionCharges,
//                'purchase_id' => $record['purchase']['id'],
//                'subscription_id' => null,
//                'purchased_package_id' => $transaction['purchased_package_id'],
//                'class_booking_id' => null,
//                'appointment_id' => null,
//                'amount_due_on' => strtotime($dueDate),
//                'currency' => $record['currency'],
//            ],
//            'captures' => [
//                'payment_by' => $record['purchase']['purchased_by'],
//                'amount' => $record['package_paid_amount'],
//                'cko_id' => (isset($record['purchase']['purchases_transition']['cko_id'])) ? $record['purchase']['purchases_transition']['cko_id'] : null,
//                'payment_id' => (isset($record['purchase']['purchases_transition']['checkout_transaction_id'])) ? $record['purchase']['purchases_transition']['checkout_transaction_id'] : null,
//            ],
//            'status' => [
//                'purchase_status' => 'succeeded',
//                'purchase_transition_status' => 'Captured',
//                'purchase_id' => $record['purchase']['id'],
//                'purchase_transition_id' => (isset($record['purchase']['purchases_transition']['id'])) ? $record['purchase']['purchases_transition']['id'] : null,
//            ]
//        ];
    }

    public static function packageAppointmentEarningParams($appointment) {

        $record = $appointment[0];
        $date = CommonHelper::convertMyDBDateIntoLocalDate($record['appointment_end_date_time'], $record['saved_timezone'], $record['local_timezone']);
        $date = new \DateTime($date);
        $date->modify('+1 day');
        $dueDate = $date->format('Y-m-d H:i:s');
        $transaction = \App\Purchases::where('purchased_package_id', $record['purchased_package']['id'])->first()->toArray();
        $circlFeeAmount = \App\Helpers\BankResponseHelper::prepareCirclFeeAmount($transaction);
        $transactionCharges = \App\Helpers\BankResponseHelper::prepareTransactionChargesAmount($transaction);
        return [
            'params' => [
                'freelancer_earnings_uuid' => UuidHelper::generateUniqueUUID('freelancer_earnings', 'freelancer_earnings_uuid'),
                'freelancer_id' => $record['freelancer_id'],
                'earned_amount' => $record['paid_amount'] - $circlFeeAmount - $transactionCharges,
                'purchase_id' => $record['purchased_package']['purchase']['id'],
                'subscription_id' => null,
                'purchased_package_id' => $record['purchased_package']['id'],
                'class_booking_id' => null,
                'appointment_id' => null,
                'amount_due_on' => strtotime($dueDate),
                'currency' => $record['currency'],
            ],
            'captures' => [
                'payment_by' => $record['purchased_package']['purchase']['purchased_by'],
                'amount' => $record['paid_amount'],
                'cko_id' => (isset($record['purchased_package']['purchase']['purchases_transition']['cko_id'])) ? $record['purchased_package']['purchase']['purchases_transition']['cko_id'] : null,
                'payment_id' => (isset($record['purchased_package']['purchase']['purchases_transition']['checkout_transaction_id'])) ? $record['purchased_package']['purchase']['purchases_transition']['checkout_transaction_id'] : null,
            ],
            'status' => [
                'purchase_status' => 'succeeded',
                'purchase_transition_status' => 'Captured',
                'purchase_id' => $record['purchased_package']['purchase']['id'],
                'purchase_transition_id' => (isset($record['purchased_package']['purchase']['purchases_transition']['id'])) ? $record['purchased_package']['purchases_transition']['id'] : null,
            ]
        ];
    }

    public static function singleClassEarningParams($classBooking) {

        $record = $classBooking[0];
//        $date = CommonHelper::convertMyDBDateIntoLocalDate($record['schedule']['end_date_time'], $record['schedule']['saved_timezone'], $record['schedule']['local_timezone']);
        $end_date = date('Y-m-d H:i:s', $record['schedule']['end_date_time']);
        $date = new \DateTime($end_date, new \DateTimeZone($record['schedule']['local_timezone']));
//        $date->modify('+1 day');
        $date->modify('+1 hour');
        $dueDate = $date->format('Y-m-d H:i:s');
        $transaction = \App\Purchases::where('class_booking_id', $record['id'])->first()->toArray();
        $circlFeeAmount = \App\Helpers\BankResponseHelper::prepareCirclFeeAmount($transaction);
        $transactionCharges = \App\Helpers\BankResponseHelper::prepareTransactionChargesAmount($transaction);
        return [
            'params' => [
                'freelancer_earnings_uuid' => UuidHelper::generateUniqueUUID('freelancer_earnings', 'freelancer_earnings_uuid'),
                'freelancer_id' => $record['purchase']['freelancer_id'],
                'earned_amount' => $record['paid_amount'] - $circlFeeAmount - $transactionCharges,
                'purchase_id' => $record['purchase']['id'],
                'subscription_id' => null,
                'purchased_package_id' => null,
                'class_booking_id' => $record['id'],
                'appointment_id' => null,
                'amount_due_on' => strtotime($dueDate),
                'currency' => $record['currency'],
            ],
            'captures' => [
                'payment_by' => $record['purchase']['purchased_by'],
                'amount' => $record['paid_amount'],
                'cko_id' => (isset($record['purchase']['purchases_transition']['cko_id'])) ? $record['purchase']['purchases_transition']['cko_id'] : null,
                'payment_id' => (isset($record['purchase']['purchases_transition']['checkout_transaction_id'])) ? $record['purchase']['purchases_transition']['checkout_transaction_id'] : null,
            ]
        ];
    }

    public static function multipleClassEarningParams($classBooking) {
        $record = $classBooking[0];

//        $date = CommonHelper::convertMyDBDateIntoLocalDate($record['schedule']['end_date_time'], $record['schedule']['saved_timezone'], $record['schedule']['local_timezone']);
        $end_date = date('Y-m-d H:i:s', $record['schedule']['end_date_time']);
        $date = new \DateTime($end_date, new \DateTimeZone($record['schedule']['local_timezone']));
//        $date->modify('+1 day');
        $date->modify('+1 hour');
        $dueDate = $date->format('Y-m-d H:i:s');
        $transaction = \App\Purchases::where('purchased_package_id', $record['purchased_package']['id'])->first()->toArray();
        $transaction['total_amount'] = $record['paid_amount'];
        $circlFeeAmount = \App\Helpers\BankResponseHelper::prepareCirclFeeAmount($transaction);
        $transactionCharges = \App\Helpers\BankResponseHelper::prepareTransactionChargesAmount($transaction);
        return [
            'params' => [
                'freelancer_earnings_uuid' => UuidHelper::generateUniqueUUID('freelancer_earnings', 'freelancer_earnings_uuid'),
                'freelancer_id' => $record['purchased_package']['purchase']['freelancer_id'],
                'earned_amount' => $record['paid_amount'] - $circlFeeAmount - $transactionCharges,
                'purchase_id' => $record['purchased_package']['purchase']['id'],
                'subscription_id' => null,
                'purchased_package_id' => $record['purchased_package']['id'],
                'class_booking_id' => !empty($record['id']) ? $record['id'] : null,
                'appointment_id' => null,
                'amount_due_on' => strtotime($dueDate),
                'currency' => $record['currency'],
            ],
            'captures' => [
                'payment_by' => $record['purchased_package']['purchase']['purchased_by'],
                'amount' => $record['paid_amount'],
                'cko_id' => (isset($record['purchased_package']['purchase']['purchases_transition']['cko_id'])) ? $record['purchased_package']['purchase']['purchases_transition']['cko_id'] : null,
                'payment_id' => (isset($record['purchased_package']['purchase']['purchases_transition']['checkout_transaction_id'])) ? $record['purchased_package']['purchase']['purchases_transition']['checkout_transaction_id'] : null,
            ]
        ];
    }

    public static function subscriptionEarningParams($subscription_id) {
        $records = [];
        $subscriptionRecords = self::makeHelpingParams(Subscription::getSubscriptionWithSetting($subscription_id));
        $transaction = \App\Purchases::where('subscription_id', $subscription_id)->first()->toArray();
        $transaction['total_amount'] = $subscriptionRecords['record']['subscription_setting']['price'];
        $circlFeeAmount = \App\Helpers\BankResponseHelper::prepareCirclFeeAmount($transaction);
        $transactionCharges = \App\Helpers\BankResponseHelper::prepareTransactionChargesAmount($transaction);
        $startingDate = $subscriptionRecords['record']['subscription_date'];
        $subscriptionRecords['record']['subscription_setting']['price'] = $subscriptionRecords['record']['subscription_setting']['price'] - $circlFeeAmount - $transactionCharges;
        for ($i = 0; $i < $subscriptionRecords['size']; $i++) {
            $firstDate = date('Y-m-d', strtotime($startingDate . ' + 1 month'));
//            $firstDate = $startingDate;
            $date = new \DateTime($startingDate);
            $date->modify('+1 months');
            $startingDate = $date->format('Y-m-d H:i:s');
            //$records[] = self::mapOnTable($subscriptionRecords, $firstDate);

            // To immediatly available subscription earning
            $records[] = self::mapOnTable($subscriptionRecords, date("Y-m-d H:i:s"));
        }

        return $records;
    }
    public static function premiumFolderEarningParams($premium_folder_purchased_id) {
        $records = [];
        $transaction = \App\Purchases::where('purchased_premium_folders_id', $premium_folder_purchased_id)->first();
        $circlFeeAmount = \App\Helpers\BankResponseHelper::prepareCirclFeeAmount($transaction);
        $transactionCharges = \App\Helpers\BankResponseHelper::prepareTransactionChargesAmount($transaction);
        $record = self::prepareParamsOfPremiumFolderForEarningAmount($transaction,$circlFeeAmount,$transactionCharges);
        return $record;
    }
    public static function makeHelpingParams($subscription) {

        return [
            'size' => self::loopSize($subscription[0]['subscription_setting']['type']),
            'record' => $subscription[0]
        ];
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

    public static function mapOnTable($subscription, $startDate) {

        $record = $subscription['record'];
        return [
            'freelancer_earnings_uuid' => UuidHelper::generateUniqueUUID('freelancer_earnings', 'freelancer_earnings_uuid'),
            'freelancer_id' => $record['purchase']['freelancer_id'],
            'earned_amount' => ($record['subscription_setting']['price'] / $subscription['size']),
            'purchase_id' => $record['purchase']['id'],
            'subscription_id' => $record['id'],
            'purchased_package_id' => null,
            'class_booking_id' => null,
            'appointment_id' => null,
            'amount_due_on' => strtotime($startDate),
            'transfer_status' => 'completed',
            'currency' => $record['purchase']['service_provider_currency'],
        ];
    }

    public static function getPendingAmount($freelancerId) {
        return FreelancerEarning::where('freelancer_id', $freelancerId)->whereNull('freelancer_withdrawal_id')->sum('earned_amount');
    }

    public static function avaliableAmount($freelancerId) {
        return FreelancerEarning::where('freelancer_id', $freelancerId)
                        ->whereNull('freelancer_withdrawal_id')
                        ->where('amount_due_on', '<=', strtotime(date('Y-m-d H:i:s')))
                        ->sum('earned_amount');
    }

    public static function prepareParamsOfPremiumFolderForEarningAmount($purchaseObj,$circlFeeAmount,$transactionCharges)
    {
        // $date = new \DateTime($purchaseObj['created_at']);
        // //$date->modify('+1 day');
        // $date = $date->format('Y-m-d H:i:s');
        return [
            'freelancer_earnings_uuid' => UuidHelper::generateUniqueUUID('freelancer_earnings', 'freelancer_earnings_uuid'),
            'freelancer_id' => $purchaseObj['freelancer_id'],
            'earned_amount' => CommonHelper::getConvertedCurrency(($purchaseObj['total_amount'] - $circlFeeAmount - $transactionCharges), $purchaseObj['purchased_in_currency'], $purchaseObj['service_provider_currency']),
            'purchase_id' => $purchaseObj['id'],
            'purchased_premium_folders_id' => $purchaseObj['purchased_premium_folders_id'],
            'currency' => $purchaseObj['service_provider_currency'],
            'amount_due_on' => strtotime(date('Y-m-d H:i:s'))
        ];
    }
}

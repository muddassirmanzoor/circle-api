<?php

namespace App\Helpers;

use App\Classes;
use App\Folder;
use App\Package;
use App\Purchases;
use App\Subscription;

class BankResponseHelper {
    /*
      |--------------------------------------------------------------------------
      | BankResponseHelper that contains all the exception related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper Bank related stuff
      |
     */

    public static function setResponse($bank_detail) {

        $response = [];
        if (!empty($bank_detail)) {
            $response['bank_detail_uuid'] = $bank_detail['bank_detail_uuid'];
            $response['freelancer_uuid'] = CommonHelper::getRecordByUuid('freelancers', 'id', $bank_detail['freelancer_id'], 'freelancer_uuid');
            $response['account_name'] = $bank_detail['account_name'];
            $response['account_title'] = $bank_detail['account_title'];
            $response['iban_account_number'] = $bank_detail['location_type'] == 'KSA' ? $bank_detail['iban_account_number'] : $bank_detail['account_number'];
            $response['bank_name'] = !empty($bank_detail['bank_name']) ? $bank_detail['bank_name'] : null;
            $response['sort_code'] = !empty($bank_detail['sort_code']) ? $bank_detail['sort_code'] : null;
            $response['billing_address'] = !empty($bank_detail['billing_address']) ? $bank_detail['billing_address'] : null;
            $response['post_code'] = !empty($bank_detail['post_code']) ? $bank_detail['post_code'] : null;
            $response['location_type'] = $bank_detail['location_type'];
        }
        return $response;
    }

    public static function setTransactionTitle($data = []) {
        $title = null;
        if (!empty($data['appointment'])) {
            $title = $data['appointment']['title'];
        } elseif (!empty($data['class_book']['class_object'])) {
            $title = $data['class_book']['class_object']['name'];
        } elseif (!empty($data['subscription'])) {
            $title = ucfirst($data['subscription']['subscription_setting']['type']) . ' subscription';
        }
        return $title;
    }

    public static function decideName($content) {
        $name = null;
        $location = null;
        if ($content['appointment'] != null) {
            $name = $content['appointment']['title'];
            $location = $content['appointment']['address'];
        } elseif ($content['purchased_package_id'] != null) {
            $package = Package::where('id', $content['appointment_package']['package_id'])->first();
            if ($package != null) {
                $package = $package->toArray();
                $name = $package['package_name'];
                $location = (isset($content['appointment_package']['appointments']['address'])) ? $content['appointment_package']['appointments']['address'] : $content['appointment_package']['class_booking']['class_object']['address'];
            }
        } elseif (isset($content['classbooking'])) {
            $class = Classes::where('id', $content['classbooking']['class_id'])->first()->toArray();
            $name = $class['name'];
            $location = $class['address'];
        } elseif (isset($content['subscription_id'])) {
            $name = 'Subscription';
        } elseif (isset($content['premium_folder'])) {
            $name = Folder::where('id', $content['premium_folder']['folder_id'])->value('name');
        }else {
            $name = 'Default';
        }
        return [
            'name' => $name,
            'location' => $location
        ];
    }

    public static function getBookingDate($content) {
        $data = [];

        if (!empty($content['appointment_id'])) {
            $data['booking_date'] = date('Y-m-d H:i:s', $content['appointment']['appointment_start_date_time']);
        } elseif (isset($content['classbooking']) && !empty($content['classbooking'])) {
            $data['booking_date'] = date('Y-m-d H:i:s', $content['classbooking']['schedule']['start_date_time']);
        } else {
            $data['booking_date'] = null;
        }
        return $data;
    }

    public static function setTransactionResposne($transitions, $inputs) {
        $response = [];
        $userCurrency = self::getUserCurrency($inputs);

        foreach ($transitions as $key => $transition) {
            if($transition['subscription_id']){
                $subscription = Subscription::find($transition['subscription_id']);
                if($subscription['payment_status'] != 'captured'){
                    continue;
                }
            }
            $status = null;
            $circlFeeAmount = self::prepareCirclFeeAmount($transition);
            $transactionCharges = self::prepareTransactionChargesAmount($transition);
            if ($inputs['login_user_type'] == 'customer') {
                $currency = $transition['purchased_in_currency'];
                $purchasedCurrency = $transition['purchased_in_currency'];
                $amount = CommonHelper::getConvertedCurrency($transition['total_amount'], $currency, $userCurrency);
                $status = self::prepareCustomerTransactionStatuses($transition);
                if($transition['type'] == 'appointment' && $status == 'processing'){
                    unset($transitions[$key]);
                    continue;
                }
            } else {
                $prices['amount_paid_by_customer'] = CommonHelper::getConvertedCurrency($transition['total_amount'], $transition['purchased_in_currency'], $userCurrency);
                $prices['circl_charges'] = CommonHelper::getConvertedCurrency($circlFeeAmount, $transition['purchased_in_currency'], $userCurrency);
                $prices['transaction_charges'] = CommonHelper::getConvertedCurrency($transactionCharges, $transition['purchased_in_currency'], $userCurrency);
                // amount earned by freelancer
                $amount = ($prices['amount_paid_by_customer'] - $prices['circl_charges'] - $prices['transaction_charges']);
                $currency = $transition['service_provider_currency'];
                $purchasedCurrency = $transition['purchased_in_currency'];
                $status = self::prepareFreelancerTransactionStatuses($transition);
                if($inputs['type'] == 'pending' && $transition['type'] == 'package' && $status == 'completed'){
                    unset($transitions[$key]);
                    continue;
                }
                if($inputs['type'] == 'available' && $transition['type'] == 'package' && $status == 'pending'){
                    unset($transitions[$key]);
                    continue;
                }
            }
            $purchaseDateTime = CommonHelper::convertDateTimeToTimezone(date('Y-m-d H:i:s', strtotime($transition['created_at'])), 'UTC', $inputs['local_timezone']);
            $response[] = [
                //                'Id' => $transition['id'],
                'transaction_id' => !empty($transition['purchase_unique_id']) ? $transition['purchase_unique_id'] : null,
                'purchase_uuid' => $transition['purchases_uuid'],
                'name' => self::decideName($transition)['name'],
                'price' => $amount,
                'purchase_date' => date('Y-m-d', strtotime($purchaseDateTime)),
                'purchase_time' => date('H:i:s', strtotime($purchaseDateTime)),
                'currency' => $currency,
                'purchased_currency' => $purchasedCurrency,
                'type' => $transition['type'],
//                'currency' => (isset($transition['purchases_transition']) && !empty($transition['purchases_transition'])) ? $transition['purchases_transition']['currency'] : $transition['service_provider_currency'],
                'status' => $status,
                'refunded_amount' => self::prepareCustomerRefundAmount($transition),
                'earned_amount_by_freelancer' => $amount,
                'amount_paid_by_customer' => $transition['total_amount']
            ];
        }

        return $response;

        //        $timezone = $inputs['local_timezone'] ?? 'UTC';
        //        $login_user_type = $inputs['login_user_type'];
        //        $type = $inputs['type'];
        //
        //        $response = [];
        //        if (!empty($dataArray)) {
        //            $total_count = 0;
        //            $index = 0;
        //            foreach ($dataArray as $key => $value) {
        //                if ($login_user_type == "freelancer" && $value['transaction_type'] == 'subscription' && isset($value['payment_due']) && count($value['payment_due']) > 0) {
        //                    foreach ($value['payment_due'] as $due_payment) {
        //                        $diff = self::checkDateTimeDifference($value, $due_payment, $type);
        //                        if ($diff) {
        //                            $response[] = self::setTransactionListCommonResponse($value, $timezone, $login_user_type, $due_payment, $type);
        //                        }
        //                    }
        //                } else {
        //                    $diff = self::checkDateTimeDifference($value, [], $type);
        //                    if ($diff) {
        //                        $response[] = self::setTransactionListCommonResponse($value, $timezone, $login_user_type, [], $type);
        //                    }
        //                }
        //            }
        //        }
        //        return $response;
    }

    public static function checkDateTimeDifference($data, $due_payment, $list_type) {

        if ($list_type == "all") {
            return true;
        }

        if ($data['transaction_type'] == 'appointment_bookoing') {
            $appointment_time = $data['appointment']['appointment_date'] . ' ' . $data['appointment']['from_time'];
            $diff = CommonHelper::checkDateTimeDifferenceInMinutes($appointment_time);
        } elseif ($data['transaction_type'] == 'class_booking') {
            $appointment_time = $data['class_book']['schedule']['class_date'] . ' ' . $data['class_book']['schedule']['from_time'];
            $diff = CommonHelper::checkDateTimeDifferenceInMinutes($appointment_time);
        } elseif ($data['transaction_type'] == 'subscription') {

            $diff_mint = CommonHelper::checkDateTimeDifferenceInMinutes($due_payment['due_date'] ?? $data['transaction_date']);

            if ($list_type == "available" && $diff_mint >= 0) {
                return true;
            }
            if ($list_type == "pending" && $diff_mint < 0) {
                return true;
            }
        }

        if ($list_type == "available" && isset($diff)) {
            if ($diff >= 1440) {
                return true;
            }
        }
        if ($list_type == "pending" && isset($diff)) {
            if ($diff < 1440) {
                return true;
            }
        }

        return false;
    }

    public static function setTransactionListCommonResponse($value, $timezone, $login_user_type, $due_payment = [], $list_type) {

        $response['name'] = self::setTransactionTitle($value);
        $response['uuid'] = $value['freelancer_transaction_uuid'];
        $response['freelancer_uuid'] = $value['freelancer_uuid'];
        $response['payment_due_uuid'] = isset($due_payment['payment_due_uuid']) ? $due_payment['payment_due_uuid'] : null;
        $response['freelancer_name'] = !empty($value['freelancer']) ? $value['freelancer']['first_name'] . ' ' . $value['freelancer']['last_name'] : null;
        $response['freelancer_profile_image'] = !empty($value['freelancer']['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_image'] . $value['freelancer']['profile_image'] : null;
        $response['type'] = self::setTransactionType($value);
        $response['amount'] = self::getTransactionAndPaymentDueAmountForList($value, $login_user_type, $due_payment);
        $response['transaction_id'] = !empty($value['transaction_id']) ? $value['transaction_id'] : null;
        $response['travel_fee'] = self::calculateTravelFee($value);
        $response['date'] = date('d M Y', strtotime($value['transaction_date']));
        $time = date('H:i:s', strtotime($value['transaction_date']));
        $response['time'] = CommonHelper::convertTimeToTimezone($time, 'UTC', $timezone);
        $response['customer_name'] = !empty($value['customer']) ? $value['customer']['first_name'] . ' ' . $value['customer']['last_name'] : null;
        $response['customer_uuid'] = !empty($value['customer']) ? $value['customer']['customer_uuid'] : null;
        $response['profile_image'] = !empty($value['customer']['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_image'] . $value['customer']['profile_image'] : null;
        $response['payment_by'] = 'Visa';
        $response['transaction_status'] = self::prepareTransactionStatus($value);
        $response['currency'] = null;
        if ($login_user_type == "freelancer") {
            $response['currency'] = $value['freelancer']['default_currency'] ?? '';
        } elseif ($login_user_type == "customer") {
            $response['currency'] = $value['to_currency'];
        }
        $response['location'] = self::setTransactionLocation($value);
        $response['session_number'] = isset($due_payment['due_no']) ? $due_payment['due_no'] : 1;
        $response['total_session'] = isset($due_payment['total_dues']) ? $due_payment['total_dues'] : 1;
        $response['is_package'] = false;
        $response['session_status'] = null;
        if (!empty($value['appointment'])) {
            $response['session_status'] = $value['appointment']['status'];
        } elseif (!empty($value['class_book'])) {
            $response['session_status'] = $value['class_book']['status'];
        }
        if (!empty($value['appointment']['package_uuid'])) {
            $response['session_number'] = $value['appointment']['session_number'];
            $response['total_session'] = $value['appointment']['total_session'];
            $response['is_package'] = true;
        } elseif (!empty($value['class_book']['package_uuid'])) {
            $response['session_number'] = $value['class_book']['session_number'];
            $response['total_session'] = $value['class_book']['total_session'];
            $response['is_package'] = true;
        }

        if ($value['status'] == 'cancelled') {
            $response['is_refunded'] = isset($value['refund_transaction']['id']) ? 1 : 0;
            $response['refund_transaction_uuid'] = $value['refund_transaction']['refund_transaction_uuid'] ?? '';
            $response['refund_type'] = $value['refund_transaction']['refund_type'] ?? '';
            $response['refund_amount'] = $value['refund_transaction']['amount'] ?? 0;
            $response['refund_currency'] = $value['refund_transaction']['currency'] ?? '';

            $response['refund_amount'] = self::convertAmountToFreelancerCurrency($value['freelancer'], $login_user_type, $response['refund_amount'], $value['exchange_rate'], $value['to_currency']);
            $response['amount'] = $response['amount'] - $response['refund_amount'];
            $response['amount'] = $response['amount'] < 0 ? 0 : $response['amount'];
        }

        return $response;
    }

    public static function getTransactionAndPaymentDueAmountForList($value, $login_user_type, $payment_due = []) {

        $amount = 0;
        if (isset($payment_due['payment_due_uuid'])) {
            if (isset($value['freelancer']['default_currency']) && strtolower($value['freelancer']['default_currency']) == 'pound') {
                $amount = $payment_due['pound_amount'];
            }
            if (isset($value['freelancer']['default_currency']) && strtolower($value['freelancer']['default_currency']) == 'sar') {
                $amount = $payment_due['sar_amount'];
            }
        } else {
            if (!empty($login_user_type) && $login_user_type == 'customer') {
                $amount = $value['total_amount'];
            } else {
                $amount = $value['total_amount'] - ($value['circl_charges'] + $value['hyperpay_fee']);
                $amount = self::convertAmountToFreelancerCurrency($value['freelancer'], $login_user_type, $amount, $value['exchange_rate'], $value['to_currency']);
            }
        }

        return round($amount, 2);
    }

    public static function convertAmountToFreelancerCurrency($freelancer, $login_user_type, $amount, $exchangeRate, $convertedCurr) {
        if (!empty($login_user_type) && $login_user_type == 'customer') {
            $amount = $amount;
        } else {

            if (isset($freelancer['default_currency']) && strtolower($freelancer['default_currency']) == strtolower($convertedCurr)) {
                $amount = $amount;
            } else {
                if (!empty($exchangeRate) && $exchangeRate >= 0) {
                    $amount = $amount / $exchangeRate;
                }
                $amount = round($amount, 2);
            }
        }
        return $amount;
    }

    public static function prepareTransactionStatus($data) {
        $status = null;
        if (!empty($data['appointment'])) {
            if ($data['appointment']['status'] == "pending" || $data['appointment']['status'] == "confirmed") {
                $status = "pending";
            } elseif ($data['appointment']['status'] == "completed") {
                $status = "completed";
            } elseif ($data['appointment']['status'] == "cancelled") {
                $status = "cancelled";
            } elseif ($data['appointment']['status'] == "rejected") {
                $status = "rejected";
            }
        } elseif (!empty($data['class_book'])) {
            if ($data['class_book']['status'] == "pending" || $data['class_book']['status'] == "confirmed") {
                $status = "pending";
            } elseif ($data['class_book']['status'] == "completed") {
                $status = "completed";
            } elseif ($data['class_book']['status'] == "cancelled") {
                $status = "cancelled";
            } elseif ($data['class_book']['status'] == "rejected") {
                $status = "rejected";
            }
        }
        return $status;
    }

    public static function getTotalAppointmentCount($data) {
        $count_array = [];
        if (!empty($data)) {
            $appointment_data = \App\Appointment::getAppointmentWithPurchasedPackages('purchased_package_uuid', $value['appointment']['purchased_package_uuid']);
            $count_array['total_count'] = count($appointment_data);
            foreach ($appointment_data as $key => $appointment) {
                if ($appointment['purchased_package_uuid'] == $data['purchased_package_uuid']) {

                }
            }
        }
    }

    //    public static function setAppointmentsResponse($appointments = [], $response) {
    //        if (!empty($appointments)) {
    //            foreach ($appointments as $appointment) {
    //                $key = count($response) + 1;
    //                $response[$key] = self::setAppointResponse($appointment);
    //            }
    //        }
    //        return $response;
    //    }
    public static function processImage($image, $imageType, $type) {

        $result = ThumbnailHelper::processThumbnails($image, $imageType, $type);
        if ($result['success'] == true) {
            return $result['data'];
        } else {
            return null;
        }
    }

    public static function setTransactionDetailResponse($transition = [], $inputs = []) {
        $response = [];
        $status = null;
        $finalResponse = []; // Initialize $finalResponse here
        if (!empty($transition)) {
            $data = self::getBookingDate($transition);
            $status = self::getTransactionStatusAccordingToType($transition, $inputs);
            $getPrices = self::getAllRelatedPrices($transition, $inputs);
            $purchaseDateTime = CommonHelper::convertDateTimeToTimezone(date('Y-m-d H:i:s', strtotime($transition['created_at'])), 'UTC', $inputs['local_timezone']);
            $response = [

                'transaction_id' => !empty($transition['purchase_unique_id']) ? $transition['purchase_unique_id'] : null,

                'purchase_uuid' => !empty($transition['purchase_unique_id']) ? $transition['purchase_unique_id'] : null,

                'name' => self::decideName($transition)['name'],
                'booking_date' => date('Y-m-d', strtotime($data['booking_date'])),
                'booking_time' => date('H:i:s', strtotime($data['booking_date'])),

                'purchase_date' => date('Y-m-d', strtotime($purchaseDateTime)),
                'purchase_time' => date('H:i:s', strtotime($purchaseDateTime)),

                'status' => $status,
                'purchase_type' => $transition['type'],
                'payment_by' => $transition['purchased_by'],
                'refunded_amount' => self::prepareCustomerRefundAmount($transition),
                'customer' => [
                    'first_name' => $transition['customer']['first_name'],
                    'last_name' => $transition['customer']['last_name'],
                    'profile_images' => CustomerResponseHelper::customerProfileImagesResponse($transition['customer']['profile_image']),
                ],
                'freelancer' => [
                    'first_name' => $transition['freelancer']['first_name'],
                    'last_name' => $transition['freelancer']['last_name'],
                    'profile_images' => FreelancerResponseHelper::freelancerProfileImagesResponse($transition['freelancer']['profile_image']),
                ],
                'location' => self::decideName($transition)['location']
            ];
            $finalResponse = array_merge($response, $getPrices);
        }
        return $finalResponse;
    }

    public static function prepareCirclFeeAmount($transition = []) {
        $percentInAmount = 0;
        if (!empty($transition['circl_fee_percentage'])) {
            $divide = $transition['circl_fee_percentage'] / 100;
            $percentInAmount = $transition['total_amount'] * $divide;
        }
        return $percentInAmount;
    }

    public static function prepareCustomerTransactionStatuses($transition = []) {
        $status = null;
        if (!empty($transition['appointment'])) {
            if ($transition['appointment']['status'] == 'pending') {
                $status = 'pending';
            }
            if (($transition['appointment']['status'] == 'rejected') || $transition['appointment']['status'] == 'cancelled') {
                $status = 'refunded';
            }
            if ($transition['appointment']['status'] == 'confirmed') {
                $status = 'confirmed';
            }
            if ($transition['appointment']['status'] == 'completed') {
                $status = 'completed';
            }
            if ($transition['appointment']['status'] == 'processing') {
                $status = 'processing';
            }
        }
        if (!empty($transition['appointment_package']['appointments'])) {
            if ($transition['status'] == 'pending') {
                $status = 'pending';
            }
            if (($transition['status'] == 'rejected') || $transition['status'] == 'cancelled' || $transition['status'] == 'refunded') {
                $status = 'refunded';
            }
            if (($transition['status'] == 'voided')) {
                $status = 'rejected';
            }
            if ($transition['status'] == 'succeeded' || $transition['status'] == 'confirmed') {
                $status = 'confirmed';
            }
            if ($transition['status'] == 'completed') {
                $status = 'completed';
            }
        }
        if (!empty($transition['classbooking'])) {
            if ($transition['classbooking']['status'] == 'pending') {
                $status = 'pending';
            }
            if (($transition['classbooking']['status'] == 'rejected') || $transition['classbooking']['status'] == 'cancelled') {
                $status = 'refunded';
            }
            if ($transition['classbooking']['status'] == 'confirmed') {
                $status = 'confirmed';
            }
            if ($transition['classbooking']['status'] == 'completed') {
                $status = 'completed';
            }
        }
        if (!empty($transition['appointment_package']['class_booking'])) {
            if ($transition['status'] == 'pending') {
                $status = 'pending';
            }
            if (($transition['status'] == 'rejected') || $transition['status'] == 'cancelled' || $transition['status'] == 'refunded') {
                $status = 'refunded';
            }
            if ($transition['status'] == 'succeeded' || $transition['status'] == 'confirmed') {
                $status = 'confirmed';
            }
            if ($transition['status'] == 'completed') {
                $status = 'completed';
            }
            if (($transition['status'] == 'voided')) {
                $status = 'rejected';
            }
        }
        if (!empty($transition['subscription'])) {
            if ($transition['status'] == 'pending') {
                $status = 'pending';
            }
            if (($transition['status'] == 'cancelled')) {
                $status = 'refunded';
            }
            if ($transition['status'] == 'succeeded') {
                $status = 'confirmed';
            }
            if ($transition['status'] == 'completed' || $transition['subscription']['subscription_end_date'] < date('Y-m-d H:i:s')) {
                $status = 'completed';
            }
        }
        if (!empty($transition['premium_folder'])) {
            if ($transition['status'] == 'pending') {
                $status = 'pending';
            }
            if (($transition['status'] == 'cancelled')) {
                $status = 'refunded';
            }
            if ($transition['status'] == 'succeeded') {
                $status = 'confirmed';
            }
            if ($transition['status'] == 'completed') {
                $status = 'completed';
            }
        }
        return $status;
    }

    public static function prepareFreelancerTransactionStatuses($transition = []) {
        $status = null;
        if (!empty($transition)) {
            if ($transition['status'] == 'succeeded') {
                if($transition['type'] =='premium_folder' && $transition['total_amount'] >= 0)
                {
                    $status = 'completed';
                    return $status;
                }
                if($transition['type'] == 'subscription' && $transition['total_amount'] >= 0)
                {
                    $status = 'completed';
                    return $status;
                }
                $status = 'pending';
            }
            if (($transition['status'] == 'refunded') || $transition['status'] == 'cancelled' || $transition['status'] == 'voided') {
                $status = 'cancelled';
            }
            if ($transition['status'] == 'completed') {
                $status = 'completed';
            }
            if ($transition['status'] == 'pending') {
                $status = 'pending';
            }
            if($transition['type'] == 'package' && $transition['status'] == 'succeeded') {

                foreach ($transition['appointment_package']['all_appointments'] as $key => $appointment) {
                    if (!empty($appointment)) {
                        if (count($transition['appointment_package']['all_appointments'] ) === 1 && $appointment['status'] === 'cancelled') {
                            $status = 'cancelled';
                        } else {
                            // Check if all appointments are either 'cancelled' or 'completed'
                            $allAppointmentsValid = collect($transition['appointment_package']['all_appointments'])->every(function ($appointment) {
                                return in_array($appointment['status'], ['completed', 'cancelled']);
                            });

                            // Update the package status based on appointments
                            $status = $allAppointmentsValid ? 'completed' : 'pending';
                        }
                    }
                }
                foreach ($transition['appointment_package']['all_class_bookings'] as $key => $booking) {
                    if (!empty($booking)) {
                        if (count($transition['appointment_package']['all_class_bookings'] ) === 1 && $booking['status'] === 'cancelled') {
                            $status = 'cancelled';
                        } else {
                            // Check if all classes are either 'cancelled' or 'completed'
                            $allAppointmentsValid = collect($transition['appointment_package']['all_class_bookings'])->every(function ($booking) {
                                return in_array($booking['status'], ['completed', 'cancelled']);
                            });

                            // Update the package status based on classes
                            $status = $allAppointmentsValid ? 'completed' : 'pending';
                        }
                    }
                }
            }
            // Subscription amount will be immediatly available
            //if (!empty($transition['subscription']) && ($transition['subscription']['subscription_end_date']) < date('Y-m-d H:i:s')) {
            if (!empty($transition['subscription'])) {
                $status = 'completed';
            }
        }
        return $status;
    }

    public static function prepareFreelancerClassBookingStatuses($transition = []) {
        $status = null;
        if (!empty($transition)) {
            if ($transition['status'] == 'confirmed') {
                $status = 'pending';
            }
            if (($transition['status'] == 'refunded') || $transition['status'] == 'cancelled' || $transition['status'] == 'voided') {
                $status = 'cancelled';
            }
            if ($transition['status'] == 'completed') {
                $status = 'completed';
            }
            if ($transition['status'] == 'pending') {
                $status = 'pending';
            }
        }
        return $status;
    }

    public static function prepareTransactionChargesAmount($transition = []) {
        $percentInAmount = 0;
        if (!empty($transition['transaction_charges'])) {
            $divide = $transition['transaction_charges'] / 100;
            $percentInAmount = $transition['total_amount'] * $divide;
        }
        return $percentInAmount;
    }

    public static function getAllRelatedPrices($transition = [], $inputs = []) {
        $prices = [];
        if (!empty($transition)) {
            // appointment case
            if (!empty($transition['appointment_id'])) {
                $prices = self::prepareAppointmentPrices($transition, $inputs);
            }
            if (!empty($transition['class_booking_id'])) {
                $prices = self::prepareClassBookingPrices($transition, $inputs);
            }
            if (!empty($transition['purchased_package_id'])) {
                $prices = self::preparePackagePrices($transition, $inputs);
            }

            if (!empty($transition['subscription_id'])) {
                $prices = self::prepareSubscriptionPrices($transition, $inputs);
            }
            if (!empty($transition['purchased_premium_folders_id'])) {
                $prices = self::preparePremiumFolderPrices($transition, $inputs);
            }
        }
        return $prices;
    }

    public static function prepareAppointmentPrices($transition = [], $inputs = []) {
        $prices = [];
        // appointment case
        if (($inputs['login_user_type'] == 'customer')) {
            $prices = self::prepareCustomerAppointmentPrices($transition, $inputs);
        } elseif (($inputs['login_user_type'] == 'freelancer')) {
            $prices = self::prepareFreelancerAppointmentPrices($transition, $inputs);
        }
        return $prices;
    }

    public static function prepareClassBookingPrices($transition = [], $inputs = []) {
        $prices = [];
        // appointment case
        if (($inputs['login_user_type'] == 'customer')) {
            $prices = self::prepareCustomerClassBookingPrices($transition, $inputs);
        } elseif (($inputs['login_user_type'] == 'freelancer')) {
            $prices = self::prepareFreelancerClassBookingPrices($transition, $inputs);
        }
        return $prices;
    }

    public static function preparePackagePrices($transition = [], $inputs = []) {
//        $prices = [];
        // appointment case
        $prices = self::prepareCustomerPackagePrices($transition, $inputs);

//        if (($inputs['login_user_type'] == 'customer')) {
//            $prices = self::prepareCustomerPackagePrices($transition, $inputs);
//        } elseif (($inputs['login_user_type'] == 'freelancer')) {
//            $prices = self::prepareCustomerPackagePrices($transition, $inputs);
//        }
        return $prices;
    }

    public static function prepareSubscriptionPrices($transition = [], $inputs = []) {
        $prices = [];
        // subscription case
        if (($inputs['login_user_type'] == 'customer')) {
            $prices = self::prepareCustomerSubscriptionPrices($transition, $inputs);
        } elseif (($inputs['login_user_type'] == 'freelancer')) {
            $prices = self::prepareFreelancerSubscriptionPrices($transition, $inputs);
        }
        return $prices;
    }
    public static function preparePremiumFolderPrices($transition = [], $inputs = []) {
        $prices = [];
        // subscription case
        if (($inputs['login_user_type'] == 'customer')) {
            $prices = self::prepareCustomerPremiumFolderPrices($transition, $inputs);
        } elseif (($inputs['login_user_type'] == 'freelancer')) {
            $prices = self::prepareFreelancerPremiumFolderPrices($transition, $inputs);
        }
        return $prices;
    }
    public static function getTransactionStatusAccordingToType($transition = [], $inputs = []) {
        $status = [];
        // subscription case
        if (($inputs['login_user_type'] == 'customer')) {
            $status = self::prepareCustomerTransactionStatuses($transition);
        } elseif (($inputs['login_user_type'] == 'freelancer')) {
            $status = self::prepareFreelancerTransactionStatuses($transition, $inputs);
        }
        return $status;
    }

    public static function prepareCustomerSubscriptionPrices($transition = [], $inputs = []) {
        $appointmentSubscription = !empty($transition['appointment_subscription']) ? $transition['appointment_subscription'] : [];
        $customerCurrency = \App\Customer::where('customer_uuid', '=', $inputs['logged_in_uuid'])->first()->default_currency;
        $response = [];

        if (!empty($appointmentSubscription)) {
            $circlFeeAmount = self::prepareCirclFeeAmount($transition);
            $response['currency'] = $transition['purchased_in_currency'];
            $response['amount_paid_by_customer'] = $transition['total_amount'];
//            $response['amount_paid_by_customer'] = CommonHelper::getConvertedCurrency($transition['total_amount'], $appointmentSubscription['subscription_setting']['currency'], $customerCurrency);
//            $response['circl_charges'] = CommonHelper::getConvertedCurrency($circlFeeAmount, $appointmentSubscription['subscription_setting']['currency'], $customerCurrency);
            $response['circl_charges'] = $circlFeeAmount;
//            $response['amount_paid_by_customer'] = $transition['total_amount'];
//            $response['circl_charges'] = $circlFeeAmount;
//            $response['transaction_charges'] = CommonHelper::getConvertedCurrency($transition['transaction_charges'], $appointmentSubscription['subscription_setting']['currency'], $customerCurrency);
            $response['transaction_charges'] = $transition['transaction_charges'];
//            $response['promo_discount'] = !empty($transition['discount']) ? CommonHelper::getConvertedCurrency($transition['discount'], $appointmentSubscription['subscription_setting']['currency'], $customerCurrency) : 0.0;
            $response['promo_discount'] = !empty($transition['discount']) ? $transition['discount'] : 0.0;
            $response['earned_amount_by_freelancer'] = ($response['amount_paid_by_customer'] - $response['circl_charges'] - $response['transaction_charges']);
            $response['due_date'] = !empty($appointmentSubscription['appointment_subscription']['subscription_end_date']) ? $transition['appointment_subscription']['subscription_end_date'] : null;
            $response['subscription_type'] = !empty($appointmentSubscription['subscription_setting']) ? config('general.subscription_type.' . $transition['appointment_subscription']['subscription_setting']['type']) : null;
            $response['purchase_detail'] = !empty($appointmentSubscription['subscription_setting']) ? ( $transition['appointment_subscription']['subscription_setting']['type'] . '' . ' subscription') : null;
            $response['status'] = 'confirmed';
            if ($transition['appointment_subscription']['subscription_end_date'] < date('Y-m-d H:i:s')) {
                $response['status'] = 'completed';
            }
        }
        return $response;
    }
    public static function prepareCustomerPremiumFolderPrices($transition = [], $inputs = []) {
        $premiumFolder = !empty($transition['premium_folder']) ? $transition['premium_folder'] : [];
        $customerCurrency = \App\Customer::where('customer_uuid', '=', $inputs['logged_in_uuid'])->first()->default_currency;
        $response = [];
        if (!empty($premiumFolder)) {
            $circlFeeAmount = self::prepareCirclFeeAmount($transition);
            $response['currency'] = $transition['purchased_in_currency'];
            $response['amount_paid_by_customer'] = $transition['total_amount'];
            $response['purchase_detail'] = Folder::where('id', $transition['premium_folder']['folder_id'])->value('name');
        }
        return $response;
    }

    public static function prepareFreelancerPremiumFolderPrices($transition = [], $inputs = []) {
            $premiumFolder = !empty($transition['premium_folder']) ? $transition['premium_folder'] : [];
            $freelancerCurrency = \App\Freelancer::where('freelancer_uuid', $inputs['logged_in_uuid'])->first()->default_currency;
            $response = [];
            if (!empty($premiumFolder)) {
                $circlFeeAmount = self::prepareCirclFeeAmount($transition);
                $response['currency'] = $transition['service_provider_currency'];
                $response['amount_paid_by_customer'] = CommonHelper::getConvertedCurrency($transition['total_amount'], $transition['purchased_in_currency'], $freelancerCurrency);
                $response['circl_charges'] = CommonHelper::getConvertedCurrency($circlFeeAmount, $transition['purchased_in_currency'], $freelancerCurrency);
                $transactionCharges = self::prepareTransactionChargesAmount($transition);
                $response['transaction_charges'] = CommonHelper::getConvertedCurrency($transactionCharges, $transition['purchased_in_currency'], $freelancerCurrency);
                $response['promo_discount'] = !empty($transition['discount']) ? ($transition['discount']) : 0.0;
                $response['earned_amount_by_freelancer'] = ($response['amount_paid_by_customer'] - $response['circl_charges'] - $response['transaction_charges']);
                $transition['earned_amount_by_freelancer'] = $response['earned_amount_by_freelancer'];
                $response['purchase_detail'] = Folder::where('id', $transition['premium_folder']['folder_id'])->value('name');

            }
            return $response;
        }
    public static function prepareFreelancerSubscriptionPrices($transition = [], $inputs = []) {
        $appointmentSubscription = !empty($transition['appointment_subscription']) ? $transition['appointment_subscription'] : [];
        $freelancerCurrency = \App\Freelancer::where('freelancer_uuid', $inputs['logged_in_uuid'])->first()->default_currency;
        $response = [];
        if (!empty($appointmentSubscription)) {
            $circlFeeAmount = self::prepareCirclFeeAmount($transition);
            $response['currency'] = $transition['service_provider_currency'];
            $response['amount_paid_by_customer'] = CommonHelper::getConvertedCurrency($transition['total_amount'], $transition['purchased_in_currency'], $freelancerCurrency);
//            $response['amount_paid_by_customer'] = $transition['total_amount'];
            $response['circl_charges'] = CommonHelper::getConvertedCurrency($circlFeeAmount, $transition['purchased_in_currency'], $freelancerCurrency);
//            $response['circl_charges'] = $circlFeeAmount;
            $transactionCharges = self::prepareTransactionChargesAmount($transition);
            $response['transaction_charges'] = CommonHelper::getConvertedCurrency($transactionCharges, $transition['purchased_in_currency'], $freelancerCurrency);
//            $response['transaction_charges'] = $transition['transaction_charges'];
            $response['promo_discount'] = !empty($transition['discount']) ? ($transition['discount']) : 0.0;
//            $response['promo_discount'] = !empty($transition['discount']) ? $transition['discount'] : 0.0;
            $response['earned_amount_by_freelancer'] = ($response['amount_paid_by_customer'] - $response['circl_charges'] - $response['transaction_charges']);
            $response['due_date'] = !empty($appointmentSubscription['subscription_start_date']) ? $appointmentSubscription['subscription_start_date'] : null;
            $response['subscription_type'] = !empty($appointmentSubscription['subscription_setting']) ? config('general.subscription_type.' . $appointmentSubscription['subscription_setting']['type']) : null;
            $response['purchase_detail'] = !empty($appointmentSubscription['subscription_setting']) ? ($appointmentSubscription['subscription_setting']['type']) . ' Subscription' : null;
            $transition['earned_amount_by_freelancer'] = $response['earned_amount_by_freelancer'];
            $response['subscriptions'] = self::prepareSubscriptionFreelancerEarning($transition, $inputs);
        }
        return $response;
    }

    public static function prepareSubscriptionFreelancerEarning($transition, $inputs) {
        $response = [];

        $date = strtotime(date('Y-m-d H:i:s'));
        $currency = $transition['service_provider_currency'];
        if (!empty($transition['freelancer_earning'])) {
            $count = count($transition['freelancer_earning']);

            foreach ($transition['freelancer_earning'] as $key => $earning) {
                $response[$key]['currency'] = $currency;
                $response[$key]['status'] = 'completed';
                $response[$key]['earned_amount'] = $transition['earned_amount_by_freelancer'] / $count;
                $response[$key]['amount_due_on'] = CommonHelper::convertDateTimeToTimezone(date('Y-m-d H:i:s', ($earning['amount_due_on'])), 'UTC', $inputs['local_timezone']);
//                $response[$key]['amount_due_on'] = date('Y-m-d H:i:s', $earning['amount_due_on']);
                if (!empty($transition['appointment_subscription']) && $transition['appointment_subscription']['subscription_end_date'] < date('Y-m-d H:i:s')) {
                    $response[$key]['status'] = 'completed';
                }
//                if (!empty($earning['freelancer_withdrawal_id'])) {
//                    $response[$key]['status'] = 'completed';
//                }
            }
        }
        return $response;
    }

    public static function prepareCustomerAppointmentPrices($transition = [], $inputs = []) {
        $appointment = !empty($transition['appointment']) ? $transition['appointment'] : [];
        $prices = [];
        // get customer currency in which we have to return the prices
        $customerCurrency = \App\Customer::where('customer_uuid', '=', $inputs['logged_in_uuid'])->first()->default_currency;
        if (!empty($appointment)) {
            $bookingDateTime = !empty($appointment['appointment_start_date_time']) ? CommonHelper::convertDateTimeToTimezone(date('Y-m-d H:i:s', $appointment['appointment_start_date_time']), 'UTC', $inputs['local_timezone']) : null;
            $travelFee = self::distanceResponse($transition['freelancer'], $appointment['travelling_distance']);
            $prices['booking_name'] = !empty($appointment['title']) ? $appointment['title'] : null;
            $prices['booking_date'] = date('Y-m-d', strtotime($bookingDateTime));
            $prices['booking_time'] = date('H:i:s', strtotime($bookingDateTime));
            $prices['purchase_detail'] = 'Single appointment';
            $prices['appointment_status'] = $appointment['status'];
            $prices['type'] = ($appointment['is_online'] == 1) ? 'online' : 'face to face';
            $prices['currency'] = $transition['purchased_in_currency'];
            $prices['travel_fee']['distance'] = $travelFee['distance'];
//            $prices['travel_fee']['distance_cost'] = $travelFee['distance_cost'];
            $prices['travel_fee']['distance_cost'] = CommonHelper::getConvertedCurrency($travelFee['distance_cost'], $transition['freelancer']['freelancer_categories'][0]['currency'], $customerCurrency);
            $prices['travel_fee']['total_distance_cost'] = CommonHelper::getConvertedCurrency($travelFee['total_distance_cost'], $transition['freelancer']['freelancer_categories'][0]['currency'], $customerCurrency);
//            $prices['travel_fee']['total_distance_cost'] = $travelFee['total_distance_cost'];
            //            $prices['travel_fee'] = self::distanceResponse($transition['freelancer'], $appointment['travelling_distance']);
            $prices['service_amount'] = CommonHelper::getConvertedCurrency($transition['service_amount'], $transition['freelancer']['freelancer_categories'][0]['currency'], $customerCurrency);
            $prices['promo_discount'] = !empty($transition['discount']) ? $transition['discount'] : 0.0;
//            $prices['promo_discount'] = !empty($transition['discount']) ? CommonHelper::getConvertedCurrency($transition['discount'], $transition['freelancer']['freelancer_categories'][0]['currency'], $customerCurrency) : 0.0;
            $finalAmount = ($prices['service_amount'] + $prices['travel_fee']['total_distance_cost']) - ($prices['promo_discount']);
            $prices['final_amount'] = CommonHelper::getConvertedCurrency($finalAmount, $appointment['currency'], $customerCurrency);
//            $prices['amount_paid_by_customer'] = CommonHelper::getConvertedCurrency($transition['total_amount'], $appointment['currency'], $customerCurrency);
//            $prices['promo_discount'] = !empty($transition['discount']) ? $transition['discount'] : 0.0;
            $prices['amount_paid_by_customer'] = $appointment['paid_amount'];
        }
        return $prices;
    }

    public static function prepareCustomerClassBookingPrices($transition = [], $inputs = []) {
        $booking = !empty($transition['classbooking']) ? $transition['classbooking'] : [];
        $customerCurrency = \App\Customer::where('customer_uuid', '=', $inputs['logged_in_uuid'])->first()->default_currency;
        $prices = [];
        if (!empty($booking)) {
            $bookingDateTime = !empty($transition['classbooking']['schedule']['start_date_time']) ? CommonHelper::convertDateTimeToTimezone(date('Y-m-d H:i:s', $transition['classbooking']['schedule']['start_date_time']), 'UTC', $inputs['local_timezone']) : null;
            $prices['currency'] = $transition['purchased_in_currency'];
            $prices['booking_date'] = date('Y-m-d', strtotime($bookingDateTime));
            $prices['booking_time'] = date('H:i:s', strtotime($bookingDateTime));
            $prices['booking_name'] = !empty($booking['class_object']['name']) ? $booking['class_object']['name'] : null;
            $prices['purchase_detail'] = 'Single class';
            $prices['booking_status'] = $booking['status'];
            $prices['type'] = !empty($booking['class_object']['online_link']) ? 'online' : 'face to face';
            //            $prices['type'] = ($appointment['is_online'] == 1) ? 'online' : 'face to face';
            //            $prices['travel_fee'] = self::distanceResponse($transition['freelancer'], $appointment['travelling_distance']);
            $prices['service_amount'] = $booking['actual_price'];
            $prices['status'] = $booking['status'];
            $prices['travel_fee'] = null;
//            $prices['travel_fee'] = CommonHelper::getConvertedCurrency($booking['travelling_charges'], $booking['currency'], $customerCurrency);
            $prices['promo_discount'] = !empty($transition['discount']) ? CommonHelper::getConvertedCurrency($transition['discount'], $booking['currency'], $customerCurrency) : 0.0;
//            $prices['service_amount'] = $transition['service_amount'];
//            $prices['travel_fee'] = $booking['travelling_charges'];
//            $prices['promo_discount'] = !empty($transition['discount']) ? $transition['discount'] : 0.0;
            $prices['final_amount'] = ($prices['service_amount'] + $prices['travel_fee']) - ($prices['promo_discount']);
            $prices['amount_paid_by_customer'] = $booking['paid_amount'];
//            $prices['amount_paid_by_customer'] = CommonHelper::getConvertedCurrency($transition['total_amount'], $booking['currency'], $customerCurrency);
//            $prices['amount_paid_by_customer'] = $transition['total_amount'];
        }
        return $prices;
    }

    public static function prepareCustomerPackagePrices($transition = [], $inputs = []) {
        if (!empty($transition['appointment_package']['appointments'])) {
            // appointment package
            $data = self::prepareAppointmentPackageData($transition, $inputs);
        } elseif (!empty($transition['appointment_package']['class_booking'])) {
            // class package
            $data = self::prepareClassPackageData($transition, $inputs);
        }
//        $appointmentPackage = !empty($transition['appointment_package']['appointments']) ? $transition['appointment_package']['appointments'] : $transition['appointment_package']['class_booking'];
//        $customerCurrency = \App\Customer::where('customer_uuid', '=', $inputs['logged_in_uuid'])->first()->default_currency;
//        $prices = [];
//        if (!empty($appointmentPackage)) {
//            $bookingDateTime = !empty($appointmentPackage['appointments']['appointment_start_date_time']) ? CommonHelper::convertDateTimeToTimezone(date('Y-m-d H:i:s', $appointmentPackage['appointments']['appointment_start_date_time']), 'UTC', $inputs['local_timezone']) : null;
//            $prices['booking_date'] = date('Y-m-d', strtotime($bookingDateTime));
//            $prices['booking_time'] = date('H:i:s', strtotime($bookingDateTime));
//            $prices['booking_name'] = !empty($appointmentPackage['appointments']['title']) ? $appointmentPackage['appointments']['title'] : null;
//            $prices['purchase_detail'] = !empty($appointmentPackage['package']['package_name']) ? $appointmentPackage['package']['package_name'] : null;
//            $prices['type'] = ($appointmentPackage['appointments']['is_online'] == 1) ? 'online' : 'face to face';
//            //            $prices['travel_fee'] = self::distanceResponse($transition['freelancer'], $appointmentPackage['appointments']['travelling_distance']);
//            $travelFee = self::distanceResponse($transition['freelancer'], $appointmentPackage['appointments']['travelling_distance']);
//            $prices['travel_fee']['distance'] = $travelFee['distance'];
//            $prices['travel_fee']['distance_cost'] = CommonHelper::getConvertedCurrency($travelFee['distance_cost'], $appointmentPackage['appointments']['currency'], $customerCurrency);
//            $prices['travel_fee']['total_distance_cost'] = CommonHelper::getConvertedCurrency($travelFee['total_distance_cost'], $appointmentPackage['appointments']['currency'], $customerCurrency);
////            $prices['travel_fee']['distance_cost'] = $travelFee['distance_cost'];
////            $prices['travel_fee']['total_distance_cost'] = $travelFee['total_distance_cost'];
//            $prices['service_amount'] = CommonHelper::getConvertedCurrency($transition['service_amount'], $appointmentPackage['appointments']['currency'], $customerCurrency);
//            $prices['promo_discount'] = !empty($transition['discount']) ? CommonHelper::getConvertedCurrency($transition['discount'], $appointmentPackage['appointments']['currency'], $customerCurrency) : 0.0;
//            $prices['travel_fee']['total_distance_cost'] = CommonHelper::getConvertedCurrency($prices['travel_fee']['total_distance_cost'], $appointmentPackage['appointments']['currency'], $customerCurrency);
////            $prices['service_amount'] = $transition['service_amount'];
////            $prices['promo_discount'] = !empty($transition['discount']) ? $transition['discount'] : 0.0;
//            $prices['final_amount'] = ($prices['service_amount'] + $prices['travel_fee']['total_distance_cost']) - ($prices['promo_discount']);
//            $prices['amount_paid_by_customer'] = CommonHelper::getConvertedCurrency($transition['total_amount'], $appointmentPackage['appointments']['currency'], $customerCurrency);
//
////            $prices['amount_paid_by_customer'] = $transition['total_amount'];
//        }
        return $data;
    }

    public static function prepareAppointmentPackageData($transition, $inputs) {
        $appointmentPackage = !empty($transition['appointment_package']['appointments']) ? $transition['appointment_package']['appointments'] : $transition['appointment_package']['class_booking'];
        $userCurrency = self::getUserCurrency($inputs);
        $prices = [];
        $count = 0;
        if (!empty($appointmentPackage)) {
            $bookingDateTime = !empty($appointmentPackage['appointment_start_date_time']) ? CommonHelper::convertDateTimeToTimezone(date('Y-m-d H:i:s', $appointmentPackage['appointment_start_date_time']), 'UTC', $inputs['local_timezone']) : null;
            $prices['booking_date'] = date('Y-m-d', strtotime($bookingDateTime));
            $prices['currency'] = $transition['purchased_in_currency'];
            $prices['booking_time'] = date('H:i:s', strtotime($bookingDateTime));
            $prices['booking_name'] = !empty($appointmentPackage['title']) ? $appointmentPackage['title'] : null;
            $prices['purchase_detail'] = !empty($transition['appointment_package']['package']['package_name']) ? $transition['appointment_package']['package']['package_name'] : null;
            $prices['type'] = ($appointmentPackage['is_online'] == 1) ? 'online' : 'face to face';
            //            $prices['travel_fee'] = self::distanceResponse($transition['freelancer'], $appointmentPackage['appointments']['travelling_distance']);
            foreach ($transition['appointment_package']['all_appointments'] as $key => $appointment) {
                if (!empty($appointment)) {
                    $travelFee = self::distanceResponse($transition['freelancer'], $appointment['travelling_distance']);
                    $distance[$count] = $travelFee['distance'];
                    $distance_cost[$count] = $travelFee['distance_cost'];
                    $total_distance_cost[$count] = $travelFee['total_distance_cost'];
                    $count++;
                }
            }
//            $travelFee = self::distanceResponse($transition['freelancer'], $appointmentPackage['travelling_distance']);
//            $prices['travel_fee']['distance'] = $travelFee['distance'];
//            $prices['travel_fee']['distance_cost'] = CommonHelper::getConvertedCurrency($travelFee['distance_cost'], $transition['service_provider_currency'], $userCurrency);
//            $prices['travel_fee']['total_distance_cost'] = CommonHelper::getConvertedCurrency($travelFee['total_distance_cost'], $transition['service_provider_currency'], $userCurrency);
            $prices['travel_fee']['distance'] = array_sum($distance);
            $prices['travel_fee']['distance_cost'] = CommonHelper::getConvertedCurrency(array_sum($distance_cost), $transition['service_provider_currency'], $userCurrency);
            $prices['travel_fee']['total_distance_cost'] = CommonHelper::getConvertedCurrency(array_sum($total_distance_cost), $transition['service_provider_currency'], $userCurrency);
//            $prices['travel_fee']['distance_cost'] = $travelFee['distance_cost'];
//            $prices['travel_fee']['total_distance_cost'] = $travelFee['total_distance_cost'];
            $prices['service_amount'] = CommonHelper::getConvertedCurrency($transition['appointment_package']['package']['price'], $transition['service_provider_currency'], $userCurrency);
            $prices['promo_discount'] = !empty($transition['discount']) ? ($transition['discount']) : 0.0;
//            $prices['service_amount'] = $transition['service_amount'];
//            $prices['promo_discount'] = !empty($transition['discount']) ? $transition['discount'] : 0.0;
            $prices['final_amount'] = ($prices['service_amount'] + $prices['travel_fee']['total_distance_cost']) - ($prices['promo_discount']);
            $prices['amount_paid_by_customer'] = CommonHelper::getConvertedCurrency($transition['total_amount'], $appointmentPackage['currency'], $userCurrency);
            $prices['appointments'] = self::prepareAppointmentResponse($transition, $inputs);
//            $prices['amount_paid_by_customer'] = $transition['total_amount'];
            // ====================== Show to freelancer
            if ($inputs['login_user_type'] == 'freelancer') {
                $prices['currency'] = $transition['service_provider_currency'];
                $circlFeeAmount = self::prepareCirclFeeAmount($transition);
                $transactionCharges = self::prepareTransactionChargesAmount($transition);
                $prices['circl_charges'] = CommonHelper::getConvertedCurrency($circlFeeAmount, $appointmentPackage['currency'], $userCurrency);
                $prices['transaction_charges'] = CommonHelper::getConvertedCurrency($transactionCharges, $appointmentPackage['currency'], $userCurrency);
                $prices['earned_amount_by_freelancer'] = CommonHelper::getConvertedCurrency(self::preparePackageFreelancerEarning($transition, $prices), $appointmentPackage['currency'], $userCurrency);
                $prices['promo_discount'] = !empty($transition['discount']) ? CommonHelper::getConvertedCurrency($transition['discount'], $transition['purchased_in_currency'], $userCurrency) : 0.0;
            }
        }
        return $prices;
    }

    public static function prepareClassPackageData($transition, $inputs) {
        $appointmentPackage = $transition['appointment_package']['class_booking'];

        $userCurrency = self::getUserCurrency($inputs);
        $prices = [];
        if (!empty($appointmentPackage)) {
            $bookingDateTime = CommonHelper::convertDateTimeToTimezone(date('Y-m-d H:i:s', strtotime($appointmentPackage['schedule']['class_date'] . ' ' . $appointmentPackage['schedule']['from_time'])), 'UTC', $inputs['local_timezone']);
            $prices['booking_date'] = date('Y-m-d', strtotime($bookingDateTime));
            $prices['booking_time'] = date('H:i:s', strtotime($bookingDateTime));
            $prices['currency'] = $userCurrency;
            $prices['booking_name'] = !empty($appointmentPackage['class_object']['name']) ? $appointmentPackage['class_object']['name'] : null;
            $prices['purchase_detail'] = !empty($transition['appointment_package']['package']['package_name']) ? $transition['appointment_package']['package']['package_name'] : null;
            $prices['type'] = !empty($appointmentPackage['class_object']['online_link']) ? 'online' : 'face to face';
            //            $prices['travel_fee'] = self::distanceResponse($transition['freelancer'], $appointmentPackage['appointments']['travelling_distance']);
//            $travelFee = null;
            $prices['travel_fee'] = null;
//            $prices['travel_fee']['distance'] = null;
//            $prices['travel_fee']['distance_cost'] = null;
//            $prices['travel_fee']['total_distance_cost'] = null;
//            $prices['travel_fee']['distance_cost'] = $travelFee['distance_cost'];
//            $prices['travel_fee']['total_distance_cost'] = $travelFee['total_distance_cost'];
//            $prices['service_amount'] = CommonHelper::getConvertedCurrency($transition['service_amount'], $appointmentPackage['currency'], $userCurrency);
            $prices['promo_discount'] = !empty($transition['discount']) ? CommonHelper::getConvertedCurrency($transition['discount'], $appointmentPackage['currency'], $userCurrency) : 0.0;
//            $prices['travel_fee']['total_distance_cost'] = CommonHelper::getConvertedCurrency($prices['travel_fee']['total_distance_cost'], $appointmentPackage['currency'], $userCurrency);
            $prices['amount_paid_by_customer'] = CommonHelper::getConvertedCurrency($transition['total_amount'], $appointmentPackage['currency'], $userCurrency);
            $prices['service_amount'] = $prices['amount_paid_by_customer'] + ($prices['promo_discount']);
            $prices['final_amount'] = ($prices['service_amount']) - ($prices['promo_discount']);

            $prices['class_bookings'] = self::prepareClassBookingResponse($transition, $inputs);

//            $prices['amount_paid_by_customer'] = $transition['total_amount'];
            // ====================== Show to freelancer
            if ($inputs['login_user_type'] == 'freelancer') {
                $circlFeeAmount = self::prepareCirclFeeAmount($transition);
                $transactionCharges = self::prepareTransactionChargesAmount($transition);
                $prices['currency'] = $userCurrency;
                $prices['circl_charges'] = CommonHelper::getConvertedCurrency($circlFeeAmount, $transition['purchased_in_currency'], $userCurrency);
                $prices['transaction_charges'] = CommonHelper::getConvertedCurrency($transactionCharges, $transition['purchased_in_currency'], $userCurrency);
                $prices['earned_amount_by_freelancer'] = ($prices['amount_paid_by_customer'] - $prices['circl_charges'] - $prices['transaction_charges']);
            }
        }
        return $prices;
    }

    public static function prepareFreelancerAppointmentPrices($transition = [], $inputs = []) {
        $appointment = !empty($transition['appointment']) ? $transition['appointment'] : [];
        $freelancerCurrency = \App\Freelancer::where('freelancer_uuid', $inputs['logged_in_uuid'])->first()->default_currency;
        $prices = [];
        if (!empty($appointment)) {
            $bookingDateTime = !empty($appointment['appointment_start_date_time']) ? CommonHelper::convertDateTimeToTimezone(date('Y-m-d H:i:s', $appointment['appointment_start_date_time']), 'UTC', $inputs['local_timezone']) : null;
            $prices['booking_date'] = date('Y-m-d', strtotime($bookingDateTime));
            $prices['booking_time'] = date('H:i:s', strtotime($bookingDateTime));
            $prices['booking_name'] = !empty($appointment['title']) ? $appointment['title'] : null;
            $prices['purchase_detail'] = 'Single appointment';
//            $prices['appointment_status'] = $appointment['status'];
            $prices['appointment_status'] = self::prepareTransactionStatus($transition);
            $prices['currency'] = $transition['service_provider_currency'];
            $prices['type'] = ($appointment['is_online'] == 1) ? 'online' : 'face to face';
//            $prices['travel_fee'] = self::distanceResponse($transition['freelancer'], $appointment['travelling_distance']);
            $travelFee = self::distanceResponse($transition['freelancer'], $appointment['travelling_distance']);
            $prices['travel_fee']['distance'] = $travelFee['distance'];
            $prices['travel_fee']['distance_cost'] = $travelFee['distance_cost'];
//            $prices['travel_fee']['distance_cost'] = CommonHelper::getConvertedCurrency($travelFee['distance_cost'], $appointment['currency'], $freelancerCurrency);
            $prices['travel_fee']['total_distance_cost'] = $travelFee['total_distance_cost'];
//            $prices['travel_fee']['total_distance_cost'] = CommonHelper::getConvertedCurrency($travelFee['total_distance_cost'], $appointment['currency'], $freelancerCurrency);
            $taxAmounts['total_amount'] = $appointment['paid_amount'];
            $taxAmounts['transaction_charges'] = $transition['transaction_charges'];
            $taxAmounts['circl_fee_percentage'] = $transition['circl_fee'];
            $circlFeeAmount = self::prepareCirclFeeAmount($taxAmounts);
            $transactionCharges = self::prepareTransactionChargesAmount($taxAmounts);
            $prices['amount_paid_by_customer'] = CommonHelper::getConvertedCurrency($appointment['paid_amount'], $appointment['currency'], $freelancerCurrency);
            $prices['circl_charges'] = CommonHelper::getConvertedCurrency($circlFeeAmount, $appointment['currency'], $freelancerCurrency);
            $prices['transaction_charges'] = CommonHelper::getConvertedCurrency($transactionCharges, $appointment['currency'], $freelancerCurrency);
            $prices['promo_discount'] = !empty($transition['discount']) ? CommonHelper::getConvertedCurrency($transition['discount'], $appointment['currency'], $freelancerCurrency) : 0.0;
//            $prices['circl_charges'] = $circlFeeAmount;
//            $prices['transaction_charges'] = $transition['transaction_charges'];
//            $prices['promo_discount'] = !empty($transition['discount']) ? $transition['discount'] : 0.0;
//            $prices['earned_amount_by_freelancer'] = ($prices['amount_paid_by_customer'] - $prices['circl_charges'] - $prices['transaction_charges']);
//            $prices['earned_amount_by_freelancer'] = !empty($transition['appointment']['freelancer_earning']) ? $transition['appointment']['freelancer_earning']['earned_amount'] : 0;
            $prices['earned_amount_by_freelancer'] = CommonHelper::getConvertedCurrency($appointment['freelancer_earning']['earned_amount'], $appointment['currency'], $freelancerCurrency);
//            $prices['earned_amount_by_freelancer'] = self::prepareFreelancerEarningWRTStatuses($transition, $prices);
        }
        return $prices;
    }

    public static function prepareFreelancerEarningWRTStatuses($transition, $prices) {
        $amount = 0;
        if (!empty($prices['amount_paid_by_customer'])) {
            $amount = self::prepareAppointmentEarning($transition, $prices);
        }
        return $amount;
    }

    public static function prepareAppointmentEarning($transition, $prices) {
        if ($transition['appointment']['status'] == 'confirmed' || $transition['status'] == 'completed') {
            $amount = $prices['amount_paid_by_customer'] - $prices['circl_charges'] - $prices['transaction_charges'];
        }
        if ($transition['appointment']['status'] == 'rejected') {
            $amount = 0;
        }
        if ($transition['appointment']['status'] == 'cancelled') {
            $earning = \App\FreelancerEarning::getEarning('appointment_id', $transition['appointment_id']);
            if (!empty($earning)) {
                $amount = $earning['earned_amount'];
            }
        }
        return isset($amount) ? $amount : 0;
    }

    public static function preparePackageFreelancerEarning($transition, $prices) {
        $earning = \App\FreelancerEarning::getSumOfCol('purchased_package_id', $transition['purchased_package_id'], 'earned_amount');
        if (!empty($earning)) {
            $amount = !empty($earning) ? $earning : 0;
        }
        return isset($amount) ? $amount : 0;
    }

    public static function getUserCurrency($inputs) {

        if (($inputs['login_user_type']) == 'customer') {
            $userCurrency = \App\Customer::where('customer_uuid', '=', $inputs['logged_in_uuid'])->first()->default_currency;
        } else {
            $userCurrency = \App\Freelancer::where('freelancer_uuid', '=', $inputs['logged_in_uuid'])->first();
            $userCurrency = $userCurrency['default_currency'] ?? '';
        }
        return $userCurrency;
    }

    public static function prepareFreelancerClassBookingPrices($transition = [], $inputs = []) {
        $booking = !empty($transition['classbooking']) ? $transition['classbooking'] : [];
        $freelancerCurrency = \App\Freelancer::where('freelancer_uuid', $inputs['logged_in_uuid'])->first()->default_currency;
        $prices = [];
        if (!empty($booking)) {
            $bookingDateTime = !empty($transition['classbooking']['schedule']['start_date_time']) ? CommonHelper::convertDateTimeToTimezone(date('Y-m-d H:i:s', $transition['classbooking']['schedule']['start_date_time']), 'UTC', $inputs['local_timezone']) : null;
            $prices['booking_date'] = date('Y-m-d', strtotime($bookingDateTime));
            $prices['booking_time'] = date('H:i:s', strtotime($bookingDateTime));
            $prices['booking_name'] = !empty($booking['class_object']['name']) ? $booking['class_object']['name'] : null;
            $prices['status'] = self::prepareFreelancerClassBookingStatuses($booking);
//            $prices['status'] = $booking['status'];
            $prices['purchase_detail'] = 'Single class';
            $prices['booking_status'] = self::prepareFreelancerClassBookingStatuses($booking);
//            $prices['booking_status'] = $booking['status'];
            $prices['currency'] = $transition['service_provider_currency'];
            $prices['type'] = !empty($booking['class_object']['online_link']) ? 'online' : 'face to face';
            $prices['travel_fee'] = null;
//            $prices['travel_fee'] = CommonHelper::getConvertedCurrency($booking['travelling_charges'], $booking['currency'], $freelancerCurrency);
//            $prices['travel_fee'] = $booking['travelling_charges'];
            $tax['circl_fee_percentage'] = $transition['circl_fee_percentage'];
            $tax['transaction_charges'] = $transition['transaction_charges'];
            $tax['total_amount'] = $booking['paid_amount'];
            $circlFeeAmount = self::prepareCirclFeeAmount($tax);
            $transactionCharges = self::prepareTransactionChargesAmount($tax);
//            $prices['amount_paid_by_customer'] = $transition['total_amount'];
            $prices['amount_paid_by_customer'] = CommonHelper::getConvertedCurrency($transition['total_amount'], $booking['currency'], $freelancerCurrency);
            $paid_amount = CommonHelper::getConvertedCurrency($booking['paid_amount'], $booking['currency'], $freelancerCurrency);
            $prices['circl_charges'] = CommonHelper::getConvertedCurrency($circlFeeAmount, $booking['currency'], $freelancerCurrency);
            $prices['transaction_charges'] = CommonHelper::getConvertedCurrency($transactionCharges, $booking['currency'], $freelancerCurrency);
            $prices['promo_discount'] = !empty($transition['discount']) ? CommonHelper::getConvertedCurrency($transition['discount'], $booking['currency'], $freelancerCurrency) : 0.0;
//            $prices['circl_charges'] = $circlFeeAmount;
//            $prices['transaction_charges'] = $transition['transaction_charges'];
//            $prices['promo_discount'] = !empty($transition['discount']) ? $transition['discount'] : 0.0;
            $prices['earned_amount_by_freelancer'] = ($paid_amount - $prices['circl_charges'] - $prices['transaction_charges']);
        }
        return $prices;
    }

    public static function prepareFreelancerPackagePrices($transition = [], $inputs = []) {
        $appointmentPackage = !empty($transition['appointment_package']) ? $transition['appointment_package'] : [];
        $freelancerCurrency = \App\Freelancer::where('freelancer_uuid', $inputs['logged_in_uuid'])->first()->default_currency;
        $prices = [];
        if (!empty($appointmentPackage)) {
            $bookingDateTime = !empty($appointmentPackage['appointments']['appointment_start_date_time']) ? CommonHelper::convertDateTimeToTimezone(date('Y-m-d H:i:s', $appointmentPackage['appointments']['appointment_start_date_time']), 'UTC', $inputs['local_timezone']) : null;
            $prices['booking_date'] = date('Y-m-d', strtotime($bookingDateTime));
            $prices['booking_name'] = !empty($appointmentPackage['appointments']['title']) ? $appointmentPackage['appointments']['title'] : null;
            $prices['purchase_detail'] = !empty($appointmentPackage['package']['package_name']) ? $appointmentPackage['package']['package_name'] : null;
            $prices['type'] = ($appointmentPackage['appointments']['is_online'] == 1) ? 'online' : 'face to face';
            $prices['currency'] = $transition['service_provider_currency'];
//            $prices['travel_fee'] = self::distanceResponse($transition['freelancer'], $appointmentPackage['appointments']['travelling_distance']);
            $travelFee = self::distanceResponse($transition['freelancer'], $appointmentPackage['appointments']['travelling_distance']);
            $prices['travel_fee']['distance'] = $travelFee['distance'];
            $prices['travel_fee']['distance_cost'] = CommonHelper::getConvertedCurrency($travelFee['distance_cost'], $appointmentPackage['appointments']['currency'], $freelancerCurrency);
            $prices['travel_fee']['total_distance_cost'] = CommonHelper::getConvertedCurrency($travelFee['total_distance_cost'], $appointmentPackage['appointments']['currency'], $freelancerCurrency);
            $circlFeeAmount = self::prepareCirclFeeAmount($transition);
            $prices['amount_paid_by_customer'] = CommonHelper::getConvertedCurrency($transition['total_amount'], $appointmentPackage['appointments']['currency'], $freelancerCurrency);
            $prices['circl_charges'] = CommonHelper::getConvertedCurrency($circlFeeAmount, $appointmentPackage['appointments']['currency'], $freelancerCurrency);
//            $prices['amount_paid_by_customer'] = $transition['total_amount'];
//            $prices['circl_charges'] = $circlFeeAmount;
//            $prices['transaction_charges'] = $transition['transaction_charges'];
            $transactionCharges = self::prepareTransactionChargesAmount($transition);
            $prices['transaction_charges'] = CommonHelper::getConvertedCurrency($transactionCharges, $appointmentPackage['appointments']['currency'], $freelancerCurrency);
            $prices['promo_discount'] = !empty($transition['discount']) ? $transition['discount'] : 0.0;
//            $prices['promo_discount'] = !empty($transition['discount']) ? $transition['discount'] : 0.0;
            $prices['earned_amount_by_freelancer'] = ($prices['amount_paid_by_customer'] - $prices['circl_charges'] - $prices['transaction_charges']);
        }
        return $prices;
    }

    public static function setTransactionDetailOldResponse($data, $inputs) {



        $timezone = $inputs['local_timezone'];
        $response = [];
        if (!empty($data)) {
            if (isset($inputs['payment_due_uuid']) && !empty($inputs['payment_due_uuid']) && isset($data['payment_due']) && count($data['payment_due']) > 0) {
                $due_key = array_search($inputs['payment_due_uuid'], array_column($data['payment_due'], 'payment_due_uuid'));
                $data['single_pay_due'] = $data['payment_due'][$due_key];
            }
            $response['uuid'] = $data['freelancer_transaction_uuid'];
            $response['freelancer_uuid'] = $data['freelancer_uuid'];
            $response['freelancer_name'] = !empty($data['freelancer']) ? $data['freelancer']['first_name'] . ' ' . $data['freelancer']['last_name'] : null;
            $response['freelancer_profile_image'] = !empty($data['freelancer']['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_image'] . $data['freelancer']['profile_image'] : null;
            $response['purchase_type'] = self::setTransactionType($data);
            $response['purchase_detail'] = self::getPurchaseDetails($data);
            $response['booking_name'] = self::setTransactionTitle($data);
            $response['transaction_id'] = !empty($data['transaction_id']) ? $data['transaction_id'] : null;
            $response['travel_fee'] = null;
            $response['travel_fee'] = self::calculateTravelFee($data);
            $response['purchase_date'] = date('d M Y', strtotime($data['transaction_date']));
            $time = date('H:i:s', strtotime($data['transaction_date']));
            $response['time'] = CommonHelper::convertTimeToTimezone($time, 'UTC', $timezone);
            $response['customer'] = !empty($data['customer']) ? $data['customer']['first_name'] . ' ' . $data['customer']['last_name'] : null;
            $response['customer_uuid'] = !empty($data['customer']) ? $data['customer']['customer_uuid'] : null;
            $response['profile_image'] = !empty($data['customer']['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_image'] . $data['customer']['profile_image'] : null;
            $response['payment_by'] = !empty($data['payment_brand']) ? $data['payment_brand'] : 'VISA';
            $response['transaction_status'] = self::prepareTransactionStatus($data);
            $response['circl_charges'] = !empty($data['circl_charges']) ? $data['circl_charges'] : null;
            $response['hyperpay_fee'] = !empty($data['hyperpay_fee']) ? $data['hyperpay_fee'] : null;
            $response['location'] = self::setTransactionLocation($data);
            $response['online_link'] = self::setTransactionSessionLink($data);
            $response['type'] = self::checkBookingIsOnline($data) != null ? 'online' : 'face to face';
            $response['booking_date'] = self::getTransactionBookingDate($data);
            $response['booking_time'] = self::getTransactionBookingTime($data, $timezone);

            $exchange_rate = 1;
            $exchange_type = '';
            if ($inputs['login_user_type'] == "freelancer") {
                $response['currency'] = $data['freelancer']['default_currency'] ?? '';
                $response['service_fee'] = !empty($data['total_amount']) ? $data['total_amount'] : 0;
                $response['amount'] = $response['service_fee'] - ($response['circl_charges'] + $response['hyperpay_fee']);
                $response['amount'] = round($response['amount'], 2);
                $exchange_rate = $data['exchange_rate'];
                $promo_discount = self::getPromoDiscount($data, $response);
                $response['promo_discount'] = !empty($promo_discount) ? $promo_discount : 0;
                //                $response['discount'] = self::getDiscountPrice($data, $response);
                $response['promo_code'] = self::getPromoCode($data);

                if (isset($data['freelancer']['default_currency']) && strtolower($data['freelancer']['default_currency']) != strtolower($data['to_currency'])) {
                    $exchange_type = 'd';
                }
            }

            if ($inputs['login_user_type'] == "customer") {
                $response['currency'] = !empty($data['to_currency']) ? $data['to_currency'] : null;
                $response['service_fee'] = !empty($data['actual_amount']) ? $data['actual_amount'] : 0;
                $response['amount'] = $data['total_amount'] ?? 0;
                $promo_discount = self::getPromoDiscount($data, $response);
                $response['promo_discount'] = !empty($promo_discount) ? $promo_discount : 0;
            }
            $response['discount'] = self::getDiscountPrice($data, $response);
            $response['promo_code'] = self::getPromoCode($data);

            if (!empty($exchange_type)) {
                if ($exchange_type == 'd') {
                    $response['service_fee'] = round($response['service_fee'] / $exchange_rate, 2);
                    $response['circl_charges'] = round($response['circl_charges'] / $exchange_rate, 2);
                    $response['hyperpay_fee'] = round($response['hyperpay_fee'] / $exchange_rate, 2);
                    $response['amount'] = round($response['amount'] / $exchange_rate, 2);
                    $response['discount'] = round($response['discount'] / $exchange_rate, 2);
                }
                if ($exchange_type == 'm') {
                    $response['service_fee'] = round($response['service_fee'] * $exchange_rate, 2);
                    $response['circl_charges'] = round($response['circl_charges'] * $exchange_rate, 2);
                    $response['hyperpay_fee'] = round($response['hyperpay_fee'] * $exchange_rate, 2);
                    $response['amount'] = round($response['amount'] * $exchange_rate, 2);
                    $response['discount'] = round($response['discount'] * $exchange_rate, 2);
                }
            }

            if ($data['transaction_type'] == 'subscription') {
                $response['subscription_type'] = $data['subscription']['subscription_setting']['type'];
            }
            // get amount from payment_due table if transaction type is subscription
            if ($data['transaction_type'] == 'subscription' && isset($inputs['payment_due_uuid']) && !empty($inputs['payment_due_uuid']) && isset($data['payment_due']) && count($data['payment_due']) > 0) {
                $response['due_amount'] = self::getPaymentDueAmountForSingleTransaction($data, $inputs);
                $response['due_date'] = isset($data['single_pay_due']['due_date']) ? date('d M Y', strtotime($data['single_pay_due']['due_date'])) : null;
            }

            $session_number_info = self::getSessionNumberDetails($data);
            $response['total_session'] = $session_number_info['total_session'];
            $response['session_number'] = $session_number_info['session_number'];

            if (!empty($data['appointment'])) {
                $response['session_status'] = $data['appointment']['status'];
            } elseif (!empty($data['class_book'])) {
                $response['session_status'] = $data['class_book']['status'];
            }

            if ($data['status'] == 'cancelled') {
                $response['is_refunded'] = isset($data['refund_transaction']['id']) ? 1 : 0;
                $response['refund_transaction_uuid'] = $data['refund_transaction']['refund_transaction_uuid'] ?? '';
                $response['refund_type'] = $data['refund_transaction']['refund_type'] ?? '';
                $response['refund_amount'] = $data['refund_transaction']['amount'] ?? 0;
                $response['refund_currency'] = $data['refund_transaction']['currency'] ?? '';

                $response['amount'] = $response['amount'] - $response['refund_amount'];
            }
        }
        return $response;
    }

    public static function getPaymentDueAmountForSingleTransaction($data, $inputs) {
        $amount = 0;
        $freelancer_currency = isset($data['freelancer']['default_currency']) ? $data['freelancer']['default_currency'] : '';
        if (isset($data['single_pay_due'])) {
            if (strtolower($freelancer_currency) == 'pound') {
                $amount = $data['single_pay_due']['pound_amount'];
            } else {
                $amount = $data['single_pay_due']['sar_amount'];
            }
        }

        return round($amount, 2);
    }

    public static function getPromoDiscount($data) {

        $code = 0;
        if ($data['transaction_type'] == 'appointment_bookoing') {
            $code = $data['appointment']['promo_code']['coupon_amount'] ?? '';
        }

        if ($data['transaction_type'] == 'class_booking') {
            $code = $data['class_book']['promo_code']['coupon_amount'] ?? '';
        }

        return $code;
    }

    public static function getPromoCode($data) {

        $code = '';
        if ($data['transaction_type'] == 'appointment_bookoing') {
            $code = $data['appointment']['promo_code']['coupon_code'] ?? '';
        }

        if ($data['transaction_type'] == 'class_booking') {
            $code = $data['class_book']['promo_code']['coupon_code'] ?? '';
        }

        return $code;
    }

    public static function getDiscountPrice($data, $response) {

        $discounted_price = 0;
        if ($data['transaction_type'] == 'appointment_bookoing') {
            $discounted_price = $data['appointment']['discounted_price'] ?? 0;
        }

        if ($data['transaction_type'] == 'class_booking') {
            $discounted_price = $data['class_book']['discounted_price'] ?? 0;
        }

        $discount = ($discounted_price > 0) ? $response['service_fee'] - $discounted_price : 0;
        $discount = round(abs($discount), 2);

        return $discount;
    }

    public static function getSessionNumberDetails($data) {

        $resp = [
            'session_number' => 0,
            'total_session' => 0
        ];

        if ($data['transaction_type'] == 'appointment_bookoing') {
            if (!empty($data['appointment']['session_number']) && !empty($data['appointment']['total_session'])) {
                $resp['session_number'] = $data['appointment']['session_number'];
                $resp['total_session'] = $data['appointment']['total_session'];
            }
        }

        if ($data['transaction_type'] == 'class_booking') {
            if (!empty($data['class_book']['session_number']) && !empty($data['class_book']['total_session'])) {
                $resp['session_number'] = $data['class_book']['session_number'];
                $resp['total_session'] = $data['class_book']['total_session'];
            }
        }

        if ($data['transaction_type'] == 'subscription') {
            if (isset($data['single_pay_due'])) {
                $resp['session_number'] = $data['single_pay_due']['due_no'];
                $resp['total_session'] = $data['single_pay_due']['total_dues'];
            }
        }

        return $resp;
    }

    public static function getPurchaseDetails($data) {
        $name = '';
        if ($data['transaction_type'] == 'appointment_bookoing') {
            if (isset($data['appointment']['package']['package_name'])) {
                $name = $data['appointment']['package']['package_name'];
            } else {
                $name = "Single Appointment";
            }
        }

        if ($data['transaction_type'] == 'class_booking') {
            if (isset($data['class_book']['package']['package_name'])) {
                $name = $data['class_book']['package']['package_name'];
            } else {
                $name = "Single Class";
            }
        }

        if ($data['transaction_type'] == 'subscription') {
            $name = 'Subscription';
        }

        return $name;
    }

    public static function getTransactionBookingDate($data = []) {

        if ($data['transaction_type'] == 'appointment_bookoing') {
            return date('d M Y', strtotime($data['appointment']['appointment_date']));
        } elseif ($data['transaction_type'] == 'class_booking') {
            return date('d M Y', strtotime($data['class_book']['schedule']['class_date']));
        } elseif ($data['transaction_type'] == 'subscription') {
            return date('d M Y', strtotime($data['subscription']['subscription_date']));
        }
    }

    public static function getTransactionBookingTime($data = [], $timezone) {
        if ($data['transaction_type'] == 'appointment_bookoing') {
            return CommonHelper::convertTimeToTimezone($data['appointment']['from_time'], 'UTC', $timezone);
        } elseif ($data['transaction_type'] == 'class_booking') {
            return CommonHelper::convertTimeToTimezone($data['class_book']['schedule']['from_time'], 'UTC', $timezone);
        } elseif ($data['transaction_type'] == 'subscription') {
            return '';
        }
    }

    public static function setTransactionType($data = []) {
        if ($data['transaction_type'] == 'appointment_bookoing') {
            if (!empty($data['appointment']['package_uuid'])) {
                return 'Session Package';
            } else {
                return 'Session';
            }
        } elseif ($data['transaction_type'] == 'class_booking') {
            if (!empty($data['class_book']['package_uuid'])) {
                return 'Class Package';
            } else {
                return 'Class';
            }
        } elseif ($data['transaction_type'] == 'subscription') {
            return 'Subscription';
        } elseif ($data['transaction_type'] == 'refund') {
            return 'Refund';
        }
        return $data['transaction_type'];
    }

    public static function checkBookingIsOnline($data = []) {
        $link = null;
        if (isset($data['appointment']['is_online']) && !empty($data['appointment']['is_online'])) {
            $link = $data['appointment']['is_online'];
        } elseif (!empty($data['class_book']) && !empty($data['class_book']['class_object']['online_link'])) {
            $link = $data['class_book']['class_object']['online_link'];
        }
        return $link;
    }

    public static function setTransactionSessionLink($data = []) {
        $link = null;
        if (!empty($data['appointment']) && !empty($data['appointment']['online_link'])) {
            $link = $data['appointment']['online_link'];
        } elseif (!empty($data['class_book']) && !empty($data['class_book']['class_object']['online_link'])) {
            $link = $data['class_book']['class_object']['online_link'];
        }
        return $link;
    }

    public static function setTransactionLocation($data = []) {
        $location = null;
        if (!empty($data['appointment']) && !empty($data['appointment']['address'])) {
            $location = $data['appointment']['address'];
        } elseif (!empty($data['class_book']) && !empty($data['class_book']['class_object']['address'])) {
            $location = $data['class_book']['class_object']['address'];
        }
        return $location;
    }

    public static function calculateTravelFee($data = []) {
        $travel_cost = null;
        if (!empty($data['appointment']) && $data['appointment']['location_type'] != 'freelancer' && $data['freelancer']['can_travel'] == 1) {
            if (!empty($data['appointment']['travelling_distance'])) {
                $travel_cost = self::distanceResponse($data['freelancer'], $data['appointment']['travelling_distance']);
            }
        }
        return $travel_cost;
    }

    //    public static function setClassesResponse($classes, $timezone) {
    //        $response = [];
    //        if (!empty($classes)) {
    //            foreach ($classes as $key => $class) {
    //                $response[$key] = self::setClassResponse($class, $timezone);
    //            }
    //        }
    //        return $response;
    //    }
    //
    //    public static function setClassResponse($class, $timezone) {
    //        $response = [];
    //        if (!empty($class)) {
    //            $response['uuid'] = $class['transaction_id'];
    //            $response['freelancer_uuid'] = $class['freelancer_uuid'] ?? null;
    //            $response['type'] = 'class';
    //            $response['name'] = $class['class_book']['class_object']['name'] ?? '';
    //            $response['id'] = !empty($class['transaction_id']) ? $class['transaction_id'] : null;
    //            $response['transaction_id'] = !empty($class['transaction_id']) ? $class['transaction_id'] : null;
    //            $response['travel_fee'] = !empty($class['appointment_freelancer']['can_travel']) ? self::distanceResponse($class['appointment_freelancer']) : null;
    //            $response['date'] = CommonHelper::setDbDateFormat($class['class_book']['schedule']['class_date'], 'd M Y');
    //            $response['time'] = CommonHelper::convertTimeToTimezone($class['class_book']['schedule']['from_time'], 'UTC', $timezone);
    //            $response['amount'] = $class['class_book']['class_object']['price'];
    //            $response['customer'] = $class['appointment_customer']['first_name']. ' ' . $class['appointment_customer']['last_name'];
    //            $response['customer_uuid'] = $class['appointment_customer']['customer_uuid'];
    //            $response['profile_image'] = !empty($class['appointment_customer']['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_image'] . $class['appointment_customer']['profile_image'] : null;
    //            $response['location'] = $class['appointment_customer']['address'] ?? '';
    //            $response['payment_by'] = null;
    //            $response['status'] = $class['class_book']['status'] ?? '';
    //        }
    //        return $response;
    //    }

    public static function distanceResponse($data, $total_distance = 0) {

        $distance['distance'] = $total_distance;
        $distance['distance_cost'] = $data['travelling_cost_per_km'];
        $distance['total_distance_cost'] = $total_distance * $data['travelling_cost_per_km'];
        return $distance;
    }

    public static function preparePaymentRequestResposne($dataArray = [], $timezone = 'UTC') {

        $response = [];
        if (!empty($dataArray)) {
            foreach ($dataArray as $key => $value) {
                $response[$key]['freelancer_withdrawal_uuid'] = $value['freelancer_withdrawal_uuid'];
                $response[$key]['invoice_id'] = $value['invoice_id'];
                $response[$key]['amount'] = round($value['amount'], 2);
                $response[$key]['currency'] = $value['currency'];
                $response[$key]['last_withdraw_date'] = $value['last_withdraw_date'];
                $response[$key]['receipt_date'] = $value['receipt_date'];

                if ($value['schedule_status'] == 'complete') {
                    $response[$key]['status'] = "Complete";
                } elseif ($value['schedule_status'] == 'in_progress') {
                    $response[$key]['status'] = "In Progress";
                }

                $time = date('H:i:s', strtotime($value['created_at']));
                $response[$key]['time'] = CommonHelper::convertDateTimeToTimezone($time, 'UTC', $timezone);
            }
        }
        return $response;
    }

    public static function prepareAppointmentResponse($transaction = [], $inputs = []) {
        $response = [];
        if (!empty($transaction['appointment_package']['all_appointments'])) {
            foreach ($transaction['appointment_package']['all_appointments'] as $key => $appointment) {
                $transaction['appointment'] = $appointment;
                if ($inputs['login_user_type'] == 'customer') {
                    $response[$key] = self::prepareCustomerAppointmentPrices($transaction, $inputs);
                } else {
                    $response[$key] = self::prepareFreelancerAppointmentPrices($transaction, $inputs);
                }
            }
        }
        return $response;
    }

    public static function prepareClassBookingResponse($transaction = [], $inputs = []) {
        $response = [];

        if (!empty($transaction['appointment_package']['all_class_bookings'])) {
            foreach ($transaction['appointment_package']['all_class_bookings'] as $key => $booking) {
                $transaction['classbooking'] = $booking;
                if ($inputs['login_user_type'] == 'customer') {
                    $response[$key] = self::prepareCustomerClassBookingPrices($transaction, $inputs);
                } else {
                    $response[$key] = self::prepareFreelancerClassBookingPrices($transaction, $inputs);
                }
            }
        }
        return $response;
    }

    public static function prepareCustomerRefundAmount($transaction) {
        $amount = 0;
        $circlFeeAmount = self::prepareCirclFeeAmount($transaction);
        $transactionCharges = self::prepareTransactionChargesAmount($transaction);
        $deductions = $circlFeeAmount + $transactionCharges;

        if ($transaction['status'] == 'refunded'
            || $transaction['status'] == 'voided'
            || $transaction['status'] == 'rejected'
            || $transaction['status'] == 'cancelled' ) {
            // if it is refunded then get earning
            $freelancer_earning = \App\FreelancerEarning::where('purchase_id', $transaction['id'])->where('is_archive', 0)->first();
            if (empty($freelancer_earning)) {
                $amount = $transaction['total_amount'];
            } else {
                $earning = $freelancer_earning->toArray();
                if ($earning['earned_amount'] < 1) {
                    $amount = $transaction['total_amount'];
                }
                if ($earning > 1) {
                    $amount = ($transaction['total_amount'] - ($earning['earned_amount'] + $deductions));
                }
            }
        }
        return $amount;
    }

}

<?php

/**
 * Created by PhpStorm.
 * User: ILSA Interactive
 * Date: 12/22/2021
 * Time: 9:19 AM
 */

namespace App\Helpers\AfterPayment;

use App\Appointment;
use App\FreelancerEarning;
use App\Helpers\CommonHelper;
use App\payment\checkout\Checkout;
use App\PurchasesTransition;
use App\Purchases;
use App\Traits\EarningAmount;
use App\Helpers\UuidHelper;
use DB;
use Illuminate\Support\Facades\Log;

class StatusConditions
{

    use EarningAmount;

    public static function singleAppointmentStatus($inputs)
    {
        Log::channel('daily_change_status')->debug('singleAppointmentStatus');
        Log::channel('daily_change_status')->debug('Appointment Inputs');
        Log::channel('daily_change_status')->debug($inputs);
        if ($inputs['status'] == 'confirmed') {
            // check if booking has been rescheduled
            Log::info('confirming status');
            $appointmentId = Appointment::where('appointment_uuid', $inputs['appointment_uuid'])->first()->id;
            $reschedules = \App\RescheduledAppointment::getRescheduleData('appointment_id', $appointmentId);
            $checkConfirm = self::getRescheduleWithConfirmStatus($reschedules);
            if ($checkConfirm == false) {
                $response = self::singleAppointmentConfirmedStatus($inputs);
            }
            if ($checkConfirm == true) {
                $response = true;
            }
            return $response;
        }
        // Rejected before appointment confirmation
        // incase if appointment is paid through MADA card then refund customer otherwise void the payment
        if (($inputs['status'] == 'rejected') && (($inputs['login_user_type'] == 'freelancer') || ($inputs['login_user_type'] == 'customer'))) {
            $response = self::singleAppointmentRejectedByFreelancer($inputs);
            return $response;
        }
        // cancelled after confirmation by freelancer
        if ($inputs['status'] == 'cancelled' && $inputs['login_user_type'] == 'freelancer') {
            $response = self::singleAppointmentCancelledByFreelancer($inputs);
            return $response;
        }
        // cancelled after confirmation by customer
        if ($inputs['status'] == 'cancelled' && $inputs['login_user_type'] == 'customer') {
            Log::channel('daily_change_status')->debug(' cancelled after confirmation by customer');
            Log::channel('daily_change_status')->debug('Appointment Inputs');
            Log::channel('daily_change_status')->debug($inputs);
            $response = self::singleAppointmentCancelledByCustomer($inputs);
            return $response;
        }
    }

    public static function packageAppointmentStatus($inputs)
    {
        if ($inputs['status'] == 'confirmed') {
            // check if booking has been rescheduled
            $appointmentId = Appointment::where('purchased_package_uuid', $inputs['purchased_package_uuid'])->pluck('id')->toArray();
            $reschedules = \App\RescheduledAppointment::getPackageRescheduleData('appointment_id', $appointmentId);
            $checkConfirm = self::getRescheduleWithConfirmStatus($reschedules);

            if ($checkConfirm == false) {
                $response = self::packageAppointmentConfirmedStatus($inputs);
            }
            if ($checkConfirm == true) {
                $response = true;
            }
            return $response;
        }
        // Rejected before appointment confirmation by freelancer whole package will be rejected
        // incase if appointment is paid through MADA card then refund customer otherwise void the payment 
        if (($inputs['status'] == 'rejected') && (($inputs['login_user_type'] == 'freelancer'))) {
            //        if (($inputs['status'] == 'rejected') && (($inputs['login_user_type'] == 'freelancer') || ($inputs['login_user_type'] == 'customer'))) {
            //            $response = self::appointmentPackageRejectedByFreelancer($inputs);
            $response = self::singleAppointmentRejectedByFreelancer($inputs);
            return $response;
        }
        // cancelled after confirmation by freelancer only specific appointment will be cancelled
        if ($inputs['status'] == 'cancelled' && $inputs['login_user_type'] == 'freelancer') {
            $response = self::appointmentPackageRejectedByFreelancer($inputs);
            return $response;
        }
        // cancelled after confirmation by customer
        if ($inputs['status'] == 'cancelled' && $inputs['login_user_type'] == 'customer') {
            $response = self::singleAppointmentCancelledByCustomer($inputs);
            return $response;
        }
    }

    public static function getRescheduleWithConfirmStatus($reschedules = [])
    {
        $check = false;
        if (!empty($reschedules)) {
            foreach ($reschedules as $key => $reschedule) {
                if ($reschedule['previous_status'] == 'confirmed') {
                    $check = true;
                    break;
                } else {
                    $check = false;
                }
            }
        }
        return $check;
    }

    public static function singleAppointmentRejectedByFreelancer($inputs)
    {
        Log::channel('daily_change_status')->debug('singleAppointmentRejectedByFreelancer');
        Log::channel('daily_change_status')->debug('inputs');
        Log::channel('daily_change_status')->debug($inputs);
        $appointment = Appointment::getAppointmentWithPurchase($inputs['appointment_uuid'])[0];
        if ((!empty($appointment['purchased_package_uuid']))) {
            $purchasePackageId = \App\PurchasesPackage::where('purchased_packages_uuid', $appointment['purchased_package_uuid'])->first()->id;
            $purchase = \App\Purchases::where('purchased_package_id', $purchasePackageId)->with('purchasesTransition')->first();
            $purchase = !empty($purchase) ? $purchase->toArray() : null;
            $appointment['purchase'] = $purchase;
            $appointment['paid_amount'] = $appointment['package_paid_amount'];
        }
        $response = Checkout::appointmentRefund($inputs['appointment_uuid'], $appointment, $inputs['status']);
        Log::channel('daily_change_status')->debug('appointmentRefund Response');
        Log::channel('daily_change_status')->debug($response);
        if ((isset($response['status'])) && (($response['status'] == 'Refunded')) || ($response['status'] == 'Voided')) {
            $params = self::trasaitionParams($appointment, $appointment['paid_amount'], $response['response']);
            Purchases::where('id', $params['purchase_id'])->update(['status' => strtolower($response['status'])]);
            PurchasesTransition::createPurchase($params);
        }
        return $response;
    }

    public static function appointmentPackageRejectedByFreelancer($inputs)
    {
        $appointment = Appointment::getSingleAppointment('purchased_package_uuid', $inputs['purchased_package_uuid']);
        $purchasePackageId = \App\PurchasesPackage::where('purchased_packages_uuid', $inputs['purchased_package_uuid'])->first()->id;
        $purchase = \App\Purchases::where('purchased_package_id', $purchasePackageId)->with('purchasesTransition')->first();
        $purchase = !empty($purchase) ? $purchase->toArray() : null;
        $appointment['purchase'] = $purchase;
        if (!empty($appointment['purchased_package_uuid'])) {
            $allPackageAppointments = Appointment::where('purchased_package_uuid', $inputs['purchased_package_uuid'])->get();
            $appointmentPackageStatus = self::checkAllPackageAppointmentStatuses($allPackageAppointments);
            $getEarning = FreelancerEarning::where('purchased_package_id', $purchasePackageId)->where('is_archive', 0)->orderBy('id', 'desc')->first();
            if (!empty($getEarning)) {
                $previousEarning = $getEarning->toArray();
                $appointmentEarning = $appointment['paid_amount'] - (($appointment['purchase']['circl_fee'] / 100) * $appointment['paid_amount']) - (($appointment['purchase']['transaction_charges'] / 100) * $appointment['paid_amount']);
                $appointment['freelancer_earning'] = $previousEarning - $appointmentEarning;
            }
        }
        if ($inputs['status'] == 'rejected') {
            $appointment['paid_amount'] = $appointment['package_paid_amount'];
        }
        $response = Checkout::appointmentRefund($inputs['appointment_uuid'], $appointment, $inputs['status']);
        $status = $purchase['status'];
        if ((isset($response['status'])) && (($response['status'] == 'Refunded')) || ($response['status'] == 'Voided')) {
            $params = self::trasaitionParams($appointment, $appointment['paid_amount'], $response['response']);
            if (isset($appointmentPackageStatus) && !empty($appointmentPackageStatus) && $appointmentPackageStatus == 'cancelled') {
                $status = $response['status'];
            }
            Purchases::where('id', $params['purchase_id'])->update(['status' => strtolower($status)]);
            PurchasesTransition::createPurchase($params);
            FreelancerEarning::where('purchased_package_id', $purchasePackageId)->update(['is_archive' => 1]);
            if (isset($appointment['freelancer_earning']) && !empty($appointment['freelancer_earning'])) {
                $records = self::prepareFreelancerEarningRecord($appointment);
                $createEarning = FreelancerEarning::createRecords($records);
            }
        }
        return $response;
    }

    public static function checkAllPackageAppointmentStatuses($appointments = [])
    {
        $status = 'cancelled';
        $count = 0;
        if (!empty($appointments)) {
            $appointments = $appointments->toArray();
            foreach ($appointments as $key => $appointment) {
                if ($appointment['status'] == 'confirmed') {
                    $count++;
                }
                //                if ($appointment['status'] != 'cancelled' || $appointment['status'] != 'rejected') {
                //                    $status = 'confirmed';
                //                    break;
                //                }
            }
        }
        if ($count <= 1) {
            $status = 'cancelled';
        } else {
            $status = 'confirmed';
        }
        return $status;
    }

    public static function singleAppointmentCancelledByFreelancer($inputs)
    {
        // if freelancer cancelled the appointment after confirmation then fully refund customer
        $paidAmount = 0;
        $status = 'Refunded';
        $appointment = Appointment::getAppointmentWithPurchase($inputs['appointment_uuid'])[0];
        if ((!empty($appointment['purchased_package_uuid']))) {

            $purchasePackageId = \App\PurchasesPackage::where('purchased_packages_uuid', $appointment['purchased_package_uuid'])->first()->id;
            $purchase = \App\Purchases::where('purchased_package_id', $purchasePackageId)->with('purchasesTransition')->first();
            $purchase = !empty($purchase) ? $purchase->toArray() : null;
            $appointment['purchase'] = $purchase;
            $allPackageAppointments = Appointment::where('purchased_package_uuid', $appointment['purchased_package_uuid'])->get();
            $appointmentPackageStatus = self::checkAllPackageAppointmentStatuses($allPackageAppointments);
            $getEarning = FreelancerEarning::where('appointment_id', $appointment['id'])->where('is_archive', 0)->orderBy('id', 'desc')->first();
            //            if (!empty($getEarning)) {
            //                $previousEarning = $getEarning->toArray();
            //                $appointmentEarning = $appointment['paid_amount'] - (($appointment['purchase']['circl_fee'] / 100) * $appointment['paid_amount']) - (($appointment['purchase']['transaction_charges'] / 100) * $appointment['paid_amount']);
            //                $appointment['freelancer_earning'] = $previousEarning['earned_amount'] - $appointmentEarning;
            //            }
            $status = $appointment['purchase']['status'];
        }


        // get payment detail check its status
        $response = Checkout::appointmentFullRefund($appointment, $inputs['status']);

        if ((isset($response['status'])) && $response['status'] == 'Refunded') {
            $params = self::trasaitionParams($appointment, $appointment['paid_amount'], $response['response']);
            if (isset($appointmentPackageStatus) && !empty($appointmentPackageStatus) && $appointmentPackageStatus == 'cancelled') {
                $status = $response['status'];
            }
            \App\Purchases::where('id', $params['purchase_id'])->update(['status' => strtolower($status)]);
            PurchasesTransition::createPurchase($params);
            FreelancerEarning::where('appointment_id', $appointment['id'])->update(['is_archive' => 1]);
            if (isset($appointment['freelancer_earning']) && !empty($appointment['freelancer_earning'])) {
                $records = self::prepareFreelancerEarningRecord($appointment);
                $createEarning = FreelancerEarning::createRecords($records);
            }
        }
        return $response;

        //        $diff = self::differenceTwoDates(CommonHelper::getFullDateAndTime($appointment['appointment_end_date_time'], $appointment['saved_timezone'], $appointment['local_timezone']));
        //        if ($appointment['status'] == 'pending' && $diff->y > 0 || $diff->m > 0 || $diff->d > 0 || $diff->h >= 24) {
        //            $response = Checkout::appointmentRefund($inputs['appointment_uuid'], $appointment, $inputs['status']);
        //        } else {
        //            $response = Checkout::appointmentRefund($inputs['appointment_uuid'], $appointment, $inputs['status']);
        //        }
        //        if ($response->status == 'Partially Refunded' || $response->status == 'Refunded') {
        //            PurchasesTransition::createPurchase(self::trasaitionParams($appointment, $appointment['paid_amount'], $response));
        //            FreelancerEarning::where('purchase_id', $appointment['purchase']['id'])->update(['is_archive' => 1]);
        //        }
    }

    public static function singleAppointmentCancelledByCustomer($inputs)
    {
        // check if booking has ever been confirmed
        $checkConfirm = false;
        $appointment = Appointment::getAppointmentWithPurchase($inputs['appointment_uuid'])[0];
        if (!empty($appointment['purchased_package_uuid'])) {
            $purchasePackageId = \App\PurchasesPackage::where('purchased_packages_uuid', $appointment['purchased_package_uuid'])->first()->id;
            $purchase = \App\Purchases::where('purchased_package_id', $purchasePackageId)->with('purchasesTransition')->first();
            $purchase = !empty($purchase) ? $purchase->toArray() : null;
            $appointment['purchase'] = $purchase;
            // if (isset($inputs['update_type']) && !empty($inputs['update_type']) && $inputs['update_type'] == 'package') {
            //     $appointment['paid_amount'] = $appointment['package_paid_amount'];
            // }
            $allPackageAppointments = Appointment::where('purchased_package_uuid', $appointment['purchased_package_uuid'])->get();
            $appointmentPackageStatus = self::checkAllPackageAppointmentStatuses($allPackageAppointments);
        }

        if ($appointment['status'] != 'confirmed') {
            Log::channel('daily_change_status')->debug('status != confirmed');
            Log::channel('daily_change_status')->debug('Appointment Inputs');
            Log::channel('daily_change_status')->debug($inputs);
            Log::channel('daily_change_status')->debug('Appointment Database');
            Log::channel('daily_change_status')->debug($appointment);
            $reschedules = \App\RescheduledAppointment::getRescheduleData('appointment_id', $appointment['id']);
            $checkConfirm = self::getRescheduleWithConfirmStatus($reschedules);
            if ($checkConfirm == false) {

                $response = self::singleAppointmentRejectedByFreelancer($inputs);
            }
        }
        if ($appointment['status'] == 'confirmed' || $checkConfirm == true) {

            /* if customer cancelled the appointment after confirmation then following cases will apply
             * ===========================================================
             * case 1: 
             * Customer cancels the appointment then we will check the actual/starting time 
             * of the appointment on which date it will be held. 
             * If the time is greater than 24 hours then the total 
             * amount will be refunded to the customer
             * ===========================================================
             * case 2:
             * 50% refund to customer remaining after deductions add to freelancer earning
             * ============================================================
             * case 3:
             * no refund to customer add paid amount to freelancer earning  
             */

            /* Refund Types and there meanings
             *  1 ====> full refund to customer
             * 2 ====> 50% refund to customer remaining after deductions add to freelancer earning
             * 3 ====> no refund to customer add paid amount to freelancer earning
             */
            $response['response'] = [];
            $status = 'Refunded';
            $purchaseData = true;
            $createEarning = true;
            $updateEarning = true;
            $purchaseTransactionData = true;
            $refundType = self::getRefundType($appointment);

            $calculatedRefundAndEarning = self::getRefundAndEarningAfterCalculations($appointment, $refundType);

            if ($calculatedRefundAndEarning['customer_refund'] > 0) {
                // call to checkout to refund customer
                $appointment['paid_amount'] = $calculatedRefundAndEarning['customer_refund'];
                $response = Checkout::appointmentFullRefund($appointment, $inputs['status']);
            }
            if (!empty($appointment['purchased_package_uuid'])) {
                $status = $purchase['status'];
            }
            if ($calculatedRefundAndEarning['freelancer_earning'] >= 0 && empty($appointment['purchased_package_uuid'])) {
                // get previous earning
                // test this scenario before demo
                $getEarning = FreelancerEarning::where('appointment_id', $appointment['id'])->where('is_archive', 0)->orderBy('id', 'desc')->first();
                $updateEarning = FreelancerEarning::updateData('freelancer_earnings_uuid', $getEarning['freelancer_earnings_uuid'], ['is_archive' => 1]);

                // Add to Freelancer Earning but before delete previous earning because 
                // customer has cancelled the booking
                $appointment['freelancer_earning'] = $calculatedRefundAndEarning['freelancer_earning'];
                if ($calculatedRefundAndEarning['freelancer_earning'] > 0) {
                    $records = self::prepareRefundFreelancerEarningRecord($appointment);
                    $createEarning = FreelancerEarning::createRecords($records);
                }
                //                else {
                //                    if (!empty($getEarning)) {
                //                        $previousEarning = $getEarning->toArray();
                //                        $appointmentEarning = $appointment['paid_amount'] - (($appointment['purchase']['circl_fee'] / 100) * $appointment['paid_amount']) - (($appointment['purchase']['transaction_charges'] / 100) * $appointment['paid_amount']);
                //                        $calculatedRefundAndEarning['freelancer_earning'] = $previousEarning['earned_amount'] - $appointmentEarning;
                //                    }
                //                    $records = self::prepareFreelancerEarningRecord($appointment);
                //                    $createEarning = FreelancerEarning::createRecords($records);
                //                }
            }
            if ($calculatedRefundAndEarning['freelancer_earning'] >= 0 && !empty($appointment['purchased_package_uuid'])) {

                $getEarning = FreelancerEarning::where('appointment_id', $appointment['id'])->where('is_archive', 0)->orderBy('id', 'desc')->first();
                $updateEarning = FreelancerEarning::updateData('freelancer_earnings_uuid', $getEarning['freelancer_earnings_uuid'], ['is_archive' => 1]);

                // Add to Freelancer Earning but before delete previous earning because 
                // customer has cancelled the booking
                if ($calculatedRefundAndEarning['freelancer_earning'] > 0) {
                    $appointment['purchased_package_id'] = $appointment['purchase']['purchased_package_id'];
                    $appointment['freelancer_earning'] = $calculatedRefundAndEarning['freelancer_earning'];
                    $records = self::prepareRefundFreelancerEarningRecord($appointment);
                    $createEarning = FreelancerEarning::createRecords($records);
                }
                //                if (!empty($getEarning)) {
                //                    $previousEarning = $getEarning->toArray();
                //                    $appointmentEarning = $appointment['paid_amount'] - (($appointment['purchase']['circl_fee'] / 100) * $appointment['paid_amount']) - (($appointment['purchase']['transaction_charges'] / 100) * $appointment['paid_amount']);
                //                    $calculatedRefundAndEarning['freelancer_earning'] = $previousEarning['earned_amount'] - $appointmentEarning;
                //                    $appointment['freelancer_earning'] = $calculatedRefundAndEarning['freelancer_earning'];
                //                }
                //                $appointment['purchased_package_id'] = $appointment['purchase']['purchased_package_id'];
                //                $records = self::prepareFreelancerEarningRecord($appointment);
                //                $createEarning = FreelancerEarning::createRecords($records);
            }

            $params = self::trasaitionParams($appointment, $appointment['paid_amount'], $response);
            if (isset($appointmentPackageStatus) && !empty($appointmentPackageStatus) && $appointmentPackageStatus == 'cancelled') {
                $status = 'Refunded';
            }
            $purchaseData = \App\Purchases::where('id', $params['purchase_id'])->update(['status' => strtolower($status)]);
            $purchaseTransactionData = PurchasesTransition::createPurchase($params);
            if (((isset($response['success'])) && ($response['success'] == false)) || (!$purchaseData) || (!$purchaseTransactionData) ||
                (!$updateEarning)
            ) {
                DB::rollback();
                return ['success' => false, 'message' => 'Error occured while processing payment'];
            }
            return ['success' => true, 'message' => 'Successful Request'];
        } else {
            return true;
        }
    }

    public static function prepareFreelancerEarningRecord($record)
    {
        //        $amountDueOn = !empty($record['appointment_end_date_time']) ? date('Y-m-d H:i:s', ($record['appointment_end_date_time'] . '+1 month')) : null;
        $amountDueOn = !empty($record['appointment_end_date_time']) ? strtotime(date("Y-m-d H:i:s", strtotime("+1 month", $record['appointment_end_date_time']))) : null;
        $data = [
            'freelancer_earnings_uuid' => UuidHelper::generateUniqueUUID('freelancer_earnings', 'freelancer_earnings_uuid'),
            'freelancer_id' => (isset($record['freelancer_id'])) ? $record['freelancer_id'] : null,
            'earned_amount' => $record['freelancer_earning'],
            'purchase_id' => $record['purchase']['id'],
            'subscription_id' => null,
            'purchased_package_id' => (isset($record['purchase']['purchased_package_id']) && !empty($record['purchase']['purchased_package_id'])) ? $record['purchase']['purchased_package_id'] : null,
            'class_booking_id' => null,
            'appointment_id' => $record['id'],
            'amount_due_on' => $amountDueOn,
            'currency' => $record['currency']
        ];
        return $data;
    }

    public static function prepareRefundFreelancerEarningRecord($record)
    {
        //        $amountDueOn = !empty($record['appointment_end_date_time']) ? date('Y-m-d H:i:s', ($record['appointment_end_date_time'] . '+1 month')) : null;
        $amountDueOn = strtotime(date("Y-m-d H:i:s"));
        $data = [
            'freelancer_earnings_uuid' => UuidHelper::generateUniqueUUID('freelancer_earnings', 'freelancer_earnings_uuid'),
            'freelancer_id' => (isset($record['freelancer_id'])) ? $record['freelancer_id'] : null,
            'earned_amount' => $record['freelancer_earning'],
            'purchase_id' => $record['purchase']['id'],
            'subscription_id' => null,
            'purchased_package_id' => (isset($record['purchased_package_id']) && !empty($record['purchased_package_id'])) ? $record['purchased_package_id'] : null,
            'class_booking_id' => null,
            'appointment_id' => $record['id'],
            'amount_due_on' => $amountDueOn,
            'currency' => $record['currency']
        ];
        return $data;
    }

    /* Refund Types and there meanings
     *  1 ====> full refund to customer
     * 2 ====> 50% refund to customer remaining after deductions add to freelancer earning
     * 3 ====> no refund to customer add paid amount in freelancer earning after deductions
     */

    public static function getRefundType($booking)
    {
        $refundType = null;
        $timeDifference = CommonHelper::getTimeDifferenceInMinutes((date('Y-m-d H:i:s')), date('Y-m-d H:i:s', $booking['appointment_start_date_time']));

        if ($timeDifference > 1440) {
            // if time difference greater then 24 hours then customer will get a full refund
            $refundType = 1;
        } elseif ($timeDifference < 1440 && $timeDifference > 360) {
            // 1440 minutes in 24 hours and 360 minutes in 6 hours
            // between 24 and 6 hours then 50% refund to customer and remaining to freelancer
            $refundType = 2;
        } else {
            // no refund paid amount after deductions will be given to freelancer
            $refundType = 3;
        }
        return $refundType;
    }

    public static function getRefundAndEarningAfterCalculations($booking, $refundType = null)
    {
        $circlChargesPercent = !empty($booking['purchase']['circl_fee']) ? $booking['purchase']['circl_fee'] : 0;
        $circlCharges = ($circlChargesPercent / 100) * $booking['paid_amount'];
        $checkoutChargesPercent = !empty($booking['purchase']['transaction_charges']) ? $booking['purchase']['transaction_charges'] : 0;
        $checkoutCharges = ($checkoutChargesPercent / 100) * ($booking['paid_amount']);

        $amount_after_deduction = !empty($booking['paid_amount']) ? ((($booking['paid_amount'] - $circlCharges) - $checkoutCharges)) : 0;
        // as of previous scenario we were deducting circl and transaction charges from customer refund
        //        $amount_after_deduction = !empty($booking['paid_amount']) ? ((($booking['paid_amount'] - $circlCharges) - $checkoutCharges)) : 0;
        $paidAmount = $booking['paid_amount'];
        $amount['customer_refund'] = null;
        $amount['freelancer_earning'] = null;

        // Only for launch after that we will make this FULL_REFUND to false and the actual logic will start working
        if (env('FULL_REFUND') && env('FULL_REFUND') == true) {
            $amount['customer_refund'] = $paidAmount;
            $amount['freelancer_earning'] = 0;
        } else {
            if ($refundType == 1) {
                // customer will receive full refund freelancer will get nothing
                $amount['customer_refund'] = $paidAmount;
                $amount['freelancer_earning'] = 0;
            } elseif ($refundType == 2) {
                // 50% refund to customer and remaining to freelancer

                $amount['customer_refund'] = $paidAmount - (($paidAmount * 50) / 100);
                $amount['freelancer_earning'] = $amount_after_deduction - $amount['customer_refund'];
            } elseif ($refundType == 3) {
                // no refund to customer and paid amount after deductions to freelancer
                $amount['customer_refund'] = 0;
                $amount['freelancer_earning'] = $amount_after_deduction;
            }
        }
        $amount['customer_refund'] = ($amount['customer_refund'] != 0) ? round($amount['customer_refund'], 2) : 0;
        $amount['freelancer_earning'] = ($amount['freelancer_earning'] != 0) ? round($amount['freelancer_earning'], 2) : 0;
        return $amount;
    }

    public static function trasaitionParams($appointment, $amount, $response)
    {
        return [
            'purchase_id' => $appointment['purchase']['id'],
            'currency' => !empty($appointment['purchase']['purchases_transition']) ? $appointment['purchase']['purchases_transition']['currency'] : $appointment['purchase']['purchased_in_currency'],
            'amount' => $amount,
            'transaction_type' => 'refund',
            'gateway_response' => serialize($response),
            'request_parameters',
            'transaction_status' => 'cancelled',
            'checkout_transaction_id' => !empty($appointment['purchase']['purchases_transition']) ? $appointment['purchase']['purchases_transition']['checkout_transaction_id'] : null,
            'class_booking_id' => (isset($appointment['class_booking_id']) && !empty($appointment['class_booking_id'])) ? $appointment['class_booking_id'] : null,
            'appointment_booking_id' => $appointment['id'],
            'cko_id' => !empty($appointment['purchase']['purchases_transition']) ? $appointment['purchase']['purchases_transition']['cko_id'] : null,
            'customer_card_id' => !empty($appointment['purchase']['purchases_transition']) ? $appointment['purchase']['purchases_transition']['customer_card_id'] : null,
        ];
    }

    public static function differenceTwoDates($date)
    {
        $date1 = new \DateTime($date);
        $date2 = new \DateTime(date('Y-m-d H:i:s'));
        $interval = $date1->diff($date2);
        return $interval;
    }

    public static function capturePayment($captureParams, $status, $params)
    {
        \Log::info('in capture scenario');
        $paymentDetail = [];
        if ($captureParams['payment_by'] == 'card' || $captureParams['payment_by'] == 'apple_pay') {
            // capture and then get payment detail
            $paymentDetail = Checkout::capturePayment($captureParams);
            \Log::info('in capture scenario');

            \Log::info(print_r($paymentDetail, true));
            if (isset($paymentDetail->status) && $paymentDetail->status == 'Captured') {
                $earnedAmount = (isset($params['earned_amount'])) ? $params['earned_amount'] : $params[0]['earned_amount'];
                if ($earnedAmount > 0) {
                    $updateData = CommonHelper::updatePurchaseAndPurcaseTranistion($status);
                    $frelancerEarning = FreelancerEarning::createRecords($params);
                    if (!$updateData && !$frelancerEarning) {
                        DB::rollback();
                        return ['success' => false, 'message' => 'error occurred while updating payment related data'];
                    }
                }
                return true;
            } else {
                DB::rollback();
                return ['success' => false, 'message' => 'error occurred while capturing payment', 'data' => $paymentDetail];
            }
        } else {
            $updateData = CommonHelper::updatePurchaseAndPurcaseTranistion($status);
            // add freelancer earning
            $earnedAmount = (isset($params['earned_amount'])) ? $params['earned_amount'] : $params[0]['earned_amount'];
            if ($earnedAmount > 0) {
                $frelancerEarning = FreelancerEarning::createRecords($params);
                if (!$updateData && !$frelancerEarning) {
                    DB::rollback();
                    return ['success' => false, 'message' => 'error occurred while updating payment related data'];
                }
            }

            return true;
            // do something
            //            DB::rollback();
            //            return ['success' => false, 'message' => 'error occurred while capturing payment'];
        }
    }

    public static function singleAppointmentConfirmedStatus($inputs)
    {

        $record = self::appointmentEarningParams(Appointment::getAppointmentWithPurchase($inputs['appointment_uuid']));
        $params = $record['params'];
        $captureParams = $record['captures'];
        $status = $record['status'];
        $response = self::capturePayment($captureParams, $status, $params);
        return $response;
    }

    public static function packageAppointmentConfirmedStatus($inputs)
    {
        $appointment = Appointment::getPackageAppointments('purchased_package_uuid', $inputs['purchased_package_uuid']);
        // get purchase
        $purchasePackageId = \App\PurchasesPackage::where('purchased_packages_uuid', $inputs['purchased_package_uuid'])->first()->id;
        $purchase = \App\Purchases::where('purchased_package_id', $purchasePackageId)->with('purchasesTransition')->first();
        $purchase = !empty($purchase) ? $purchase->toArray() : null;
        $appointment[0]['purchase'] = $purchase;
        $record = self::appointmentPackageEarningParams($appointment, $purchase);
        $params = $record['params'];
        $captureParams = $record['captures'];
        $status = $record['status'];
        $response = self::capturePayment($captureParams, $status, $params);
        return $response;
    }
}

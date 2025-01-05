<?php

namespace App\Helpers;

use App\Customer;
use App\MoyasarWebForm;
use App\payment\checkout\Checkout;
use App\PurchasesPackage;
use App\Traits\EarningAmount;
use Illuminate\Support\Facades\Validator;
use DB;
use App\Classes;
use App\ClassSchedule;
use App\ClassBooking;
use App\Helpers\ClassBookingResponseHelper;
use App\Package;
use App\Freelancer;

Class ClassBookingHelper {

    use EarningAmount;
    /*
      |--------------------------------------------------------------------------
      | ClassHelper that contains all the class related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use Class processes
      |
     */

    /**
     * Description of ClassHelper
     *
     * @author ILSA Interactive
     */
    public static function classBooking($inputs) {
        \Log::info('===========Class booking inputs=================');
        \Log::info(print_r($inputs, true));
        $validation = Validator::make($inputs, ClassValidationHelper::classBookingRules()['rules'], ClassValidationHelper::classBookingRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $inputs['transaction_id'] = !empty($inputs['transaction_id']) ? $inputs['transaction_id'] : null;

        $class_detail = Classes::getSingleClass('class_uuid', $inputs['class_uuid']);

        if (empty($class_detail)) {
            return CommonHelper::jsonErrorResponse(ClassValidationHelper::freelancerAddClassRules()['message_' . strtolower($inputs['lang'])]['invalid_data']);
        }

        $class_schedule = ClassSchedule::getClassSchedule('class_schedule_uuid', $inputs['class_schedule_uuid']);
        if (empty($class_schedule)) {
            return CommonHelper::jsonErrorResponse(ClassValidationHelper::freelancerAddClassRules()['message_' . strtolower($inputs['lang'])]['invalid_data']);
        }



        if ($class_schedule['class_date'] < date("Y-m-d")) {
            return CommonHelper::jsonErrorResponse(ClassValidationHelper::freelancerAddClassRules()['message_' . strtolower($inputs['lang'])]['class_date_pass']);
        } elseif ($class_schedule['class_date'] == date("Y-m-d") && $class_schedule['from_time'] < date("H:i:s")) {
            return CommonHelper::jsonErrorResponse(ClassValidationHelper::freelancerAddClassRules()['message_' . strtolower($inputs['lang'])]['class_time_pass']);
        }

        $class_array = [
            'date' => $class_schedule['class_date'],
            'from_time' => $class_schedule['from_time'],
            'to_time' => $class_schedule['to_time'],
            'logged_in_uuid' => $inputs['logged_in_uuid'],
            'local_timezone' => $inputs['local_timezone'],
            'customer_id' => CommonHelper::getCutomerIdByUuid($inputs['customer_uuid']),
            'promocode_uuid' => !empty($inputs['promocode_uuid']) ? $inputs['promocode_uuid'] : null,
            'actual_price' => $inputs['actual_price'],
            'discount_amount' => !empty($inputs['discount_amount']) ? $inputs['discount_amount'] : null,
            'discounted_price' => !empty($inputs['discounted_price']) ? $inputs['discounted_price'] : null,
            'travelling_charges' => !empty($inputs['travelling_charges']) ? $inputs['travelling_charges'] : null,
            'paid_amount' => $inputs['paid_amount'],
            'transaction_id' => $inputs['transaction_id'],
            'currency' => $inputs['currency'],
            'status' => $inputs['status'],
            'class_schedule_id' => CommonHelper::getClassSchedulesIdByUuid($inputs['class_schedule_uuid']),
            'class_id' => CommonHelper::getClassIdByUuid($inputs['class_uuid']),
            'login_user_type' => $inputs['login_user_type'],
        ];

        $inputs['freelancer_id'] = $class_detail['freelancer_id'];
        $class_array['freelancer_id'] = $class_detail['freelancer_id'];

        $check_customer_time_available = CustomerChecksHelper::checkCustomerExistingSession($class_array);
        if (!$check_customer_time_available['success']) {
            return CommonHelper::jsonErrorResponse($check_customer_time_available['message']);
        }
        $status = ['confirmed'];
        $total_class_booking = ClassBooking::classBookingCount('class_schedule_id', CommonHelper::getClassSchedulesIdByUuid($inputs['class_schedule_uuid']), $status);
        if ($class_detail['no_of_students'] <= $total_class_booking) {
            return CommonHelper::jsonErrorResponse(ClassValidationHelper::freelancerAddClassRules()['message_' . strtolower($inputs['lang'])]['class_full_error']);
        }
        $inputs['class_id'] = CommonHelper::getClassIdByUuid($inputs['class_uuid']);
        $inputs['class_schedule_id'] = CommonHelper::getClassSchedulesIdByUuid($inputs['class_schedule_uuid']);
        $is_exist = ClassBooking::getClassBookingDetail('customer_id', CommonHelper::getCutomerIdByUuid($inputs['customer_uuid']), $inputs, true);
        if ($is_exist) {
            return CommonHelper::jsonErrorResponse(ClassValidationHelper::freelancerAddClassRules()['message_' . strtolower($inputs['lang'])]['class_booking_exist']);
        }
        if (!empty($inputs['package_uuid'])) {
            $class_array['package_uuid'] = $inputs['package_uuid'];
        }
        if (isset($inputs['card_id']) && ($inputs['card_id'] == 'wallet')) {
            $class_array['payment_status'] = 'captured';
            $class_array['status'] = 'confirmed';
        }

        $class_booking = ClassBooking::createClass($class_array);

        if (!empty($class_booking)) {
//          $class_array['actual_amount'] = $class_detail['price'];
            $class_booking['actual_amount'] = $class_array['actual_price'];
//          $class_array['total_amount'] = $class_detail['price'];
            $class_booking['total_amount'] = $class_array['paid_amount'];
            $class_booking['login_user_type'] = $inputs['login_user_type'];
            $class_booking['class_booking_uuid'] = $class_booking['class_booking_uuid'];
            $class_booking['from_currency'] = $class_detail['currency'];
            $class_booking['to_currency'] = $inputs['currency'];
            $class_booking['exchange_rate'] = config('general.globals.' . $inputs['currency']);
//          $class_array['payment_brand'] = !empty($inputs['payment_details']->paymentBrand) ? $inputs['payment_details']->paymentBrand : null;
            $class_booking['payment_brand'] = !empty($inputs['payment_details']->source) && !empty($inputs['payment_details']->source->type) ? $inputs['payment_details']->source->type : null;
            $class_booking['moyasar_fee'] = !empty($inputs['payment_details']->fee) ? $inputs['payment_details']->fee : 0;
            $class_array['from_currency'] = !empty($inputs['payment_details']->currency) ? $inputs['payment_details']->currency : 0;

//            $save_transaction = TransactionHelper::saveClassTransaction($class_array);
//            if (!$save_transaction) {
//               DB::rollBack();
//               return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['add_transaction_log_error']);
//            }
            // $saveTransactionPaymentDue = TransactionHelper::saveTransactionPaymentDue($save_transaction->toArray(), 'class', $class_array['date']);
//          if (!$saveTransactionPaymentDue) {
//               DB::rollBack();
//               return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['add_transaction_request_due_error']);
//          }

            $class_booking['save_transaction'] = ['$save_transaction->toArray()'];
            $class_booking['freelancer_id'] = $class_detail['freelancer_id'];
            $class_booking['lang'] = $inputs['lang'];

            $save_class_client = ClientHelper::addClient($class_booking);
            if (!$save_class_client['success']) {
                DB::rollBack();
                return CommonHelper::jsonErrorResponse($save_class_client['message']);
            }
            $response = ClassBookingResponseHelper::classBookingResponse($class_booking);
            $class_booking['class_date'] = $class_schedule['class_date'];
            $paymentResult = '';
            $inputs['purchasing_type'] = 'class';
            if ($inputs['paid_amount'] <= 0) {
                $response['res'] = 'verify';
                $response = Checkout::entryInPurchases($inputs, $paymentResult, $class_booking, 'class');
            } else {
                $response = Checkout::paymentType($inputs, $class_booking, 'class');
            }

            // Payment method for card and cart
//            $response = Checkout::paymentType($inputs,$class_booking,'class');

            if ($response['res'] == false) {
                return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['save_appointment_error']);
            }
            if ($response['res'] == 'verify') {
                DB::commit();
                //TODO::add in freelancer earning table
//                if ($inputs['status'] == 'confirmed') {
                    self::CreateEarningRecords('single_class_booking', $class_booking['id']);
//                }
                $data = \App\ClassBooking::getSingleBooking('id', $class_booking['id']);
                if (isset($inputs['card_id']) && ($inputs['card_id'] == 'wallet') && !empty($data)) {
                    $data['freelancer_id'] = $data['class_object']['freelancer_id'];
                    $data['customer_id'] = CommonHelper::getCutomerIdByUuid($inputs['customer_uuid']);
                    ProcessNotificationHelper::sendClassBookingNotification($data, $inputs);
                }
                return CommonHelper::jsonSuccessResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['save_appointment_success'], $response);
            }

            DB::commit();
//            ProcessNotificationHelper::sendClassBookingNotification($class_booking, $inputs);

            $check = self::prepareClassBookingEmailData($class_booking, "add_class", "New Class Booking", "confirmed");

            $response = null;
            return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
        }
        return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['success_error']);
    }

    public static function prepareClassBookingEmailData($class, $template = "", $subject = "", $status) {

        $get_detail = ClassBooking::getClassBookingDetailforEmail("class_booking_uuid", $class['class_booking_uuid'], $class);
        $customer = Customer::getSingleCustomerDetail('id', $class['customer_id']);

        // Email Subject
        $email_subject = "Circl - " . (strtolower($subject) == "class status updated") ? "Class " . ucwords($status) : "New Booking Request";
        $message = ($subject == "class Status Updated") ? "Class has been " . $status : "You have a new Booking Request";
        $update_status = ($subject == "Class Status Updated") ? 1 : 0;
        $send = false;
        //send email to customer
//        if (!empty($get_detail['customer'])) {
//            $get_detail['email'] = $get_detail['customer']['email'];
//            $get_detail['send_to'] = "customer";
//            $get_detail['update_status'] = $update_status;
//            if (!empty($get_detail['email'])) {
//                $send = EmailSendingHelper::sendClassBookingEmail($get_detail, $message, $status, $template, $email_subject);
//            }
//        }
        //send email to freelancer
        if (!empty($get_detail['class_object']['freelancer'])) {
            $get_detail['email'] = $get_detail['class_object']['freelancer']['email'];
            $get_detail['send_to'] = "freelancer";
            $get_detail['customer'] = $customer;
            $get_detail['update_status'] = $update_status;
            if (!empty($get_detail['email'])) {
                $send = EmailSendingHelper::sendClassBookingEmail($get_detail, $message, $status, $template, $email_subject);
            }
        }
        return ($send) ? true : false;
    }

//TODO:: this function is too complex logic is complex and code style is complex we will refector this section
    public static function multipleClassBooking($inputs) {

        $response = [];
        if (isset($inputs['booking']) && !empty($inputs['booking'])) {

            foreach ($inputs['booking'] as $key => $booking) {

                $booking['logged_in_uuid'] = !empty($inputs['logged_in_uuid']) ? $inputs['logged_in_uuid'] : null;
                $booking['local_timezone'] = !empty($inputs['local_timezone']) ? $inputs['local_timezone'] : null;
                $booking['currency'] = !empty($inputs['currency']) ? $inputs['currency'] : null;
                $validation = Validator::make($booking, ClassValidationHelper::multipleclassBookingRules()['rules'], ClassValidationHelper::multipleclassBookingRules()['message_' . strtolower($inputs['lang'])]);
                if ($validation->fails()) {
                    return CommonHelper::jsonErrorResponse($validation->errors()->first() . ' in ' . CommonHelper::getEnglishInteger($key + 1) . ' block');
                }
            }

            $inputs['purchase_time'] = null;

            foreach ($inputs['booking'] as $key => $booking) {

                $class_detail = Classes::getSingleClass('class_uuid', $booking['class_uuid']);

                if (empty($class_detail)) {
                    return CommonHelper::jsonErrorResponse(ClassValidationHelper::freelancerAddClassRules()['message_' . strtolower($inputs['lang'])]['invalid_data']);
                }

                $class_schedule = ClassSchedule::getClassSchedule('class_schedule_uuid', $booking['class_schedule_uuid']);

                if (empty($class_schedule)) {
                    return CommonHelper::jsonErrorResponse(ClassValidationHelper::freelancerAddClassRules()['message_' . strtolower($inputs['lang'])]['invalid_data']);
                }

                $booking['datetime'] = $class_schedule['class_date'] . ' ' . $class_schedule['from_time'];

                $check_class_overlap_array = ['date' => $class_schedule['class_date'], 'from_time' => $class_schedule['from_time'],
                    'to_time' => $class_schedule['to_time'], 'logged_in_uuid' => $inputs['logged_in_uuid'],
                    'local_timezone' => $inputs['local_timezone'],
                    'customer_id' => CommonHelper::getRecordByUuid('customers', 'customer_uuid', $booking['customer_uuid']),
                    'login_user_type' => $inputs['login_user_type']];

                $check_customer_time_available = CustomerChecksHelper::checkCustomerExistingSession($check_class_overlap_array);

                if (!$check_customer_time_available['success']) {
                    return CommonHelper::jsonErrorResponse($check_customer_time_available['message']);
                }

                $status = ['confirmed'];
                $schedulesId = CommonHelper::getRecordByUuid('class_schedules', 'class_schedule_uuid', $booking['class_schedule_uuid']);
                $total_class_booking = ClassBooking::classBookingCount('class_schedule_id', $schedulesId, $status);

                if ($class_detail['no_of_students'] <= $total_class_booking) {
                    return CommonHelper::jsonErrorResponse(ClassValidationHelper::freelancerAddClassRules()['message_' . strtolower($inputs['lang'])]['class_full_error']);
                }

                $class_schedule = ClassSchedule::getClassSchedule('class_schedule_uuid', $booking['class_schedule_uuid']);
//
//                if ($class_schedule['class_date'] < date("Y-m-d")) {
//                    return CommonHelper::jsonErrorResponse(ClassValidationHelper::freelancerAddClassRules()['message_' . strtolower($inputs['lang'])]['class_date_pass']);
//                }
//
//                elseif ($class_schedule['class_date'] == date("Y-m-d") && $class_schedule['from_time'] < date("H:i:s")) {
//                    return CommonHelper::jsonErrorResponse(ClassValidationHelper::freelancerAddClassRules()['message_' . strtolower($inputs['lang'])]['class_time_pass']);
//                }

                $customerId = CommonHelper::getRecordByUuid('customers', 'customer_uuid', $booking['customer_uuid']);

                $booking['class_id'] = $class_detail['id'];
                $booking['class_schedule_id'] = $class_schedule['id'];
                $is_exist = ClassBooking::getClassBookingDetail('customer_id', $customerId, $booking, true);

//                if ($is_exist){
//                    $customer = Customer::getSingleCustomer('id', $is_exist[0]['customer_id']);
//                    return CommonHelper::jsonErrorResponse($customer['first_name'] . ' ' . $customer['last_name'] . ' already have in class');
//                }
                $inputs['booking'][$key] = $booking;
            }

            $transaction_id = null;

            usort($inputs['booking'], function ($a, $b) {
                $t1 = strtotime($a['datetime']);
                $t2 = strtotime($b['datetime']);
                return $t1 - $t2;
            });
//            foreach ($inputs['booking'] as $key => $booking) {
//                $booking['total_session'] = count($inputs['booking']);
//                $booking['session_number'] = (int) ($key + 1);
//                $inputs['booking'][$key] = $booking;
//            }


            $purchased_package_uuid = PurchasesPackage::createPackagePurchase(
                            [
                                'customer_id' => CommonHelper::getCutomerIdByUuid($inputs['booking'][0]['customer_uuid']),
                                'package_id' => CommonHelper::getPackageIdByUuid($inputs['booking'][0]['package_uuid'])
            ]);
            //$purchased_package_uuid = UuidHelper::generateUniqueUUID('appointments', 'purchased_package_uuid');
            $inputs['purchased_package_uuid'] = $purchased_package_uuid;
            $packageClass = [];
            foreach ($inputs['booking'] as $key => $booking) {
                $class_detail = Classes::getSingleClass('class_uuid', $booking['class_uuid']);

                $booking['lang'] = $inputs['lang'];
                //$class_detail = Classes::getSingleClass('class_uuid', $booking['class_uuid']);

                $class_array = [
                    'customer_id' => CommonHelper::getCutomerIdByUuid($booking['customer_uuid']),
                    'transaction_id' => $transaction_id,
                    'status' => $booking['status'],
                    'class_schedule_id' => CommonHelper::getClassSchedulesIdByUuid($booking['class_schedule_uuid']),
                    'class_id' => CommonHelper::getClassIdByUuid($booking['class_uuid'])
                ];

                $class_array['session_number'] = (int) ($key + 1);
                $class_array['total_session'] = count($inputs['booking']);
//              $class_array = ['customer_uuid' => $booking['customer_uuid'], 'transaction_id' => $transaction_id, 'status' => $booking['status'], 'class_schedule_uuid' => $booking['class_schedule_uuid'], 'class_uuid' => $booking['class_uuid'], 'total_session' => $booking_count['total_session'], 'session_number' => $booking_count['session_number']];
                $class_array['freelancer_id'] = $class_detail['freelancer_id'];
                $class_array['package_id'] = CommonHelper::getPackageIdByUuid($booking['package_uuid']);
                $class_array['promocode_id'] = !empty($booking['promocode_uuid']) ? CommonHelper::getPromoCodeIdByUuid($booking['promocode_uuid']) : null;
                $class_array['actual_price'] = $booking['actual_price'];
                $class_array['package_paid_amount'] = isset($booking['package_paid_amount']) ? $booking['package_paid_amount'] : null;
                $class_array['discount_amount'] = !empty($booking['discount_amount']) ? $booking['discount_amount'] : null;
                $class_array['discounted_price'] = !empty($booking['discounted_price']) ? $booking['discounted_price'] : null;
                $class_array['travelling_charges'] = !empty($booking['travelling_charges']) ? $booking['travelling_charges'] : null;
                $class_array['paid_amount'] = $booking['paid_amount'];
                $class_array['currency'] = $inputs['currency'];
                $class_array['purchased_package_uuid'] = $purchased_package_uuid;
                $inputs['purchased_package_uuid'] = $purchased_package_uuid;
                if (isset($inputs['card_id']) && ($inputs['card_id'] == 'wallet')) {
                    $class_array['payment_status'] = 'captured';
                }
                $class_booking = ClassBooking::createClass($class_array);

                if ($key == 0) {
                    $inputs['purchase_time'] = $class_booking['created_at'];
                    $inputs['freelancer_id'] = $class_detail['freelancer_id'];
                    $inputs['customer_uuid'] = $booking['customer_uuid'];
                }
                if (!empty($class_booking)) {

                    $class_booking['actual_amount'] = $class_booking['actual_price'];
                    $class_booking['total_amount'] = $class_booking['paid_amount'];
                    $class_booking['login_user_type'] = $inputs['login_user_type'];
                    $class_booking['class_booking_uuid'] = $class_booking['class_booking_uuid'];
                    $class_booking['from_currency'] = $class_detail['currency'];
                    $class_booking['to_currency'] = $inputs['currency'];
                    $class_booking['exchange_rate'] = config('general.globals.' . $inputs['currency']);
                    $class_booking['moyasar_fee'] = !empty($inputs['payment_details']->fee) ? $inputs['payment_details']->fee : 0;

//                    $save_transaction = TransactionHelper::saveClassTransaction($class_array);
//                    if (!$save_transaction) {
//                        DB::rollBack();
//                        return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['add_transaction_log_error']);
//                    }
//                    $saveTransactionPaymentDue = TransactionHelper::saveTransactionPaymentDue($save_transaction->toArray(), 'class', $booking['datetime']);
//                    if (!$saveTransactionPaymentDue) {
//                        DB::rollBack();
//                        return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['add_transaction_request_due_error']);
//                    }

                    $class_booking['save_transaction'] = ['$save_transaction->toArray()'];
                    $class_booking['freelancer_id'] = $class_detail['freelancer_id'];
                    $class_booking['lang'] = $inputs['lang'];
                    //TODO:: freelancer earning entry

                    $save_class_client = ClientHelper::addClient($class_booking);

                    if (!$save_class_client['success']) {
                        DB::rollBack();
                        return CommonHelper::jsonErrorResponse($save_class_client['message']);
                    }

                    $packageClass[] = $class_booking;
                    $response[$key] = ClassBookingResponseHelper::classBookingResponse($class_booking);
                } else {
                    DB::rollBack();
                    return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['success_error']);
                }
            }

            // TODO:: new payment method checkout
            $inputs['class'] = 'class';
            $inputs['purchasing_type'] = 'class';
            $paymentResult = '';
            if ($inputs['paid_amount'] <= 0) {
                $response['res'] = 'verify';
                $response = Checkout::entryInPurchases($inputs, $paymentResult, $class_booking, 'package');
            } else {
                $response = Checkout::paymentType($inputs, $class_booking, 'package');
            }
//            $response = Checkout::paymentType($inputs, $class_booking, 'package');
            if ($response['res'] == false) {
                return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['save_appointment_error']);
            }
            if ($response['res'] == 'verify') {
                DB::commit();

                $packedClasses = ClassBooking::getPackageClass($purchased_package_uuid);
                foreach ($packageClass as $packClass) {
                    self::CreateEarningRecords('multi_class_booking', $packClass['id']);
                }
                $data = \App\ClassBooking::getSingleBooking('purchased_package_uuid', $purchased_package_uuid);
                if (isset($inputs['card_id']) && ($inputs['card_id'] == 'wallet') && !empty($data)) {
                    $booking_data['freelancer_id'] = $data['class_object']['freelancer_id'];
                    $booking_data['login_user_type'] = $inputs['login_user_type'];
                    $booking_data['customer_id'] = CommonHelper::getCutomerIdByUuid($inputs['booking'][0]['customer_uuid']);
                    $booking_data['login_user_type'] = $inputs['login_user_type'];
                    $booking_data['booking'][0] = $data;
                    ProcessNotificationHelper::sendMultipleClassBookingNotification($booking_data);
                }

                return CommonHelper::jsonSuccessResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['save_appointment_success'], $response);
            }

            \Log::info($inputs);
            \Log::info("flow is here");

//            ProcessNotificationHelper::sendMultipleClassBookingNotification($inputs);
//            self::sendMultipleClassBookingEmail($inputs);
            DB::commit();
            $response = null;
            return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
        }
        return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['success_error']);
    }

    public static function sendMultipleClassBookingEmail($input) {
        if (!empty($input['booking']) && $input['login_user_type'] == "customer") {
            //Package Detail

            $package_detail = Package::where('package_uuid', $input['booking'][0]['package_uuid'])->first();

            \Log::info("flow is here");
            $package_price = $package_detail['price'];
            $currency = $package_detail['currency'];

            if (!empty($input['purchased_package_uuid'])) {
                $purchased_package = ClassBooking::getClassBookings('purchased_package_uuid', $input['purchased_package_uuid']);
                \Log::info("purchased_package detail");
                \Log::info($purchased_package);
                $package_price = !empty($purchased_package[0]['package_paid_amount']) ? $purchased_package[0]['package_paid_amount'] : $package_detail['price'];
                $currency = !empty($purchased_package[0]['currency']) ? $purchased_package[0]['currency'] : $package_detail['currency'];
            }

            \Log::info("package price");
            \Log::info($package_price);

            $freelancer_detail = Freelancer::where('id', $input['freelancer_id'])->first();
            $customer_detail = Customer::where('customer_uuid', $input['logged_in_uuid'])->first();
            $input['freelancer_detail'] = !empty($freelancer_detail) ? $freelancer_detail->toArray() : [];
            $input['customer_detail'] = !empty($customer_detail) ? $customer_detail->toArray() : [];
            $input['package_detail'] = !empty($package_detail) ? $package_detail->toArray() : [];

            $input['message'] = "You have a confirmed Class Package";
            $send = false;
            $input['send_to'] = "freelancer";
            $input['email'] = "";

            foreach ($input['booking'] as $index => $booking) {

                $class['status'] = $booking['status'];
                $class['class_schedule_id'] = $booking['class_schedule_id'];

                $class_data = ClassBooking::getClassBookingDetailForEmail("class_id", $booking['class_id'], $class);
                if (!empty($class_data['schedule'])) {


                    $input['local_timezone'] = $class_data['schedule']['local_timezone'];
                    $input['email'] = !empty($class_data['class_object']['freelancer']) ? $class_data['class_object']['freelancer']['email'] : "";
                    $input['class_data'][$index] = $class_data;
                    //set date_times
                    $input['class_data'][$index]['day'] = CommonHelper::convertTimeToTimezoneDay($class_data['schedule']['class_date'], $class_data['schedule']['saved_timezone'], $class_data['schedule']['local_timezone']);
                    $input['class_data'][$index]['date'] = CommonHelper::convertTimeToTimezoneDate($class_data['schedule']['class_date'], $class_data['schedule']['saved_timezone'], $class_data['schedule']['local_timezone']);
                    $input['class_data'][$index]['month'] = CommonHelper::convertTimeToTimezoneMonth($class_data['schedule']['class_date'], $class_data['schedule']['saved_timezone'], $class_data['schedule']['local_timezone']);
                    $input['class_data'][$index]['from_time'] = CommonHelper::convertTimeToTimezoneTime($class_data['schedule']['from_time'], $class_data['schedule']['saved_timezone'], $class_data['schedule']['local_timezone']);
                    $input['class_data'][$index]['to_time'] = CommonHelper::convertTimeToTimezoneTime($class_data['schedule']['to_time'], $class_data['schedule']['saved_timezone'], $class_data['schedule']['local_timezone']);
                    $input['class_data'][$index]['duration'] = CommonHelper::getTimeDifferenceInMinutes($class_data['schedule']['from_time'], $class_data['schedule']['to_time']);
                    $input['class_data'][$index]['local_timezone'] = $class_data['schedule']['local_timezone'];
                }
            }

            $input['package_price'] = $package_price;
            $input['currency'] = $currency;
            \Log::info($input);
            //send email to freelancer

            if (!empty($input['email'])) {
                unset($input['booking']);
                $send = EmailSendingHelper::sendMultipleClassBookingEmail($input);
            }
        }
    }

}

?>
